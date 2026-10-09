<?php

namespace tests\unit\controllers;

use app\controllers\AirportsController;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\db\Connection;
use yii\web\Application;
use yii\web\BadRequestHttpException;

// Permettre aussi l'exécution isolée avec PHPUnit, sans configuration de production.
if (Yii::getAlias('@app', false) === false) {
    Yii::setAlias('@app', dirname(__DIR__, 3));
}

class AirportCreationTest extends TestCase
{
    private $previousApplication;
    private $controller;
    private $db;
    private $previousSession;

    protected function setUp(): void
    {
        $this->previousApplication = Yii::$app;
        $this->previousSession = $_SESSION ?? null;
        $_SESSION = [];
        new Application([
            'id' => 'airport-creation-tests',
            'basePath' => dirname(__DIR__, 3),
            'components' => [
                'db' => ['class' => AirportQueryConnection::class, 'dsn' => 'sqlite::memory:'],
                'request' => [
                    'cookieValidationKey' => 'test',
                    'scriptUrl' => '/index.php',
                    'scriptFile' => dirname(__DIR__, 3) . '/web/index.php',
                    'baseUrl' => '',
                ],
                'session' => ['class' => AirportTestSession::class],
            ],
        ]);
        Yii::$app->request->setBodyParams([]);
        $this->db = Yii::$app->db;
        $this->db->createCommand('CREATE TABLE countries (country_id INTEGER PRIMARY KEY, country_name TEXT)')->execute();
        $this->db->createCommand('CREATE TABLE cities (city_id INTEGER PRIMARY KEY, country_id INTEGER, city_name TEXT)')->execute();
        $this->db->createCommand('CREATE TABLE airports (airport_id INTEGER PRIMARY KEY, airport_name TEXT, icao TEXT, city_id INTEGER, country_id INTEGER, city_name TEXT, country_name TEXT)')->execute();
        $this->db->createCommand()->batchInsert('countries', ['country_id', 'country_name'], [[1, 'Country A'], [2, 'Country B']])->execute();
        $cities = [];
        for ($id = 1; $id <= 60; $id++) {
            $cities[] = [$id, 1, sprintf('City %02d', $id)];
        }
        $cities[] = [61, 2, 'Other city'];
        $this->db->createCommand()->batchInsert('cities', ['city_id', 'country_id', 'city_name'], $cities)->execute();
        $this->controller = new AirportFormController('airports', Yii::$app);
        Yii::$app->controller = $this->controller;
        $this->db->queries = [];
    }

    protected function tearDown(): void
    {
        if (Yii::$app->session->isActive) {
            Yii::$app->session->close();
        }
        $this->db->close();
        Yii::$app = $this->previousApplication;
        if ($this->previousSession === null) {
            unset($_SESSION);
        } else {
            $_SESSION = $this->previousSession;
        }
        if ($this->previousApplication) {
            Yii::setAlias('@app', $this->previousApplication->basePath);
        }
    }

    public function testInitialFormDoesNotReadCities(): void
    {
        $this->assertSame('form', $this->controller->actionCreate());
        $this->assertSame([], $this->controller->parameters['cityOptions']);
        foreach ($this->db->queries as $sql) {
            $this->assertDoesNotMatchRegularExpression('/\bFROM\s+["`\[]?cities\b/i', $sql);
        }
    }

    public function testCitySearchIsPaginatedAndRestrictedToCountry(): void
    {
        $first = $this->controller->actionGetCities(1);
        $second = $this->controller->actionGetCities(1, '', 2);
        $this->assertCount(50, $first['cities']);
        $this->assertTrue($first['pagination']['more']);
        $this->assertCount(10, $second['cities']);
        $this->assertFalse($second['pagination']['more']);
        $this->assertSame('City 51', $second['cities'][0]['name']);
        $this->assertSame('Other city', $this->controller->actionGetCities(2)['cities'][0]['name']);
    }

    public function testCitySearchFiltersByName(): void
    {
        $result = $this->controller->actionGetCities(1, 'City 05');
        $this->assertCount(1, $result['cities']);
        $this->assertSame('City 05', $result['cities'][0]['name']);
    }

    public function testInvalidCitySearchIsRejected(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->controller->actionGetCities(['unexpected']);
    }

    public function testFailedValidationRetainsOnlySelectedCity(): void
    {
        Yii::$app->request->setBodyParams(['Airports' => ['country_id' => 1, 'city_id' => 5]]);
        $this->controller->actionCreate();
        $this->assertSame([5 => 'City 05'], $this->controller->parameters['cityOptions']);
        $this->assertTrue($this->controller->parameters['airport']->hasErrors('airport_name'));
    }

    public function testCityFromAnotherCountryCannotBeSaved(): void
    {
        Yii::$app->request->setBodyParams(['Airports' => ['airport_name' => 'Airport', 'country_id' => 1, 'city_id' => 61]]);
        $this->controller->actionCreate();
        $this->assertTrue($this->controller->parameters['airport']->hasErrors('city_id'));
        $this->assertSame('0', (string) $this->db->createCommand('SELECT COUNT(*) FROM airports')->queryScalar());
    }

    public function testMissingSelectionsReturnValidationErrors(): void
    {
        Yii::$app->request->setBodyParams(['Airports' => ['airport_name' => 'Airport']]);
        $this->controller->actionCreate();
        $this->assertTrue($this->controller->parameters['airport']->hasErrors('country_id'));
        $this->assertTrue($this->controller->parameters['airport']->hasErrors('city_id'));
    }

    public function testValidSubmissionUsesNamesFromDatabase(): void
    {
        Yii::$app->request->setBodyParams(['Airports' => [
            'airport_name' => 'Airport', 'country_id' => 1, 'city_id' => 5,
            'country_name' => 'Forged country', 'city_name' => 'Forged city',
        ]]);
        $this->controller->actionCreate();
        $row = $this->db->createCommand('SELECT * FROM airports')->queryOne();
        $this->assertSame('Country A', $row['country_name']);
        $this->assertSame('City 05', $row['city_name']);
    }
}

class AirportFormController extends AirportsController
{
    public $parameters;

    public function render($view, $params = [])
    {
        $this->parameters = $params;
        return 'form';
    }

    public function redirect($url, $statusCode = 302)
    {
        return 'redirect';
    }
}

class AirportQueryConnection extends Connection
{
    public $queries = [];

    public function createCommand($sql = null, $params = [])
    {
        if ($sql !== null) {
            $this->queries[] = $sql;
        }
        return parent::createCommand($sql, $params);
    }
}

// Éviter les cookies et les en-têtes HTTP pendant les tests en ligne de commande.
class AirportTestSession extends \yii\web\Session
{
    public function open()
    {
    }

    public function close()
    {
    }

    public function getIsActive()
    {
        return true;
    }
}
