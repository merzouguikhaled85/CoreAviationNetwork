<p>Dear <?= $application->application->request->aO->username ?>,</p>

<p>We are pleased to inform you that the MRO has accepted the PO for the request with ID <?= $application->request_id ?>.</p>

<p>Please review the details below:</p>

<table>
    <tr>
        <td>MRO Username:</td>
        <td><?= $application->application->mro->username ?></td>
    </tr>
    <tr>
        <td>Request Details:</td>
        <td><?= $application->application->Description ?></td>
    </tr>
    <!-- Add more details here if needed -->
</table>

<p>Thank you for your cooperation.</p>

<p>Best regards,<br>
Your Company Name</p>
