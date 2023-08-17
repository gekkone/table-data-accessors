<?php

namespace Gekkone\TdaLib\Tests;

use Gekkone\TdaLib\Accessor\GoogleSheet\TableOptions;
use Google;
use Google\Service\Sheets;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

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
            'lower case range'       => ['a:c'],
            'range with row indexes' => ['A1:B5']
        ];
    }

    public static function makeSetChunkSizeTestData()
    {
        return [
            [0, true],
            [-1, true],
            [1, false],
            [999, false]
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
        self::assertSame(TableOptions::DEFAULT_CHUNK_SIZE, $options->getChunkSize());

        self::assertEquals($options, TableOptions::new($service, $spreadsheetId));


        $options = new TableOptions($service, $spreadsheetId, 2345, 'A:V', 24);
        self::assertSame(2345, $options->getSheetId());
        self::assertSame('A:V', $options->getRange());
        self::assertSame(24, $options->getChunkSize());
        self::assertEquals($options, TableOptions::new($service, $spreadsheetId, 2345, 'A:V', 24));
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

    /**
     * @dataProvider makeSetChunkSizeTestData
     */
    public function testSetChunkSize($chunkSize, bool $expectException)
    {
        $options = new TableOptions(
            new Google\Service\Sheets(
                new Google\Client([
                    'scopes' => [Sheets::SPREADSHEETS_READONLY, "test"]
                ])
            ),
            ' '
        );

        if ($expectException) {
            $this->expectException(InvalidArgumentException::class);
        }
        $options->setChunkSize($chunkSize);
        if (!$expectException) {
            self::assertSame($chunkSize, $options->getChunkSize());
        }
    }

    /**
     * @dataProvider makeSetChunkSizeTestData
     */
    public function testSetChunkSizeInConstructor($chunkSize, bool $expectException)
    {
        if ($expectException) {
            $this->expectException(InvalidArgumentException::class);
        }
        $options = new TableOptions(
            new Google\Service\Sheets(
                new Google\Client([
                    'scopes' => [Sheets::SPREADSHEETS_READONLY, "test"]
                ])
            ),
            ' ',
            null,
            null,
            $chunkSize
        );
        if (!$expectException) {
            self::assertSame($chunkSize, $options->getChunkSize());
        }
    }

    /**
     * @dataProvider makeSetChunkSizeTestData
     */
    public function testSetChunkInStaticConstruct($chunkSize, bool $expectException)
    {
        if ($expectException) {
            $this->expectException(InvalidArgumentException::class);
        }
        $options = TableOptions::new(
            new Google\Service\Sheets(
                new Google\Client([
                    'scopes' => [Sheets::SPREADSHEETS_READONLY, "test"]
                ])
            ),
            ' ',
            null,
            null,
            $chunkSize
        );
        if (!$expectException) {
            self::assertSame($chunkSize, $options->getChunkSize());
        }
    }
}
