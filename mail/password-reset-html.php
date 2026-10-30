<?php
/** @var string $appName HTML-escaped application name */
/** @var string $safeName HTML-escaped recipient name */
/** @var string $safeLink HTML-escaped reset URL */
?>
    <div style='margin:0; padding:0; background:#f4f6f8;'>
      <div style='max-width:640px; margin:0 auto; padding:24px; font-family:Arial, Helvetica, sans-serif; color:#1f2937;'>
        <div style='background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;'>

          <!-- En-tête -->
          <div style='padding:18px 22px; background:#0b5ed7; color:#ffffff;'>
            <div style='font-size:16px; font-weight:700; letter-spacing:.2px;'>
              <?= $appName ?>
            </div>
            <div style='font-size:13px; opacity:.9; margin-top:4px;'>
              Password reset
            </div>
          </div>

          <!-- Corps du mail -->
          <div style='padding:22px;'>
            <h2 style='margin:0 0 12px; font-size:20px; line-height:1.3; color:#111827;'>
              Reset your password
            </h2>

            <p style='margin:0 0 12px; font-size:14px; line-height:1.7;'>
              Hello <strong><?= $safeName ?></strong>,
            </p>

            <p style='margin:0 0 12px; font-size:14px; line-height:1.7;'>
              We received a request to reset the password for your <strong><?= $appName ?></strong> account.
            </p>

            <p style='margin:0 0 16px; font-size:14px; line-height:1.7;'>
              Click the button below to set a new password:
            </p>

            <!-- Bouton d'action -->
            <div style='margin:18px 0 18px;'>
              <a href='<?= $safeLink ?>'
                 style='display:inline-block; background:#0b5ed7; color:#ffffff; text-decoration:none; padding:12px 18px; border-radius:8px; font-size:14px; font-weight:700;'>
                Reset Password
              </a>
            </div>

            <p style='margin:0 0 10px; font-size:13px; line-height:1.7; color:#374151;'>
              If the button doesn't work, copy and paste this link into your browser:
            </p>

            <p style='margin:0 0 16px; font-size:13px; line-height:1.7; word-break:break-all;'>
              <a href='<?= $safeLink ?>' style='color:#0b5ed7; text-decoration:underline;'>
                <?= $safeLink ?>
              </a>
            </p>

            <div style='padding:12px 14px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px;'>
              <p style='margin:0; font-size:13px; line-height:1.7; color:#374151;'>
                For your security, this link will expire after <strong>60 minutes</strong>.
              </p>
              <p style='margin:6px 0 0; font-size:13px; line-height:1.7; color:#374151;'>
                If you didn't request a password reset, you can safely ignore this email — your password will not be changed.
              </p>
            </div>

            <p style='margin:18px 0 0; font-size:14px; line-height:1.7;'>
              Best regards,<br>
              <strong><?= $appName ?> Team</strong>
            </p>
          </div>

          <!-- Pied de page -->
          <div style='padding:14px 22px; background:#ffffff; border-top:1px solid #e5e7eb;'>
            <p style='margin:0; font-size:12px; line-height:1.6; color:#6b7280;'>
              This is an automated message. Please do not reply.
            </p>
          </div>

        </div>
      </div>
    </div>
    
