<?php

namespace Gekkone\TdaLib\Tests;

use Gekkone\TdaLib\Accessor\GoogleSheet\TableOptions;
use Google\Service\Sheets;
use Google;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class GoogleSpreadsheetTableOptionsTest extends TestCase
{
    public static function makeFailedConstructTestData()
    {
        $service = new Sheets(
            new Google\Client([
                'scopes' => [Sheets::SPREADSHEETS_READONLY, 'test']
            ])
        );

        return [
            'without required scopes'   => [
                new Sheets(new Google\Client()),
                md5(random_bytes(4096))
            ],
            'with empty spreadsheetId'  => [$service, ''],
            'with in lowercase range'   => [$service, ' ', 33, 'a:c'],
            'with range with row index' => [$service, ' ', 33, 'A1:C1'],
        ];
    }

    public static function makeFailedSetRangeTestData()
    {
        return [
            'lower case range' => ['a:c'],
            'range with row indexes' => ['A1:B5']
        ];
    }

    public function testConstructor()
    {
        $service = new Google\Service\Sheets(
            new Google\Client([
                'scopes' => [Sheets::SPREADSHEETS_READONLY, "test"]
            ])
        );
        $spreadsheetId = md5(random_bytes(4096));

        $options = new TableOptions($service, $spreadsheetId);
        self::assertSame($service, $options->getService());
        self::assertSame($spreadsheetId, $options->getSpreadsheetId());
        self::assertNull($options->getRange());
        self::assertNull($options->getSheetId());

        $options = new TableOptions($service, $spreadsheetId, 2345, 'A:V');
        self::assertSame(2345, $options->getSheetId());
        self::assertSame('A:V', $options->getRange());
    }

    /**
     * @dataProvider makeFailedConstructTestData
     */
    public function testFailedConstruct()
    {
        $this->expectException(InvalidArgumentException::class);
        new TableOptions(...func_get_args());
    }

    public function testSetGetRange()
    {
        $service = new Google\Service\Sheets(
            new Google\Client([
                'scopes' => [Sheets::SPREADSHEETS_READONLY, "test"]
            ])
        );
        $options = new TableOptions($service, ' ');

        $options->setRange('A:C');
        self::assertSame("A:C", $options->getRange());

        $options->setRange('AA:BCR');
        self::assertSame('AA:BCR', $options->getRange());
    }

    /**
     * @dataProvider makeFailedSetRangeTestData
     */
    public function testFailedSetRange(string $range)
    {
        $options = new TableOptions(
            new Google\Service\Sheets(
                new Google\Client([
                    'scopes' => [Sheets::SPREADSHEETS_READONLY, "test"]
                ])
            ),
            ' '
        );

        $this->expectException(InvalidArgumentException::class);
        $options->setRange($range);
    }
}
