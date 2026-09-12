<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;

$this->title = 'AO Notification Preferences';

// Register custom CSS styles for pagination links and search input
$this->registerCss("
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }
    
    .pagination li {
        margin: 0 5px;
    }
    
    .pagination .active a {
        background-color: #007bff;
        color: #fff;
    }
    
    .pagination li a {
        color: #007bff;
        border: 1px solid #007bff;
        padding: 6px 12px;
        text-decoration: none;
        border-radius: 3px;
    }
    
    .pagination li a:hover {
        background-color: #f2f2f2;
    }
    
    .search-container {
        display: flex;
        border-radius: 20px;
        overflow: hidden;
    }
    
    .search-input {
        flex: 1;
        border-top-left-radius: 20px;
        border-bottom-left-radius: 20px;
    }
    
    .search-button {
        border-top-right-radius: 20px;
        border-bottom-right-radius: 20px;
    }
    .search-input-lg {
        width: 100%; /* Set width to 100% to make it full width */
        height: 50px; /* Adjust height as needed */
        font-size: 18px; /* Increase font size for larger input */
    }
");
?>
<main class="dash-content">
    <div class="container-fluid">
        <h1 class="dash-title">AO Notification Preferences</h1>

        <?php
        // Check for flash messages
        if (Yii::$app->session->hasFlash('message')) {
            echo '<div class="alert alert-success">' . Yii::$app->session->getFlash('message') . '</div>';
        }
        if (Yii::$app->session->hasFlash('error')) {
            echo '<div class="alert alert-danger">' . Yii::$app->session->getFlash('error') . '</div>';
        }
        ?>

        <div class="row" style="margin-bottom: 20px;">
            <div class="col-lg-12">
                <?= Html::beginForm(['ao-notifications-preferences/index'], 'get', ['class' => 'form-inline']) ?>
                <?= Html::textInput('search', Yii::$app->request->get('search'), ['class' => 'form-control search-input search-input-lg', 'placeholder' => 'Search by Preference']) ?>
                <div class="input-group-append">
                    <?= Html::submitButton('Search', ['class' => 'btn btn-primary btn-lg search-button']) ?>
                </div>
                <?= Html::endForm() ?>
            </div>
            <div class="col-auto ml-auto" style="float: right; display: inline-block;">
                <br>
                <br>
                <br>
                <?= Html::a('Add Preference', ['create'], ['class' => 'btn btn-outline-primary btn-lg rounded-pill']) ?>
            </div>
        </div>

        <div class="row">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Notify by Email</th>
                        <th>Notify by Platform</th>
                        <th>MRO Replies</th>
                        <th>Appointment Requests</th>
                        <th>PO Acceptance</th>
                        <th>Maintenance Proposals</th>
                        <th>MRO Recommendations</th>
                        <th>Work Start</th>
                        <th>Report Submissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preferences as $preference): ?>
                        <tr>
                            <td><?= Html::encode($preference->id) ?></td>
                            <td><?= Html::encode($preference->notify_by_email ? 'true' : 'false') ?></td>
                            <td><?= Html::encode($preference->notify_by_platform ? 'true' : 'false') ?></td>
                            <td><?= Html::encode($preference->notify_mro_replies ? 'true' : 'false') ?></td>
                            <td><?= Html::encode($preference->notify_appointment_requests ? 'true' : 'false') ?></td>
                            <td><?= Html::encode($preference->notify_po_acceptance ? 'true' : 'false') ?></td>
                            <td><?= Html::encode($preference->notify_maintenance_proposals ? 'true' : 'false') ?></td>
                            <td><?= Html::encode($preference->notify_mro_recommendations) ?></td>
                            <td><?= Html::encode($preference->notify_work_start ? 'true' : 'false') ?></td>
                            <td><?= Html::encode($preference->notify_report_submissions ? 'true' : 'false') ?></td>
                            <td>
                                <?= Html::a('View', ['view', 'id' => $preference->id], ['class' => 'btn btn-info']) ?>
                                <?= Html::a('Update', ['update', 'id' => $preference->id], ['class' => 'btn btn-primary']) ?>
                                <?= Html::a('Delete', ['delete', 'id' => $preference->id], [
                                    'class' => 'btn btn-secondary',
                                    'data' => [
                                        'confirm' => 'Are you sure you want to delete this item?',
                                        'method' => 'post',
                                    ],
                                ]) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-container">
            <?= LinkPager::widget(['pagination' => $pagination]) ?>
        </div>
    </div>
</main>
