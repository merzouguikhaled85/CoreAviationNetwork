<?php

namespace app\components;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Yii;

/**
 * Mailer Yii résilient aux indisponibilités temporaires du serveur SMTP.
 *
 * Les notifications électroniques sont utiles, mais elles ne doivent jamais
 * annuler une opération métier déjà validée (PO, rendez-vous, CRS ou feedback).
 * Cette classe conserve Symfony Mailer et centralise seulement le délai réseau
 * ainsi que la gestion des erreurs de transport.
 */
class ResilientMailer extends \yii\symfonymailer\Mailer
{
    /**
     * Durée maximale, en secondes, d'une attente réseau SMTP.
     * Quatre secondes laissent au serveur SMTP le temps de répondre tout en
     * protégeant les formulaires contre une connexion réseau bloquée. En cas
     * d'indisponibilité, l'opération métier reste enregistrée et l'échec est logué.
     */
    public float $smtpTimeout = 4.0;

    /** @var SmtpTransport|null Transport SMTP conservé pour configurer son flux. */
    private ?SmtpTransport $smtpTransport = null;

    /**
     * Construit explicitement le transport déclaré par DSN afin de pouvoir régler
     * son flux SocketStream. Les autres formes acceptées par Yii sont transmises
     * sans modification au composant parent.
     *
     * @param array|\Symfony\Component\Mailer\Transport\TransportInterface $transport
     */
    public function setTransport($transport): void
    {
        if (is_array($transport) && isset($transport['dsn'])) {
            $transport = Transport::fromDsn((string) $transport['dsn']);
        }

        if ($transport instanceof SmtpTransport) {
            $this->smtpTransport = $transport;
        }

        parent::setTransport($transport);
    }

    /**
     * Applique le délai après l'injection complète de la configuration Yii.
     * default_socket_timeout sert de repli sur les hébergements qui remplaceraient
     * ultérieurement le transport par une configuration SMTP différente.
     */
    public function init(): void
    {
        parent::init();

        $timeout = max(1.0, min(15.0, $this->smtpTimeout));
        @ini_set('default_socket_timeout', (string) $timeout);

        if ($this->smtpTransport !== null) {
            $stream = $this->smtpTransport->getStream();

            if ($stream instanceof SocketStream) {
                $stream->setTimeout($timeout);
            }
        }
    }

    /**
     * Une panne SMTP est enregistrée côté serveur puis retournée comme un échec
     * d'envoi normal. Elle ne remonte plus jusqu'au contrôleur et ne remplace donc
     * plus la page utilisateur par une erreur « Maximum execution time ».
     *
     * @param mixed $message Message construit par yii\symfonymailer.
     */
    protected function sendMessage($message): bool
    {
        try {
            return parent::sendMessage($message);
        } catch (TransportExceptionInterface $exception) {
            // Une seule tentative par requête : les délais SMTP se cumulent.
            $this->resetSmtpConnection();

            $this->logSmtpFailure($exception, 1);

            return false;
        }
    }

    /**
     * Ferme complètement le socket ayant échoué après la tentative. Symfony ne
     * marque le transport comme démarré qu'après le dialogue initial ; terminate()
     * est donc nécessaire lorsqu'un timeout survient avant cet instant.
     */
    private function resetSmtpConnection(): void
    {
        if ($this->smtpTransport === null) {
            return;
        }

        try {
            $this->smtpTransport->stop();
        } catch (TransportExceptionInterface) {
            // La fermeture peut échouer sur une socket déjà interrompue ; on la libère ci-dessous.
        }

        $this->smtpTransport->getStream()->terminate();
    }

    /**
     * Journalise l'échec final sans enregistrer le destinataire, le sujet, le corps
     * du message ou le dialogue SMTP susceptible de contenir des informations.
     */
    private function logSmtpFailure(TransportExceptionInterface $exception, int $attempt): void
    {
            /*
             * JOURNALISATION SANS DONNÉES MÉTIER : seules la classe et la raison
             * technique sont conservées ; ni destinataire ni contenu ne sont logués.
             */
            Yii::warning([
                'message' => 'Envoi SMTP indisponible ; l’opération métier est conservée.',
                'attempt' => $attempt,
                'exception' => get_class($exception),
                'reason' => $exception->getMessage(),
            ], 'mail.smtp');
    }
}
