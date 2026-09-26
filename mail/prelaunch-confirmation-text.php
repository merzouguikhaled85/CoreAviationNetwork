<?php

/** @var \app\models\PrelaunchSubscriber $subscriber */
/** @var string $confirmationUrl */
/** @var string $unsubscribeUrl */
?>
Dear <?= $subscriber->first_name ?>,

Thank you for joining the Core Aviation Network early access list.

Confirm your business email address within 24 hours:
<?= $confirmationUrl ?>

If you did not request this registration, ignore this message or remove the address:
<?= $unsubscribeUrl ?>

Core Aviation Network
