<?php

namespace app\components;

/**
 * Neutralizes values that spreadsheet applications could interpret as formulas.
 */
final class CsvCellSanitizer
{
    public static function sanitize($value)
    {
        $value = str_replace("\0", '', (string) ($value ?? ''));

        if (preg_match('/^[\s]*[=+\-@]/u', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}
