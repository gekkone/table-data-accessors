<?php

namespace Gekkone\TdaLib\Tests;

use Gekkone\TdaLib\Accessor\Csv\TableOptions;
use GuzzleHttp\Psr7\Stream;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CsvTableOptionsTest extends TestCase
{
    public static function makeValidSeparatorSet()
    {
        return [[','], [';'], ["\t"]];
    }

    public static function makeInvalidSeparatorSet()
    {
        return [["\n"], [''], ['test'], [' '], [' '], [1], ['-'], ['\\']];
    }

    public function testSetSourceInConstructor()
    {
        $source = new Stream(tmpfile());
        $options = new TableOptions($source);
        self::assertSame($source, $options->getSource());
        self::assertNull($options->getColumnSeparator());

        self::assertEquals($options, TableOptions::new($source));

        //stream is must be readable and seekable
        self::expectException(InvalidArgumentException::class);
        new TableOptions(new Stream(STDIN));
    }

    /**
     * @dataProvider makeValidSeparatorSet
     */
    public function testSetSeparator($separatorSymbol)
    {
        //constructor
        $options = new TableOptions(new Stream(tmpfile()), $separatorSymbol);
        self::assertSame($separatorSymbol, $options->getColumnSeparator());

        //static construct method
        $options = TableOptions::new(new Stream(tmpfile()), $separatorSymbol);
        self::assertSame($separatorSymbol, $options->getColumnSeparator());

        //setter & getter
        $options = $this->makeOptions()->setColumnSeparator($separatorSymbol);
        self::assertSame($separatorSymbol, $options->getColumnSeparator());
    }

    /**
     * @dataProvider makeInvalidSeparatorSet
     */
    public function testFailedSetSeparator($separatorSymbol)
    {
        //setter & getter
        $this->expectException(InvalidArgumentException::class);
        $this->makeOptions()->setColumnSeparator($separatorSymbol);
    }


    /**
     * @dataProvider makeInvalidSeparatorSet
     */
    public function testFailedSeparatorInConstructor($separatorSymbol)
    {
        $this->expectException(InvalidArgumentException::class);
        new TableOptions(new Stream(tmpfile()), $separatorSymbol);
    }

    /**
     * @dataProvider makeInvalidSeparatorSet
     */
    public function testFailedSeparatorInConstructMethod($separatorSymbol)
    {
        $this->expectException(InvalidArgumentException::class);
        TableOptions::new(new Stream(tmpfile()), $separatorSymbol);
    }

    protected function makeOptions()
    {
        return new TableOptions(new Stream(tmpfile()));
    }
}
