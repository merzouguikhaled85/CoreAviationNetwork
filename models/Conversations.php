<?php

namespace app\models;
use app\models\MroProfile;
use app\models\AoProfile;
use Yii;

class Conversations extends \yii\db\ActiveRecord
{
    /* =====================================================================
     * PIÈCES JOINTES (2026-11) — voir migration m261121_170000
     * =====================================================================
     * Le fichier est stocké hors du web (@runtime/chat-attachments) et servi
     * par ConversationsController::actionAttachment après contrôle d'accès.
     */

    /** Scénario utilisé par ConversationsController::actionReply (envoi d'un message). */
    public const SCENARIO_REPLY = 'reply';

    /** Types acceptés : documents et photos (cartes de travail, photos de dommages…). */
    public const ATTACHMENT_EXTENSIONS = ['pdf', 'png', 'jpg', 'jpeg'];
    public const ATTACHMENT_MIME_TYPES = ['application/pdf', 'image/png', 'image/jpeg'];

    /** 10 Mo, comme les autres documents ; doit rester < post_max_size (12 Mo). */
    public const ATTACHMENT_MAX_BYTES = 10 * 1024 * 1024;

    /** Dossier de stockage (non public). */
    public const ATTACHMENT_DIRECTORY = '@runtime/chat-attachments';

    /** @var \yii\web\UploadedFile|null fichier envoyé avec le formulaire (non stocké en base) */
    public $attachmentFile;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'conversations';
    }

    /**
     * SYNCHRONISATION DES MESSAGES : un nouveau message peut modifier la date,
     * l'aperçu et l'ordre de la liste des conversations. Aucun texte du message
     * n'est copie dans le journal technique afin de proteger sa confidentialite.
     */
    public function behaviors()
    {
        return [
            'requestChange' => [
                'class' => \app\components\RequestChangeBehavior::class,
                'requestIdAttribute' => 'request_id',
                'eventPrefix' => 'conversation',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sender_id', 'sender_type', 'receiver_id', 'receiver_type'], 'required'],
            [['sender_id', 'receiver_id', 'request_id'], 'integer'],
            [['sender_type', 'receiver_type'], 'string', 'max' => 255],
            [['message'], 'string'],
            [['message'], 'safe'],
            [['timestamp'], 'safe'],

            /*
             * ENVOI D'UN MESSAGE (scénario « reply » uniquement) :
             *  - le fichier joint est vérifié (extension ET type MIME réel, 10 Mo max) ;
             *  - il faut un texte OU un fichier.
             * Les autres créations de conversation (contact, recommandation) ne
             * sont pas concernées et gardent leur comportement d'origine.
             */
            [['attachmentFile'], 'file',
                'skipOnEmpty' => true,
                'extensions' => self::ATTACHMENT_EXTENSIONS,
                'mimeTypes' => self::ATTACHMENT_MIME_TYPES,
                'checkExtensionByMimeType' => true,
                'maxSize' => self::ATTACHMENT_MAX_BYTES,
                'maxFiles' => 1,
                'tooBig' => 'The attachment is too large. Maximum size: 10 MB.',
                'wrongExtension' => 'Only PDF, PNG and JPG files can be attached.',
                'wrongMimeType' => 'Only PDF, PNG and JPG files can be attached.',
                'on' => self::SCENARIO_REPLY,
            ],
            [['message'], 'validateMessageOrAttachment', 'skipOnEmpty' => false, 'on' => self::SCENARIO_REPLY],
        ];
    }

    /**
     * Refuse un message vide sans pièce jointe.
     */
    public function validateMessageOrAttachment($attribute)
    {
        if (trim((string) $this->message) === '' && $this->attachmentFile === null) {
            $this->addError($attribute, 'Write a message or attach a file.');
        }
    }

    /**
     * Vrai si ce message porte une pièce jointe.
     * hasAttribute() protège les écrans tant que la migration n'a pas été exécutée.
     */
    public function hasAttachment(): bool
    {
        return $this->hasAttribute('attachment_path') && !empty($this->attachment_path);
    }

    /** Vrai si la pièce jointe est une image (affichée en vignette). */
    public function isImageAttachment(): bool
    {
        return $this->hasAttachment() && strpos((string) $this->attachment_mime, 'image/') === 0;
    }

    /** Chemin absolu du fichier stocké (basename() empêche toute sortie du dossier). */
    public function getAttachmentAbsolutePath(): ?string
    {
        if (!$this->hasAttachment()) {
            return null;
        }

        return Yii::getAlias(self::ATTACHMENT_DIRECTORY) . DIRECTORY_SEPARATOR . basename((string) $this->attachment_path);
    }

    /** Taille lisible (ex. « 2.4 MB », « 380 KB »). */
    public function getAttachmentSizeLabel(): string
    {
        $bytes = $this->hasAttribute('attachment_size') ? (int) $this->attachment_size : 0;
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }

        return max(1, (int) round($bytes / 1024)) . ' KB';
    }

    /**
     * Define a relation with MroProfile.
     */
    public function getSender()
    {
        if ($this->sender_type === 'mro') {
            return $this->hasOne(MroProfile::class, ['mro_id' => 'sender_id']);
        } elseif ($this->sender_type === 'ao') {
            return $this->hasOne(AoProfile::class, ['ao_id' => 'sender_id']);
        } else {
            // Handle the case where sender_type is neither 'mro' nor 'ao'
            // You can return a default value or handle the situation according to your application logic
            return null;
        }
    }

    /**
     * Define a relation with AoProfile.
     */
    public function getReceiver()
    {
        if ($this->receiver_type === 'mro') {
            return $this->hasOne(MroProfile::class, ['mro_id' => 'receiver_id']);
        } elseif ($this->receiver_type === 'ao') {
            return $this->hasOne(AoProfile::class, ['ao_id' => 'receiver_id']);
        } else {
            // Handle the case where receiver_type is neither 'mro' nor 'ao'
            // You can return a default value or handle the situation according to your application logic
            return null;
        }
    }

    /**
     * Define a relation with Requests.
     */
    public function getRequest()
    {
        return $this->hasOne(Requests::class, ['request_id' => 'request_id']);
    }
}
