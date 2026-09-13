# Audit Trail — exploitation et deploiement

## Architecture

`AuditTrailBootstrap` observe globalement les evenements ActiveRecord puis delegue
l'enrichissement et l'ecriture a `AuditService`. Un seul `AuditRequestContext` est
cree par requete HTTP : toutes les traces de cette requete partagent donc le meme
identifiant cryptographiquement aleatoire.

`AuditLog` est volontairement denormalise et sans cle etrangere. Les informations
sur l'utilisateur, son role et sa societe sont des photographies historiques.

Les tables techniques suivantes ne sont pas auditees :

- `audit_log` pour eviter la recursion ;
- `migration` ;
- `request_change_event` ;
- `request_priority_history` ;
- `support_ticket_history`.

Les deux derniers historiques conservent leur responsabilite metier existante.

## Deploiement

Apres le deploiement du code sur GoDaddy :

```bash
cd "$HOME/public_html/can"
php yii migrate --interactive=0
php yii cache/flush-all --interactive=0
```

La migration `m260913_120000_create_audit_log_table` ne supprime et ne transforme
aucune table metier existante.

## Cloudflare et adresse IP reelle

La variable `CAN_CLOUDFLARE_TRUSTED_PROXIES` accepte toutes les plages CIDR IPv4
et IPv6 Cloudflare, separees par des virgules ou espaces. La meme liste peut etre
placee sous la cle `cloudflareTrustedProxies` de `config/env-local.php`.

Toujours recopier la liste publiee sur la page officielle Cloudflare « IP Ranges ».
Ne jamais configurer `0.0.0.0/0` ou `::/0`.

Quand la liste est vide, `CF-Connecting-IP` et `CF-IPCountry` sont retires par Yii
et `REMOTE_ADDR` est utilise. Un client direct ne peut donc pas falsifier son IP.

Sur GoDaddy, verifier une fois la valeur de `REMOTE_ADDR`. Si elle correspond a un
proxy GoDaddy au lieu d'une adresse Cloudflare, documenter et ajouter uniquement
le proxy intermediaire reel apres validation de sa chaine `X-Forwarded-For`.

## Geolocalisation

La version actuelle n'effectue aucun appel reseau. Elle utilise le code pays
Cloudflare uniquement apres validation du proxy et cherche son libelle dans la
table `countries`. La ville reste `NULL` si aucune source locale fiable n'existe.

Pour ajouter MaxMind GeoLite2 ulterieurement :

1. installer le lecteur PHP MaxMind avec Composer ;
2. stocker le fichier `.mmdb` hors du depot et hors du dossier Web ;
3. creer un provider implementant `AuditGeoIpProviderInterface` ;
4. remplacer le composant `auditGeoIp` dans la configuration ;
5. prevoir une mise a jour planifiee de la base GeoLite2.

Une indisponibilite GeoIP ne doit jamais bloquer une operation metier.

## Operation SQL volontairement speciale

`SupportTicketReply::updateAll()` reste utilise pour revendiquer atomiquement un
envoi d'e-mail et eviter un double envoi concurrent. Cette transition `processing`
est un verrou technique. La sauvegarde finale ActiveRecord audite le resultat
metier `pending/failed -> sent/failed`.

Les anciens `UPDATE ao_profiles` directs des controles administratifs passent
desormais par `updateAttributes()`. La validation metier reste contournee comme
auparavant, tandis que les evenements ActiveRecord deviennent auditables.

## Tests

Les tests transactionnels necessitent une base `yii2basic_test` distincte et
migree. Ne jamais pointer `config/test_db.php` vers la production.

```bash
php vendor/bin/codecept run unit --no-interaction
```

## Croissance et conservation

Aucune suppression automatique n'est activee. Surveiller mensuellement le nombre
de lignes, la taille de la table et la duree des requetes. Lorsque le volume le
justifiera, definir avec la gouvernance une duree de conservation, puis archiver
par periode vers un stockage immuable avant toute purge. Une purge ou un archivage
destructif exige une approbation separee.
