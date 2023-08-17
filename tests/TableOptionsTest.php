<?php

use PHPUnit\Framework\TestCase;
use Gekkone\TdaLib\Accessor\TableOptions;

class TableOptionsTest extends TestCase
{

    public static function getAddColumnTestDataSet(): array
    {
        return [
            [[
                ['first', 'simple-alias', false],
                ['Second', 'simple', false],
                ['third', null, true],
            ]],
            [[
                ['Пepвый', 'простой', false],
                ['ВтороЙ', null, true]
            ]]
        ];
    }

    public static function getNormalizeTestDataSet()
    {
        return [
            ['simple', 'simple'],
            ['простой', "простой"],
            ['withSomeThingCase', 'withsomethingcase'],
            ['with&*45458()#@!', 'with&*45458()#@!'],
            ['XkpeвойAбcoлютно_parse-test word test', 'хкревойабсолютно_parse-test word test']
        ];
    }

    /**
     * @dataProvider getAddColumnTestDataSet
     */
    public function testAddColumn(
        $callArgsSets
    ) {
        $options = new TableOptions();
        foreach ($callArgsSets as $set) {
            $options->addColumn(...$set);
        }

        foreach ($callArgsSets as $set) {
            self::assertContains(
                TableOptions::normalizeColumnName($set[0]),
                $options->getAllColumns()
            );
        }
    }

    /**
     * @dataProvider getAddColumnTestDataSet
     */
    public function testAliasColumn(array $callArgSet)
    {
        $options = new TableOptions();
        foreach ($callArgSet as $set) {
            $options->addColumn(...$set);
        }

        foreach ($callArgSet as $set) {
            $name = $set[0];
            $alias = $set[1];
            $normalizeName = TableOptions::normalizeColumnName($name);

            if (!empty($alias)) {
                self::assertSame($alias, $options->getAliasByColumn($name));
                self::assertSame($alias, $options->getAliasByColumn($normalizeName));
            } else {
                self::assertSame($name, $options->getAliasByColumn($name));
                self::assertSame($name, $options->getAliasByColumn($normalizeName));
            }
        }
    }

    /**
     * @dataProvider getAddColumnTestDataSet
     */
    public function testRequiredColumn($callArgSets)
    {
        $options = new TableOptions();
        foreach ($callArgSets as $set) {
            $options->addColumn(...$set);
        }

        foreach ($callArgSets as $set) {
            $name = $set[0];
            $normalizeName = TableOptions::normalizeColumnName($name);
            $required = $set[2];

            if ($required === true) {
                self::assertContains($normalizeName, $options->getRequiredColumns());
            } else {
                self::assertNotContains($normalizeName, $options->getRequiredColumns());
                self::assertNotContains($name, $options->getRequiredColumns());
            }
        }
    }

    /**
     * @dataProvider getDuplicateTestDataSets
     */
    public function testDuplicateColumn($callArgSets, $isDuplicateSet)
    {
        $options = new TableOptions();
        foreach ($callArgSets as $index => $set) {
            if ($isDuplicateSet[$index]) {
                self::assertFalse($options->addColumn(...$set));
            } else {
                self::assertTrue($options->addColumn(...$set));
            }
        }
    }

    /**
     * @dataProvider getNormalizeTestDataSet
     */
    public function testNormalize($name, $normalizeName)
    {
        self::assertSame($normalizeName, TableOptions::normalizeColumnName($name));
    }

    public static function getDuplicateTestDataSets(): array
    {
        return [
            [
                [['First'], ['first'], ['FiRsT'], ['second']],
                [false, true, true, false]
            ],
            [
                [['Первый'], ['Пepвый'], ['ПePвый элемент']],
                [false, true, false]
            ]
        ];
    }
}
