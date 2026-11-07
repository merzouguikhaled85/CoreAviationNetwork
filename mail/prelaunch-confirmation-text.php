<?php

/** @var \app\models\PrelaunchSubscriber $subscriber */
/** @var string $confirmationUrl */
/** @var string $unsubscribeUrl */
?>
Dear <?= $subscriber->first_name ?>,

Thank you for joining the Core Aviation Network early access list. Verify your business email address to receive the official launch notification.

This verification link expires after 24 hours. Copy this address into your browser:
<?= $confirmationUrl ?>

If you did not request this registration, ignore this message or remove the address:
<?= $unsubscribeUrl ?>

Core Aviation Network
