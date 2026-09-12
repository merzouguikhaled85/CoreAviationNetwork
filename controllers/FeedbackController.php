<?php

namespace app\controllers;

use app\components\UrlIdHelper;
use app\models\Feedback;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Contrôleur de compatibilité pour les anciens e-mails de feedback.
 *
 * Les nouveaux messages utilisent directement mro-applications/view-feedback
 * avec un request_id signé. Ce contrôleur ne rend aucune donnée : il transforme
 * uniquement un ancien feedback_id numérique en URL canonique, après contrôle
 * de l'utilisateur et de l'appartenance du feedback.
 */
class FeedbackController extends Controller
{
    /**
     * Limite strictement l'ancienne route aux MRO authentifiés. Cette protection
     * est appliquée avant toute requête métier et empêche l'utilisation publique
     * des identifiants numériques contenus dans les anciens e-mails.
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => static function (): bool {
                            return Yii::$app->session->get('user_type') === 'mro';
                        },
                    ],
                ],
            ],
        ];
    }

    /**
     * Convertit l'ancien lien /feedback/view?id=81 vers la page actuelle.
     * Seul un entier positif hérité est accepté ; tous les nouveaux liens sont
     * produits par UrlIdHelper et n'empruntent plus cette route de compatibilité.
     */
    public function actionView($id)
    {
        $feedbackId = filter_var($id, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($feedbackId === false) {
            throw new NotFoundHttpException('Invalid feedback link.');
        }

        $feedback = Feedback::findOne((int) $feedbackId);
        $connectedMroId = (int) Yii::$app->session->get('mro_id');

        /*
         * L'existence, l'association à une demande et la propriété du feedback
         * sont vérifiées ensemble. La même réponse 404 est utilisée afin de ne
         * révéler aucune information sur les feedbacks d'un autre MRO.
         */
        if (
            $feedback === null
            || (int) $feedback->request_id <= 0
            || $connectedMroId <= 0
            || (int) $feedback->mro_id !== $connectedMroId
        ) {
            throw new NotFoundHttpException('The requested feedback does not exist.');
        }

        return $this->redirect([
            '/mro-applications/view-feedback',
            'id' => UrlIdHelper::encode((int) $feedback->request_id),
        ]);
    }
}
