<?php

namespace Tests;

use Gekkone\TdaLib\Accessor\TableOptions;
use Gekkone\TdaLib\ResolveColumnNameTrait;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class ResolveColumnNameTraitTest extends TestCase
{

    public function testParseHeaderRow()
    {
        $resolver = $this->makeTrait();

        $options = new TableOptions();
        $options->addColumn('header', 'hdr', true);
        $options->addColumn('Тестовый заголовок');
        $options->addColumn('notFound', 'test');

        $headerRow = ['header', 'Тeстовый зaгoлoвоk'];
        $resolver->parseHeaderRow($headerRow, $options);

        //not found required header
        $resolver = $this->makeTrait();
        $headerRow = ['hdr', 'тестовый заголовок'];
        $this->expectException(UnexpectedValueException::class);
        $resolver->parseHeaderRow($headerRow, $options);
    }

    public function testResolveColumnNames()
    {
        $resolver = $this->makeTrait();
        //before parser header row
        self::assertNull($resolver->resolveOriginColumnName('test'));

        $options = new TableOptions();
        $options->addColumn('header', 'hdr', true);
        $options->addColumn('Тестовый заголовок');
        $options->addColumn('notFound', 'test');


        $headerRow = ['Header', 'Тeстовый зaгoлoвоk', 'HeADer '];
        self::assertSame($headerRow, $resolver->resolveColumnNames($headerRow));
        $resolver->parseHeaderRow($headerRow, $options);
        self::assertSame(
            ['hdr', 'Тестовый заголовок', 2],
            array_keys($resolver->resolveColumnNames(['', '', '']))
        );

        self::assertSame('Header', $resolver->resolveOriginColumnName('hdr'));
        self::assertSame(
            'Тeстовый зaгoлoвоk',
            $resolver->resolveOriginColumnName('Тестовый заголовок')
        );
        self::assertNull($resolver->resolveOriginColumnName('notFound'));

        $headerRow = ['Header', 'Тeстовый зaгoлoвоk', ' '];
        $resolver->parseHeaderRow($headerRow, $options);
        self::assertSame(
            ['hdr', 'Тестовый заголовок', 2],
            array_keys($resolver->resolveColumnNames(['', '', '']))
        );

        //TODO
    }

    protected function makeTrait()
    {
        return new class {
            use ResolveColumnNameTrait {
                parseHeaderRow as public;
                resolveColumnNames as public;
            }
        };
    }
}
