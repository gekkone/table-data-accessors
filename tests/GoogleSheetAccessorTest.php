<?php

namespace Tests;

use Gekkone\TdaLib\Accessor\GoogleSheet;
use Gekkone\TdaLib\Accessor\GoogleSheet\TableOptions;
use OutOfBoundsException;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;
use Tests\Mock;

class GoogleSheetAccessorTest extends TestCase
{
    public static function makeValidRangeTestData()
    {
        $service = Mock\GoogleSheets::make(
            'test',
            [
                [
                    'id'     => 94,
                    'title'  => 'Test',
                    'values' => [['test', 'test', 'test']]
                ]
            ]
        );
        return [
            [new GoogleSheet(new TableOptions($service, 'test', null, 'A:C'))],
            [new GoogleSheet(new TableOptions($service, 'test', null, 'B:C'))],
            [new GoogleSheet(new TableOptions($service, 'test', null, 'A:A'))],
            [new GoogleSheet(new TableOptions($service, 'test', null, 'C:C'))],
        ];
    }

    public static function makeInvalidRangeTestData()
    {
        $service = Mock\GoogleSheets::make(
            'test',
            [
                [
                    'id'     => 94,
                    'title'  => 'Test',
                    'values' => [['test', 'test', 'test']]
                ]
            ]
        );
        return [
            [
                new GoogleSheet(new TableOptions($service, 'test', 94, 'A:D')),
                'Range A:D is out of ranges sheet 94 (A:C)'
            ],
            [
                new GoogleSheet(new TableOptions($service, 'test', 94, 'D:A')),
                'Range D:A is out of ranges sheet 94 (A:C)'
            ],
            [
                new GoogleSheet(new TableOptions($service, 'test', 94, 'D:C')),
                'Range D:C is out of ranges sheet 94 (A:C)'
            ],
        ];
    }

    public function testInvalidSheetId()
    {
        $service = Mock\GoogleSheets::make(
            'test',
            [
                [
                    'id'     => 94,
                    'title'  => 'Test',
                    'values' => [['test', 'test', 'test']]
                ]
            ]
        );

        // test invalid sheetId
        $options = new TableOptions($service, 'test', 93);
        $accessor = new GoogleSheet($options);

        $this->expectException(OutOfBoundsException::class);
        $accessor->rewind();
    }

    /**
     * @dataProvider makeInvalidRangeTestData
     */
    public function testInvalidRange(GoogleSheet $accessor, string $exceptionMessage)
    {
        $this->expectExceptionObject(new OutOfRangeException($exceptionMessage));
        $accessor->rewind();
    }

    /**
     * @dataProvider makeValidRangeTestData
     */
    public function testValidRange(GoogleSheet $accessor)
    {
        //unexpected exception
        self::assertEmpty($accessor->rewind());
    }
}
