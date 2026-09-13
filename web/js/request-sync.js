/**
 * SYNCHRONISATION CENTRALE DES DEMANDES
 *
 * Ce module surveille les changements métier publiés par Yii2 sans recharger la
 * page entière. Une vue active le mécanisme en ajoutant l'attribut
 * data-request-sync-context sur le conteneur qu'elle souhaite actualiser.
 * Lorsqu'un changement autorisé est détecté, le module émet l'événement
 * "can:request-sync" ; la vue peut alors remplacer uniquement son fragment HTML.
 */
(function (window, document) {
    'use strict';

    /*
     * GARDE D'INITIALISATION : AppAsset peut être présent sur des layouts publics.
     * Une seule instance globale est autorisée afin d'éviter plusieurs minuteurs
     * après une navigation restaurée depuis le cache du navigateur.
     */
    if (window.CANRequestSync) {
        return;
    }

    var DEFAULT_DELAY = 10000;
    var MAX_FAILURE_DELAY = 60000;
    var instances = [];
    /*
     * DÉDOUBLONNAGE ENTRE CONTEXTES : une même page peut contenir plusieurs zones
     * synchronisées. Mémoriser les identifiants techniques déjà présentés empêche
     * deux SweetAlert pour le même changement, sans utiliser de donnée métier.
     */
    var shownAlertIds = {};

    /**
     * Représente la surveillance d'un seul conteneur de liste. Le curseur reste
     * propre à ce contexte : une liste AO et une liste MRO ne partagent jamais
     * leur progression, même si elles sont ouvertes dans deux onglets différents.
     */
    function RequestSyncInstance(element) {
        this.element = element;
        this.context = String(element.getAttribute('data-request-sync-context') || '').trim();
        this.endpoint = element.getAttribute('data-request-sync-url') || '/request-sync/changes';
        this.fragmentUrl = element.getAttribute('data-request-sync-fragment-url') || '';
        /*
         * CURSEUR DU RENDU INITIAL : le serveur l'a capturé avant de lire la liste.
         * Le conserver immédiatement évite qu'un premier poll tardif, notamment
         * après le retour sur un onglet masqué, saute un changement déjà publié.
         */
        var cursorAttribute = element.getAttribute('data-request-sync-cursor');
        var initialCursor = cursorAttribute !== null && cursorAttribute !== ''
            ? Number(cursorAttribute)
            : NaN;
        this.cursor = Number.isInteger(initialCursor) && initialCursor >= 0
            ? initialCursor
            : null;
        this.timer = null;
        this.controller = null;
        this.running = false;
        this.stopped = false;
        this.failureCount = 0;
        this.requestVersion = 0;
        this.fragmentRunning = false;
        this.needsFragmentRefresh = false;
    }

    /**
     * DÉMARRAGE CONTRÔLÉ : aucun appel n'est envoyé lorsqu'il manque le contexte
     * fonctionnel. Cette vérification rend le script sûr sur toutes les pages qui
     * chargent l'asset général mais ne présentent aucune liste de demandes.
     */
    RequestSyncInstance.prototype.start = function () {
        if (!this.context || this.stopped || document.hidden || !navigator.onLine) {
            return;
        }

        this.poll();
    };

    /**
     * ARRÊT PROPRE : le minuteur et la requête HTTP en cours sont annulés. Cette
     * méthode évite qu'une réponse tardive modifie une page qui vient d'être
     * masquée, restaurée ou remplacée par une navigation.
     */
    RequestSyncInstance.prototype.pause = function () {
        /*
         * INVALIDATION DES RÉPONSES TARDIVES : incrémenter la version avant
         * d'annuler fetch garantit qu'une ancienne promesse ne pourra ni déplacer
         * le curseur, ni effacer le contrôleur d'un nouvel appel déjà redémarré.
         */
        this.requestVersion += 1;

        if (this.timer !== null) {
            window.clearTimeout(this.timer);
            this.timer = null;
        }

        if (this.controller) {
            this.controller.abort();
            this.controller = null;
        }

        this.running = false;
    };

    /**
     * PLANIFICATION ADAPTATIVE : setTimeout est utilisé à la place de setInterval
     * pour garantir qu'un nouvel appel ne démarre jamais avant la fin du précédent.
     * Un léger décalage aléatoire répartit la charge lorsque plusieurs utilisateurs
     * ouvrent leurs listes au même moment sur un hébergement partagé.
     */
    RequestSyncInstance.prototype.schedule = function (delay) {
        var self = this;
        var safeDelay = Math.max(250, Number(delay) || DEFAULT_DELAY);
        var jitter = Math.floor(Math.random() * Math.min(750, safeDelay * 0.1));

        if (this.stopped || document.hidden || !navigator.onLine) {
            return;
        }

        if (this.timer !== null) {
            window.clearTimeout(this.timer);
        }

        this.timer = window.setTimeout(function () {
            self.poll();
        }, safeDelay + jitter);
    };

    /**
     * CONSTRUCTION DE L'URL : le premier appel utilise désormais le curseur que
     * Yii a capturé avant le rendu de la liste. Les appels suivants transmettent
     * le curseur retourné par l'API. Le repli sans curseur demeure uniquement pour
     * une ancienne vue qui ne fournirait pas encore cet attribut de synchronisation.
     */
    RequestSyncInstance.prototype.buildUrl = function () {
        var url = new URL(this.endpoint, window.location.origin);
        url.searchParams.set('context', this.context);

        if (Number.isInteger(this.cursor) && this.cursor >= 0) {
            url.searchParams.set('cursor', String(this.cursor));
        }

        return url.toString();
    };

    /**
     * LECTURE INCRÉMENTALE : credentials conserve la session Yii2 et no-store
     * interdit l'emploi d'une ancienne réponse. Une erreur d'authentification
     * transitoire ne doit pas arrêter définitivement la surveillance : la session
     * peut être restaurée par Yii ou renouvelée dans un autre onglet.
     */
    RequestSyncInstance.prototype.poll = function () {
        var self = this;
        var requestVersion;

        if (this.running || this.stopped || document.hidden || !navigator.onLine) {
            return;
        }

        this.running = true;
        requestVersion = ++this.requestVersion;
        this.controller = new AbortController();

        window.fetch(this.buildUrl(), {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: this.controller.signal
        }).then(function (response) {
            if (requestVersion !== self.requestVersion) {
                throw new Error('request-sync-stale-response');
            }

            if (response.status === 401 || response.status === 403) {
                throw new Error('request-sync-auth-' + response.status);
            }

            if (!response.ok) {
                throw new Error('request-sync-http-' + response.status);
            }

            return response.json();
        }).then(function (payload) {
            if (requestVersion !== self.requestVersion) {
                return;
            }

            self.processPayload(payload);
            self.failureCount = 0;

            /*
             * NOUVELLE TENTATIVE DU FRAGMENT : si le signal métier a bien été lu
             * mais que le rendu HTML précédent a échoué, le besoin reste mémorisé.
             * Chaque réponse saine réessaie donc l'affichage sans reculer le curseur
             * et sans perdre l'événement déjà consommé.
             */
            if (self.needsFragmentRefresh) {
                self.refreshFragment();
            }

            /*
             * RATTRAPAGE DES LOTS : hasMore signifie que la limite serveur a été
             * atteinte. Le lot suivant est demandé rapidement, tout en laissant au
             * navigateur un court répit pour rendre l'interface.
             */
            self.schedule(payload.hasMore ? 250 : payload.pollAfterMs);
        }).catch(function (error) {
            if (requestVersion !== self.requestVersion || error.name === 'AbortError' || self.stopped) {
                return;
            }

            /*
             * REPLI EN CAS D'ERREUR : l'attente double progressivement jusqu'à une
             * minute. Aucun message intrusif n'est affiché à l'utilisateur pour une
             * coupure momentanée ; la liste reste utilisable et le polling reprend
             * automatiquement dès que le serveur répond de nouveau.
             */
            self.failureCount += 1;

            // Diagnostic volontairement limité au contexte et au type d'erreur :
            // aucune donnée métier ni information de session n'est exposée.
            if (window.console && typeof window.console.warn === 'function') {
                window.console.warn('[CAN sync] Nouvelle tentative planifiée.', {
                    context: self.context,
                    error: error.message,
                    attempt: self.failureCount
                });
            }

            self.schedule(Math.min(DEFAULT_DELAY * Math.pow(2, self.failureCount), MAX_FAILURE_DELAY));
        }).finally(function () {
            /*
             * NETTOYAGE VERSIONNÉ : seule la requête encore courante peut libérer
             * l'état de l'instance. Une réponse obsolète ne perturbe donc jamais
             * une reprise déclenchée par visibilitychange ou online.
             */
            if (requestVersion === self.requestVersion) {
                self.running = false;
                self.controller = null;
            }
        });
    };

    /**
     * REMPLACEMENT CIBLÉ DU HTML : seule la zone marquée est téléchargée et
     * remplacée. La route courante contient déjà les paramètres de recherche et de
     * pagination, ce qui préserve exactement le contexte de travail de l'utilisateur.
     * En cas d'échec, l'ancien tableau reste visible et une prochaine boucle réessaie.
     */
    RequestSyncInstance.prototype.refreshFragment = function () {
        var self = this;

        if (!this.fragmentUrl || this.fragmentRunning || !this.needsFragmentRefresh) {
            return;
        }

        this.fragmentRunning = true;

        window.fetch(new URL(this.fragmentUrl, window.location.origin).toString(), {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
                /*
                 * CONTRAT DE FRAGMENT : cet en-tête distingue le rafraîchissement
                 * de liste des autres usages AJAX de la même action Yii2. Le
                 * contrôleur peut ainsi renvoyer seulement le tableau demandé.
                 */
                'X-CAN-Fragment': 'request-list'
            }
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('request-sync-fragment-http-' + response.status);
            }

            return response.text();
        }).then(function (html) {
            if (!html.trim()) {
                throw new Error('request-sync-empty-fragment');
            }

            self.element.innerHTML = html;
            self.needsFragmentRefresh = false;

            /*
             * RÉINITIALISATION APRÈS INJECTION : les infobulles Bootstrap ne sont
             * pas recréées automatiquement pour les nouveaux boutons. On initialise
             * seulement celles du fragment et l'on annonce le remplacement aux
             * autres scripts éventuels au moyen d'un événement dédié.
             */
            if (typeof window.bootstrap !== 'undefined' && window.bootstrap.Tooltip) {
                self.element.querySelectorAll('[data-bs-toggle="tooltip"], [data-toggle="tooltip"]').forEach(function (item) {
                    window.bootstrap.Tooltip.getOrCreateInstance(item);
                });
            }

            self.element.dispatchEvent(new CustomEvent('can:request-fragment-replaced', {
                detail: {context: self.context}
            }));
        }).catch(function () {
            self.needsFragmentRefresh = true;
        }).finally(function () {
            self.fragmentRunning = false;
        });
    };

    /**
     * VALIDATION DES ALERTES SERVEUR : même si le serveur produit déjà une structure
     * sûre, le navigateur contrôle les types, les icônes et l'origine de l'URL avant
     * toute présentation. Aucun HTML métier reçu n'est injecté dans SweetAlert.
     */
    function normalizeStatusAlerts(rawAlerts) {
        var allowedIcons = ['success', 'info', 'warning', 'error'];

        if (!Array.isArray(rawAlerts)) {
            return [];
        }

        return rawAlerts.map(function (item) {
            var eventId = Number(item && item.eventId);
            var title = item && typeof item.title === 'string' ? item.title.slice(0, 120) : '';
            var message = item && typeof item.message === 'string' ? item.message.slice(0, 240) : '';
            var icon = item && allowedIcons.indexOf(item.icon) !== -1 ? item.icon : 'info';
            var presentation = item && item.presentation === 'modal' ? 'modal' : 'toast';
            var actionUrl = '';

            if (item && typeof item.actionUrl === 'string' && item.actionUrl !== '') {
                try {
                    var parsedUrl = new URL(item.actionUrl, window.location.origin);
                    if (parsedUrl.origin === window.location.origin) {
                        actionUrl = parsedUrl.toString();
                    }
                } catch (error) {
                    actionUrl = '';
                }
            }

            if (!Number.isInteger(eventId) || eventId <= 0 || !title || !message) {
                return null;
            }

            return {
                eventId: eventId,
                title: title,
                message: message,
                icon: icon,
                presentation: presentation,
                actionUrl: actionUrl
            };
        }).filter(function (item) {
            return item !== null;
        });
    }

    /**
     * PRÉSENTATION NON INTRUSIVE : un changement ordinaire apparaît comme toast.
     * Une transition importante utilise une boîte centrale avec seulement « Close »
     * et « View Request », tous deux accompagnés d'une icône. Plusieurs changements
     * sont regroupés afin de ne jamais empiler les fenêtres sur l'utilisateur.
     */
    function displayStatusAlerts(rawAlerts) {
        var alerts = normalizeStatusAlerts(rawAlerts).filter(function (item) {
            if (shownAlertIds[item.eventId]) {
                return false;
            }

            shownAlertIds[item.eventId] = true;
            return true;
        });

        /*
         * MÉMOIRE BORNÉE : seuls les 200 derniers identifiants d'alerte sont gardés.
         * Cette limite évite qu'un onglet ouvert plusieurs jours accumule des clés.
         */
        var rememberedIds = Object.keys(shownAlertIds);
        if (rememberedIds.length > 200) {
            rememberedIds
                .map(Number)
                .sort(function (left, right) { return left - right; })
                .slice(0, rememberedIds.length - 200)
                .forEach(function (eventId) { delete shownAlertIds[eventId]; });
        }

        if (alerts.length === 0) {
            return;
        }

        /*
         * REPLI TECHNIQUE : SweetAlert est normalement chargé par AppAsset avant ce
         * module. Si le CDN est momentanément indisponible, un message natif informe
         * quand même l'utilisateur sans interrompre le rafraîchissement de la liste.
         */
        if (typeof window.Swal === 'undefined') {
            window.alert(alerts.length === 1
                ? alerts[0].title + '\n' + alerts[0].message
                : alerts.length + ' requests have been updated.');
            return;
        }

        if (alerts.length > 1) {
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: alerts.length + ' requests updated',
                text: 'The visible list has been refreshed with the latest statuses.',
                showConfirmButton: false,
                showCloseButton: true,
                timer: 7000,
                timerProgressBar: true,

                /*
                 * STYLE DU RÉSUMÉ MULTIPLE : les mêmes classes que les notifications
                 * unitaires assurent une présentation homogène, même lorsqu'aucun
                 * bouton de consultation n'est nécessaire dans le résumé global.
                 */
                customClass: {
                    popup: 'can-status-toast',
                    icon: 'can-status-toast-icon',
                    title: 'can-status-toast-title',
                    htmlContainer: 'can-status-toast-message',
                    actions: 'can-status-toast-actions',
                    confirmButton: 'can-status-toast-confirm',
                    closeButton: 'can-status-toast-close'
                }
            });
            return;
        }

        var alertData = alerts[0];
        var isModal = alertData.presentation === 'modal';
        var swalOptions = {
            toast: !isModal,
            position: isModal ? 'center' : 'top-end',
            icon: alertData.icon,
            title: alertData.title,
            text: alertData.message,
            showCloseButton: !isModal,
            showConfirmButton: alertData.actionUrl !== '',
            confirmButtonText: '<i class="bi bi-eye"></i> View Request',
            showCancelButton: isModal,
            cancelButtonText: '<i class="bi bi-x-circle"></i> Close',
            reverseButtons: true,
            timer: isModal ? undefined : 8000,
            timerProgressBar: !isModal,

            /*
             * STYLE RÉSERVÉ AUX STATUTS IMPORTANTS : les classes ne sont ajoutées
             * qu'à la fenêtre centrale. Les toasts conservent volontairement leurs
             * dimensions natives et leur comportement discret en haut de l'écran.
             */
            customClass: isModal ? {
                popup: 'can-status-swal',
                icon: 'can-status-swal-icon',
                title: 'can-status-swal-title',
                htmlContainer: 'can-status-swal-message',
                actions: 'can-status-swal-actions',
                confirmButton: 'can-status-swal-confirm',
                cancelButton: 'can-status-swal-close'
            } : {
                /*
                 * STYLE DU TOAST ORDINAIRE : les classes dédiées rapprochent son
                 * identité visuelle de la boîte centrale sans le rendre bloquant.
                 */
                popup: 'can-status-toast',
                icon: 'can-status-toast-icon',
                title: 'can-status-toast-title',
                htmlContainer: 'can-status-toast-message',
                actions: 'can-status-toast-actions',
                confirmButton: 'can-status-toast-confirm',
                closeButton: 'can-status-toast-close'
            }
        };

        window.Swal.fire(swalOptions).then(function (result) {
            /*
             * NAVIGATION EXPLICITE : seule une confirmation positive ouvre la route
             * signée validée plus haut. Fermer le toast ou la boîte ne change aucun
             * statut et laisse l'utilisateur sur sa liste actualisée.
             */
            if (result.isConfirmed && alertData.actionUrl) {
                window.location.assign(alertData.actionUrl);
            }
        });
    }

    /**
     * VALIDATION DE LA RÉPONSE : seules les propriétés attendues sont propagées.
     * Le curseur doit être un entier positif et les identifiants restent les codes
     * publics déjà encodés par PHP ; aucune donnée métier brute n'est reconstruite
     * ou acceptée depuis une source extérieure.
     */
    RequestSyncInstance.prototype.processPayload = function (payload) {
        if (!payload || !Number.isInteger(Number(payload.cursor)) || Number(payload.cursor) < 0) {
            throw new Error('request-sync-invalid-payload');
        }

        this.cursor = Number(payload.cursor);

        /*
         * INFORMATION UTILISATEUR : les alertes sont traitées indépendamment du
         * remplacement HTML. Ainsi un échec temporaire du fragment ne masque pas
         * un changement de statut important déjà autorisé par le serveur.
         */
        displayStatusAlerts(payload.alerts);

        if (payload.changed !== true) {
            return;
        }

        var detail = {
            context: this.context,
            cursor: this.cursor,
            requestIds: Array.isArray(payload.requestIds) ? payload.requestIds.slice() : [],
            alerts: normalizeStatusAlerts(payload.alerts),
            refreshMode: payload.refreshMode === 'container' ? 'container' : 'none'
        };

        /*
         * ÉVÉNEMENT LOCAL ET GLOBAL : le conteneur reçoit l'événement pour son
         * propre rafraîchissement. document reçoit une copie pour les compteurs ou
         * composants transversaux qui doivent réagir au même changement.
         */
        this.element.dispatchEvent(new CustomEvent('can:request-sync', {detail: detail}));
        document.dispatchEvent(new CustomEvent('can:request-sync', {detail: detail}));

        /*
         * ACTIVATION DU RENDU AUTOMATIQUE : une vue qui fournit fragmentUrl choisit
         * le remplacement HTML intégré. Une vue plus complexe peut omettre cette URL
         * et exploiter uniquement les événements ci-dessus avec sa propre stratégie.
         */
        if (this.fragmentUrl && detail.refreshMode === 'container') {
            this.needsFragmentRefresh = true;
            this.refreshFragment();
        }
    };

    /**
     * DÉCOUVERTE DES CONTENEURS : un attribut data suffit pour activer une liste.
     * L'instance est mémorisée sur l'élément afin qu'un script AJAX qui réanalyse
     * la page ne crée pas une deuxième surveillance du même composant.
     */
    function discover(root) {
        var scope = root || document;
        var elements = [];

        if (scope.nodeType === 1 && scope.matches('[data-request-sync-context]')) {
            elements.push(scope);
        }

        Array.prototype.push.apply(
            elements,
            scope.querySelectorAll ? scope.querySelectorAll('[data-request-sync-context]') : []
        );

        elements.forEach(function (element) {
            if (element.__canRequestSyncInstance) {
                return;
            }

            var instance = new RequestSyncInstance(element);
            element.__canRequestSyncInstance = instance;
            instances.push(instance);
            instance.start();
        });
    }

    /**
     * VISIBILITÉ ET RÉSEAU : aucune requête n'est envoyée dans un onglet masqué
     * ou hors ligne. Au retour, un contrôle immédiat rattrape les changements sans
     * attendre le prochain délai normal.
     */
    document.addEventListener('visibilitychange', function () {
        instances.forEach(function (instance) {
            if (document.hidden) {
                instance.pause();
            } else {
                instance.start();
            }
        });
    });

    window.addEventListener('offline', function () {
        instances.forEach(function (instance) {
            instance.pause();
        });
    });

    window.addEventListener('online', function () {
        instances.forEach(function (instance) {
            instance.start();
        });
    });

    /*
     * API PUBLIQUE : discover enregistre un fragment injecté, refresh force un
     * contrôle et diagnostics permet de vérifier le polling dans la console sans
     * exposer les données reçues du serveur.
     */
    window.CANRequestSync = {
        discover: discover,
        refresh: function () {
            instances.forEach(function (instance) {
                instance.pause();
                instance.start();
            });
        },
        diagnostics: function () {
            return instances.map(function (instance) {
                return {
                    context: instance.context,
                    cursor: instance.cursor,
                    running: instance.running,
                    stopped: instance.stopped,
                    failures: instance.failureCount,
                    nextPollScheduled: instance.timer !== null
                };
            });
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            discover(document);
        });
    } else {
        discover(document);
    }
})(window, document);
