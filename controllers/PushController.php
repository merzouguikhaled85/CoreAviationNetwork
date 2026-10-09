<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PushController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => ['class' => VerbFilter::class,
                'actions' => ['register' => ['POST'], 'unregister' => ['POST'], 'worker' => ['GET'], 'manifest' => ['GET']]],
            'access' => ['class' => AccessControl::class, 'except' => ['worker', 'manifest'], 'rules' => [[
                'allow' => true, 'roles' => ['@'], 'matchCallback' => static function () {
                    return preg_match('/^mro:[1-9][0-9]*$/D', (string) Yii::$app->user->id) === 1;
                },
            ]]],
        ];
    }

    public function actionRegister()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->validateDisplayedAccount();
        try {
            Yii::$app->aogPush->register(Yii::$app->request->post('token'));
        } catch (\yii\web\HttpException $error) {
            throw $error;
        } catch (\Throwable $error) {
            // Une exception SQL peut contenir le jeton : ne pas la propager au journal Web.
            throw new \yii\web\ServiceUnavailableHttpException('Could not register notifications. Please try again.');
        }
        return ['success' => true];
    }

    public function actionUnregister()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->validateDisplayedAccount();
        Yii::$app->aogPush->unregister();
        return ['success' => true];
    }

    private function validateDisplayedAccount()
    {
        // Un ancien onglet ne doit pas modifier le compte connecté dans un autre onglet.
        if (Yii::$app->request->post('account') !== (string) Yii::$app->user->id) {
            throw new \yii\web\ConflictHttpException('Your account changed. Reload this page and try again.');
        }
    }

    public function actionWorker()
    {
        if (!Yii::$app->aogPush->isAvailable()) {
            throw new NotFoundHttpException();
        }
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'application/javascript; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Service-Worker-Allowed', Yii::$app->request->baseUrl . '/');
        $config = Json::htmlEncode(Yii::$app->params['firebase']['web']);
        // Le SDK affiche les messages de notification en arrière-plan : pas de second affichage.
        return "importScripts('https://www.gstatic.com/firebasejs/13.0.0/firebase-app-compat.js');\n"
            . "importScripts('https://www.gstatic.com/firebasejs/13.0.0/firebase-messaging-compat.js');\n"
            . "firebase.initializeApp($config);\nfirebase.messaging();\n";
    }

    public function actionManifest()
    {
        if (empty(Yii::$app->params['firebase']['enabled'])) {
            throw new NotFoundHttpException();
        }
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/manifest+json; charset=UTF-8');
        Yii::$app->response->headers->set('Cache-Control', 'no-cache');
        $base = Yii::$app->request->baseUrl;
        return Json::encode([
            'id' => $base . '/', 'name' => 'Core Aviation Network', 'short_name' => 'CAN',
            'start_url' => $base . '/mro-requests', 'scope' => $base . '/', 'display' => 'standalone',
            'theme_color' => '#182432', 'background_color' => '#ffffff',
        ]);
    }
}
