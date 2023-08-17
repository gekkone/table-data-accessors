<?php

use PHPUnit\Framework\TestCase;
use Gekkone\TdaLib\Accessor\Csv\TableOptions;
use GuzzleHttp\Psr7\Stream;

class CsvTableOptionsTest extends TestCase
{
    public function testConstructor()
    {
        $source = new Stream(tmpfile());
        $options = new TableOptions($source);
        self::assertSame($source, $options->getSource());
        self::assertNull($options->getColumnSeparator());

        //stream is must be readable and seekable
        self::expectException(InvalidArgumentException::class);
        new TableOptions(new Stream(STDIN));
    }

    /**
     * @dataProvider makeValidSeparatorData
     */
    public function testValidSeparator($separatorSymbol)
    {
        //constructor
        $options = new TableOptions(new Stream(tmpfile()), $separatorSymbol);
        self::assertSame($separatorSymbol, $options->getColumnSeparator());

        //setter & getter
        $options = $this->makeOptions()->setColumnSeparator($separatorSymbol);
        self::assertSame($separatorSymbol, $options->getColumnSeparator());
    }

    /**
     * @dataProvider makeInvalidSeparatorData
     */
    public function testInvalidSeparator($separatorSymbol)
    {
        //constructor
        $this->expectException(InvalidArgumentException::class);
        $options = new TableOptions(new Stream(tmpfile()), $separatorSymbol);

        //setter & getter
        $this->expectException(InvalidArgumentException::class);
        $this->makeOptions()->setColumnSeparator($separatorSymbol);
    }

    public static function makeValidSeparatorData()
    {
        return [[','], [';'], ["\t"]];
    }

    public static function makeInvalidSeparatorData()
    {
        return [["\n"], [''], ['test'], [' '], [' '], [1], ['-'], ['\\']];
    }

    protected function makeOptions()
    {
        return new TableOptions(new Stream(tmpfile()));
    }
}
