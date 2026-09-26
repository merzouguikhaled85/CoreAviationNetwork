<?php

namespace tests\unit\components;

use app\components\CsvCellSanitizer;

class CsvCellSanitizerTest extends \Codeception\Test\Unit
{
    /**
     * @dataProvider dangerousValueProvider
     */
    public function testDangerousSpreadsheetValuesAreNeutralized($value)
    {
        verify(CsvCellSanitizer::sanitize($value))->equals("'" . $value);
    }

    public function dangerousValueProvider()
    {
        return [
            ['=SUM(1,1)'],
            ['+cmd|calc'],
            ['-1+2'],
            ['@SUM(A1:A2)'],
            ["\t=HYPERLINK(\"https://example.invalid\")"],
            ['  +1'],
        ];
    }

    public function testOrdinaryValuesRemainUnchanged()
    {
        verify(CsvCellSanitizer::sanitize('MRO Aviation Ltd'))->equals('MRO Aviation Ltd');
        verify(CsvCellSanitizer::sanitize('contact@example.com'))->equals('contact@example.com');
        verify(CsvCellSanitizer::sanitize(null))->equals('');
    }

    public function testNullBytesAreRemoved()
    {
        verify(CsvCellSanitizer::sanitize("safe\0value"))->equals('safevalue');
    }
}
