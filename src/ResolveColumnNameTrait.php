<?php

namespace Gekkone\TdaLib;

use UnexpectedValueException;

trait ResolveColumnNameTrait
{
    /** @var null|array<string,string> key - originColumn, value - columnAlias */
    protected ?array $columnAliasNameMap = null;
    /** @var null|array<int,string> index - columnIndex, value - columnAlias */
    protected ?array $columnAliases = null;

    public function resolveOriginColumnName(string $alias): ?string
    {
        if ($this->columnAliasNameMap === null) {
            return null;
        }

        return $this->columnAliasNameMap[$alias] ?? null;
    }

    /**
     * @param array $rowData
     * @return array<string|int,string>
     * key - resolvedColumnName or columnIndex, value - field data
     */
    protected function resolveColumnNames(array $rowData): array
    {
        if ($this->columnAliases === null) {
            return $rowData;
        }

        $resolvedColumnsRowData = [];
        foreach ($this->columnAliases as $index => $alias) {
            $resolvedColumnsRowData[$alias] = $rowData[$index] ?? null;
        }

        if (isset($index) && count($rowData) >= $index) {
            for ($i = $index + 1; $i < count($rowData); ++$i) {
                $resolvedColumnsRowData[$i] = $rowData[$i];
            }
        }

        return $resolvedColumnsRowData;
    }

    /**
     * @throws UnexpectedValueException - if not found required columns
     */
    protected function parseHeaderRow(
        array $headerRow,
        Accessor\TableOptions $options
    ): void {
        if ($this->columnAliases !== null) {
            return;
        }

        $notFound = [];
        $normalizeColumns = [];
        $columnAliases = [];
        $columnAliasNameMap = [];
        foreach ($headerRow as $index => $field) {
            $normalized = Accessor\TableOptions::normalizeColumnName($field);
            if (empty($normalized) || in_array($normalized, $normalizeColumns)) {
                continue;
            }

            $alias = $options->getAliasByColumn($field) ?? $field;
            $normalizeColumns[] = $normalized;
            $columnAliases[$index] = $alias;
            $columnAliasNameMap[$alias] = $field;
        }

        foreach ($options->getRequiredColumns() as $column) {
            if (!in_array($column, $normalizeColumns)) {
                $notFound[] = $column;
            }
        }
        if (!empty($notFound)) {
            throw new UnexpectedValueException(
                'Not found require headers: ' . join(', ', $notFound)
            );
        }

        $this->columnAliases = $columnAliases;
        $this->columnAliasNameMap = $columnAliasNameMap;
    }
}
