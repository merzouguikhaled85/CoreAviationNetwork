<?php

namespace app\models;

/**
 * Increments the authentication generation whenever a stored password changes.
 * Active sessions carrying the previous generation are then rejected globally.
 */
trait RevokesSessionsOnPasswordChange
{
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (!$insert && $this->isAttributeChanged('password')) {
            $this->auth_version = max(1, (int) $this->getOldAttribute('auth_version')) + 1;
        }

        return true;
    }
}
