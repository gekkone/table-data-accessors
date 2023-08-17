<?php

namespace Gekkone\TdaLib\Tests;

use Gekkone\TdaLib\Accessor\Csv;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Stream;

class CsvAccessorTest extends TestCase
{
    public static function makeConvertEncodingTestData()
    {
        return [
            [
                new Stream(fopen(__DIR__ . '/assets/csv-encoded/windows1251.csv', 'r')),
                [['Test' => 'test', 'Заголовок' => 'тестовые данные']]
            ],
            [
                new Stream(fopen(__DIR__ . '/assets/csv-encoded/utf8-bom.csv', 'r')),
                [['Test' => 'test', 'Заголовок' => 'тестовые данные']]
            ]
        ];
    }

    public static function makeDefineSeparatorTestData()
    {
        return [
            ["data;Заголовок,проверка;\n" . '"test;";2', ['test;', '2']],
            ['"data";"Заголовок","проверка";' ."\n" . '"""test;""";2', ['"test;"', '2']],
            ['"data";"Заголовок","проверка";' ."\n" . '"""
            test;""";2', ['"
            test;"', '2']],
            ['"data,test";"Заголовок,проверка"' . "\ntest;2", ['test', '2']],
            ['"data,test";"Заголовок,проверка"' . "\n" . "\"\ntest\";2", ["\ntest", '2']],
        ];
    }

    /**
     * @dataProvider makeConvertEncodingTestData
     */
    public function testConvertEncoding(StreamInterface $stream, array $auditData)
    {
        $accessor = new Csv(new Csv\TableOptions($stream));

        $accessor->rewind();
        for ($i = 0; $i < count($auditData); ++$i) {
            self::assertSame($auditData[$i], $accessor->current());
        }
    }

    /**
     * @dataProvider makeDefineSeparatorTestData
     */
    public function testDetermineSeparator(string $data, array $firstRow)
    {
        $file = tmpfile();
        fwrite($file, $data);
        $accessor = new Csv(new Csv\TableOptions(new Stream($file)));
        $accessor->rewind();

        self::assertSame($firstRow, array_values($accessor->current()));
    }
}
