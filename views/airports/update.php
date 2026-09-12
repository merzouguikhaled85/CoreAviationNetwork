<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

$this->title = 'Update Airport';

// Convert cities to a JavaScript-friendly format
$citiesByCountry = [];
foreach ($cities as $city) {
    $citiesByCountry[$city->country_id][] = ['id' => $city->city_id, 'name' => $city->city_name];
}

// JSON encode the cities array for use in JavaScript
$citiesByCountryJson = json_encode($citiesByCountry);
?>

<main class="dash-content can-form-page">
    <div class="container-fluid">
        <h1 class="dash-title">Update Airport</h1>
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($airport, 'airport_name')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="form-group col-lg-6">
                <?= $form->field($airport, 'icao')->textInput(['maxlength' => true]) ?>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-lg-6">
                <?= $form->field($airport, 'country_id')->dropDownList(
                    ArrayHelper::map($countries, 'country_id', 'country_name'),
                    [
                        'prompt' => 'Select Country',
                        'id' => 'country-select'
                    ]
                ) ?>
                <?= $form->field($airport, 'country_name')->hiddenInput()->label(false) ?>
            </div>
            <div class="form-group col-lg-6">
                <?= $form->field($airport, 'city_id')->dropDownList(
                    ArrayHelper::map([], 'id', 'name'), // Initially empty
                    [
                        'prompt' => 'Select City',
                        'id' => 'city-select'
                    ]
                ) ?>
                <?= $form->field($airport, 'city_name')->hiddenInput()->label(false) ?>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-lg-8">
                <div class="row">
                    <div class="col-lg-6">
                        <?= Html::submitButton('Update Airport', ['class' => 'btn btn-primary']) ?>
                    </div>
                    <div class="col-lg-2">
                        <?= Html::a('Back to airports', ['index'], ['class' => 'btn btn-primary']) ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php ActiveForm::end(); ?>
    </div>
</main>

<?php
// Register JavaScript to handle the city dropdown update and pre-select the current city
$script = <<<JS
    var citiesByCountry = $citiesByCountryJson;

    document.getElementById('country-select').addEventListener('change', function() {
        var countryId = this.value;
        var citySelect = document.getElementById('city-select');
        citySelect.innerHTML = '<option value="">Select City</option>'; // Reset city options

        if (citiesByCountry[countryId]) {
            citiesByCountry[countryId].forEach(function(city) {
                var option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                citySelect.appendChild(option);
            });
        }
    });

    // Pre-select the current city
    var countrySelect = document.getElementById('country-select');
    var citySelect = document.getElementById('city-select');
    var currentCountryId = countrySelect.value;
    var currentCityId = $airport->city_id; // Assuming $airport->city_id holds the current city ID

    if (currentCountryId && citiesByCountry[currentCountryId]) {
        citySelect.innerHTML = ''; // Clear city options
        citiesByCountry[currentCountryId].forEach(function(city) {
            var option = document.createElement('option');
            option.value = city.id;
            option.textContent = city.name;
            if (city.id == currentCityId) {
                option.selected = true; // Select the current city
            }
            citySelect.appendChild(option);
        });
    }

    // Update hidden fields for country name and city name
    document.getElementById('country-select').addEventListener('change', function() {
        var countrySelect = document.getElementById('country-select');
        var countryName = countrySelect.options[countrySelect.selectedIndex].text;
        document.getElementById('airport-country_name').value = countryName;
    });

    document.getElementById('city-select').addEventListener('change', function() {
        var citySelect = document.getElementById('city-select');
        var cityName = citySelect.options[citySelect.selectedIndex].text;
        document.getElementById('airport-city_name').value = cityName;
    });
JS;

$this->registerJs($script);
?>
