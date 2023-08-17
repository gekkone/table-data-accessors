<?php

use Gekkone\TdaLib\Accessor\Csv;
use Gekkone\TdaLib\Accessor\GoogleSheet;
use Gekkone\TdaLib\TableIteratorInterface;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;

class CommonAccessorsTest extends TestCase
{
    public static function makeTestSuites()
    {
        $self = (new CommonAccessorsTest(''));

        $tableData = self::makeTestTableData()['raw'];
        $auditData = self::makeTestTableData()['audit'];
        return [
            'csv with semicolon separator'       => [
                self::makeCsvTestData($tableData, ';'),
                $auditData
            ],
            'csv with comma separator'           => [
                self::makeCsvTestData($tableData, ','),
                $auditData
            ],
            'csv with tab separator'             => [
                self::makeCsvTestData($tableData, "\t"),
                $auditData
            ],
            'google sheet first sheet'           => [
                $self->makeGoogleSheetTestData($tableData, '454fdg4tyr'),
                $auditData
            ],
            'google sheet with sheetIndex'       => [
                $self->makeGoogleSheetTestData($tableData, '454fdg4tyr', 43546),
                $auditData
            ],
            'google sheet with range'            => [
                $self->makeGoogleSheetTestData($tableData, '454fdg4tyr', null, 'A:C'),
                $auditData
            ],
            'google sheet with custom buff size' => [
                $self->makeGoogleSheetTestData($tableData, '454fdg4tyr', null, null, 4),
                $auditData
            ]
        ];
    }

    public static function makeTestTableData()
    {
        return [
            'raw'   => [
                ['Article', 'Uri', 'Description'],
                [],
                ['one', 'test, new value', 'test; append'],
                ['', '', '', 'test'],
                [
                    'русские символы ?&
            new line',
                    ' символы'
                ]
            ],
            'audit' => [
                'values'      => [
                    2 => ['Article' => null, 'Uri' => null, 'Description' => null],
                    3 => [
                        'Article'     => 'one',
                        'Uri'         => 'test, new value',
                        'Description' => 'test; append'
                    ],
                    4 => ['Article' => '', 'Uri' => '', 'Description' => '', 3 => 'test'],
                    5 => [
                        'Article'     => 'русские символы ?&
            new line',
                        'Uri'         => ' символы',
                        'Description' => null
                    ]
                ],
                'columnCount' => [
                    'withEmpty'    => 3,
                    'withoutEmpty' => 4
                ]
            ]
        ];
    }

    public static function makeCsvTestData(array $tableData, string $separator = ',')
    {
        $file = tmpfile();
        foreach ($tableData as $row) {
            fputcsv($file, $row, $separator);
        }

        return new Csv(new Csv\TableOptions(new Stream($file)));
    }

    /**
     * @dataProvider makeTestSuites
     */
    public function testFetchColumnCount(TableIteratorInterface $accessor, array $auditData)
    {
        $columnCount = $auditData['columnCount'];
        $accessor->rewind();

        $key = $accessor->key();
        $current = $accessor->current();

        //test calc rowsCount
        self::assertSame($columnCount['withoutEmpty'], $accessor->fetchRowCount());
        self::assertSame($columnCount['withEmpty'], $accessor->fetchRowCount(true));

        //test save iteration after fetchRowCount
        self::assertSame($key, $accessor->key());
        self::assertSame($current, $accessor->current());
        $accessor->next();
        self::assertSame($key + 1, $accessor->key());
    }

    /**
     * @dataProvider makeTestSuites
     */
    public function testReadAccessor(TableIteratorInterface $accessor, array $auditData)
    {
        $index = 0;
        $readData = $auditData['values'];

        foreach ($accessor as $row => $rowData) {
            $key = array_keys($readData)[$index];

            self::assertSame($key, $row);
            self::assertSame($key, $accessor->key());
            //special double get current data
            self::assertSame($readData[$key], $rowData);
            self::assertSame($readData[$key], $accessor->current());
            foreach ($readData[$key] as $alias => $value) {
                self::assertSame($value, $accessor->currentField($alias, null));
                if ($value === null) {
                    $testDefault = md5(random_bytes(4096));
                    self::assertSame($testDefault, $accessor->currentField($alias, $testDefault));
                }
            }
            ++$index;
        }

        self::assertSame($accessor->key(), array_key_last($readData));
    }

    /**
     * @dataProvider makeTestSuites
     */
    public function makeGoogleSheetTestData(
        array $tableData,
        string $spreadsheetId,
        ?int $sheetId = null,
        ?string $range = null,
        int $chunkSize = GoogleSheet\TableOptions::DEFAULT_CHUNK_SIZE
    ) {
        $sheets = [];
        if ($sheetId !== null) {
            do {
                $id = rand(100000, 999999);
            } while ($id == $sheetId);

            $sheets[] = [
                'id'     => $id,
                'title'  => "Sheet $id",
                'values' => [['fake data']]
            ];
        }

        $sheets[] = [
            'id'     => $sheetId ?: rand(100000, 999999),
            'title'  => "Sheet $sheetId",
            'values' => $tableData
        ];

        return new Gekkone\TdaLib\Accessor\GoogleSheet(
            new GoogleSheet\TableOptions(
                Tests\Mock\GoogleSheets::make($spreadsheetId, $sheets),
                $spreadsheetId,
                $sheetId,
                $range,
                $chunkSize
            )
        );
    }
}
