<?php

namespace Gekkone\TdaLib\Tests;

use Gekkone\TdaLib\Accessor\GoogleSheet\TableOptions;
use Google\Service\Sheets;
use Google;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class GoogleSpreadsheetTableOptionsTest extends TestCase
{
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

        //google client without mandatory scopes
        $this->expectException(InvalidArgumentException::class);
        new TableOptions(new Sheets(new Google\Client()), $spreadsheetId);

        //empty spreadsheet
        $this->expectException(InvalidArgumentException::class);
        new TableOptions($service, '');

        //invalid range
        $this->expectException(InvalidArgumentException::class);
        new TableOptions($service, ' ', null, 'A1:B1');
    }

    public function testSetGetRange()
    {
        $service = new Google\Service\Sheets(new Google\Client([
            'scopes' => [Sheets::SPREADSHEETS_READONLY, "test"]
        ]));
        $options = new TableOptions($service, ' ');

        $options->setRange('A:C');
        self::assertSame("A:C", $options->getRange());

        $options->setRange('AA:BCR');
        self::assertSame('AA:BCR', $options->getRange());

        $this->expectException(InvalidArgumentException::class);
        $options->setRange('a:c');

        $this->expectException(InvalidArgumentException::class);
        $options->setRange('A1:B5');
    }
}
