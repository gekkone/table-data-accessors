<?php

namespace Gekkone\TdaLib\Accessor;

use InvalidArgumentException;

class TableOptions
{
    protected array $headerAliasMap = [];
    protected array $requiredHeaders = [];

    public static function new(): self
    {
        return new static(...func_get_args());
    }

    public static function normalizeColumnName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/\s/u', ' ', $name);
        $name = str_replace(['—', '–', '−', '-'], '-', $name);

        //fix typo latin symbol in rus words
        $words = explode(' ', str_replace(['-', '_'], ' ', $name));
        foreach ($words as $word) {
            if (mb_strlen($word) !== strlen($word)) {
                $fixed = str_replace(
                    ['a', 'e', 'o', 'p', 'k', 'c', 'x'], //latin
                    ['а', 'е', 'о', 'р', 'к', 'с', 'х'], //rus
                    $word
                );

                $fixed = str_replace('ё', 'е', $fixed);
                if ($fixed != $word) {
                    $name = str_replace($word, $fixed, $name);
                }
            }
        }

        return $name;
    }

    /**
     * @param string $name - convert to case-insensitivity and rus ord 'ё' to 'е'
     * @param string|null $alias - if empty, param name will be used as alias
     * @param bool $required
     * @return self
     * @throws InvalidArgumentException - if append column, false if column name duplicated
     */
    public function addColumn(
        string $name,
        ?string $alias = null,
        bool $required = false
    ): self {
        if (empty(trim($alias))) {
            $alias = $name;
        }
        $name = self::normalizeColumnName($name);

        if (array_key_exists($name, $this->headerAliasMap)) {
            throw new InvalidArgumentException(
                "Column $name already exists with alias {$this->headerAliasMap[$name]}"
            );
        }

        $this->headerAliasMap[$name] = $alias;
        if ($required) {
            $this->requiredHeaders[] = $name;
        }

        return $this;
    }

    public function getRequiredColumns(): array
    {
        return $this->requiredHeaders;
    }

    public function getAllColumns(): array
    {
        return array_keys($this->headerAliasMap);
    }

    public function getAliasByColumn(string $name): ?string
    {
        $name = self::normalizeColumnName($name);
        return $this->headerAliasMap[$name] ?? null;
    }
}
