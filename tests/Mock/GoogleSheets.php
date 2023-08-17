<?php

namespace Gekkone\TdaLib\Tests\Mock;

use Google;
use Google\Service\Sheets;
use Google\Service\Sheets\Spreadsheet;
use Google\Service\Sheets\ValueRange;
use PHPUnit\Framework\MockObject\Generator\Generator as MockGenerator;
use PHPUnit\Event;
use InvalidArgumentException;
use OutOfBoundsException;

final class GoogleSheets
{
    protected string $spreadsheetId;
    protected array $sheets;

    public static function make(string $spreadsheetId, array $sheets): Sheets
    {
        $instance = new self($spreadsheetId, $sheets);
        $service = new Sheets(
            new Google\Client(['scopes' => Google\Service\Sheets::DRIVE])
        );

        $service->spreadsheets
            = self::createStub(Sheets\Resource\Spreadsheets::class);
        $service->spreadsheets_values
            = self::createStub(Sheets\Resource\SpreadsheetsValues::class);

        $service->spreadsheets
            ->method('get')->willReturnCallback([$instance, 'makeSpreadsheet']);
        $service->spreadsheets_values
            ->method('get')->willReturnCallback([$instance, 'makeSpreadsheetValues']);

        return $service;
    }

    public function makeSpreadsheet(): Spreadsheet
    {
        $data = [
            'spreadsheetId' => $this->spreadsheetId,
            'properties'    => [
                'title' => 'TestTable',
            ],
            'sheets'        => []
        ];

        for ($i = 0; $i < count($this->sheets); ++$i) {
            $sheet = $this->sheets[$i];
            $data['sheets'][] = [
                'properties' => [
                    'sheetId'        => $sheet['id'],
                    'title'          => $sheet['title'],
                    'index'          => $i,
                    'sheetType'      => 'GRID',
                    'gridProperties' => [
                        'rowCount'       => count($sheet['values']),
                        'columnCount'    => count($sheet['values'][0]),
                        'frozenRowCount' => 1
                    ]
                ]
            ];
        }

        return new Spreadsheet($data);
    }

    /**
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException
     */
    public function makeSpreadsheetValues(string $spreadsheetId, string $range): ValueRange
    {
        if ($spreadsheetId !== $this->spreadsheetId) {
            throw new InvalidArgumentException('invalid spreadsheetId');
        }

        $pattern = '/^(?:(\'[^\']*\')!?)([A-Z]+)(\d+):([A-Z]+)(\d+)$/u';

        if (preg_match($pattern, $range, $matches)) {
            $sheetTitle = isset($matches[1]) ? trim($matches[1], "'") : null;
            $startColumn = $matches[2];
            $startRow = intval($matches[3]);
            $endColumn = $matches[4];
            $endRow = intval($matches[5]);

            $startColumnIndex = $this->a1NotationToIndex($startColumn);
            $endColumnIndex = $this->a1NotationToIndex($endColumn);
        } else {
            throw new InvalidArgumentException('Invalid range');
        }

        $sheetValues = null;
        foreach ($this->sheets as $sheet) {
            if ($sheetTitle === null || $sheet['title'] == $sheetTitle) {
                $sheetValues = $sheet['values'];
                break;
            }
        }
        if ($sheetValues == null) {
            throw new OutOfBoundsException('invalid sheetTitle in range');
        } elseif ($startColumnIndex > count($sheetValues[0])
            || $endColumnIndex > count($sheetValues[0])) {
            throw new OutOfBoundsException('invalid range');
        } elseif ($startRow > count($sheetValues)
            || $endRow > count($sheetValues)) {
            throw new OutOfBoundsException('invalid range');
        }

        $values = array_slice($sheetValues, $startRow - 1, $endRow - $startRow + 1);

        return new ValueRange([
            'range'          => "$range",
            'majorDimension' => 'ROWS',
            'values'         => $values
        ]);
    }

    protected function a1NotationToIndex($notation): int
    {
        $notation = strtoupper($notation);
        $index = 0;

        $length = strlen($notation);
        for ($i = 0; $i < $length; $i++) {
            $char = $notation[$i];
            $index = $index * 26 + ord($char) - 64;
        }

        return $index;
    }

    protected static function createStub($originalClassName)
    {
        $stub = (new MockGenerator)->getMock(
            $originalClassName,
            [],
            [],
            '',
            false,
            false,
            true,
            false,
            false,
            null,
            false
        );

        Event\Facade::emitter()->testCreatedStub($originalClassName);
        return $stub;
    }

    protected function __construct(string $spreadsheetId, array $sheets)
    {
        $this->spreadsheetId = $spreadsheetId;
        $this->sheets = $sheets;
    }
}


