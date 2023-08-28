<?php

namespace Gekkone\TdaLib\Accessor;

use Gekkone\TdaLib\Accessor\GoogleSheet\TableOptions;
use Gekkone\TdaLib\ResolveColumnNameTrait;
use Gekkone\TdaLib\TableIteratorInterface;
use Google\Service\Sheets;
use InvalidArgumentException;
use OutOfBoundsException;
use OutOfRangeException;
use UnexpectedValueException;

class GoogleSheet implements TableIteratorInterface
{
    use ResolveColumnNameTrait;

    protected TableOptions $options;
    protected ?Sheets\SheetProperties $sheetProperties = null;
    protected int $offset = 0;
    protected int $rowIndex = 0;
    protected array $currentChunk = [];

    public function __construct(TableOptions $options)
    {
        $this->options = clone $options;
    }

    /**
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     * @throws OutOfBoundsException
     * @throws OutOfRangeException
     */
    public function rewind(): void
    {
        $this->offset = 0;
        $this->rowIndex = 0;
        $this->initSheetData();
        $this->next();
    }

    public function valid(): bool
    {
        return isset($this->currentChunk[$this->rowIndex]);
    }

    public function key()
    {
        return $this->rowIndex;
    }

    public function current()
    {
        return $this->resolveColumnNames($this->currentChunk[$this->rowIndex]);
    }

    /**
     * @throws UnexpectedValueException
     */
    public function next(): void
    {
        if (!isset($this->currentChunk[$this->rowIndex + 1])) {
            $this->nextChunk();
        }

        if (isset($this->currentChunk[$this->rowIndex + 1])) {
            ++$this->rowIndex;
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException
     * @throws UnexpectedValueException
     * @throws OutOfRangeException
     */
    public function fetchRowCount(bool $skipEmpty = false): int
    {
        $this->initSheetData();

        if (!$skipEmpty) {
            return $this->sheetProperties->getGridProperties()->getRowCount() - 1;
        }

        $offset = $this->offset;
        $rowIndex = $this->rowIndex;
        $currentChunk = $this->currentChunk;
        $count = 0;

        $this->rewind();
        foreach ($this as $row) {
            $row = array_filter($row);
            if (!empty($row)) {
                ++$count;
            }
        }
        $this->offset = $offset;
        $this->rowIndex = $rowIndex;
        $this->currentChunk = $currentChunk;

        return $count;
    }

    public function currentField(string $field, $default = null)
    {
        return $this->current()[$field] ?? $default;
    }

    /**
     * @throws UnexpectedValueException
     */
    protected function nextChunk()
    {
        $this->currentChunk = [];

        if ($this->offset + 1 > $this->sheetProperties->getGridProperties()->rowCount) {
            return;
        }

        $range = explode(':', $this->options->getRange());
        $range[0] .= $this->offset + 1;
        $range[1] .= min(
            $this->offset + $this->options->getChunkSize(),
            $this->sheetProperties->getGridProperties()->rowCount
        );
        $range = "'{$this->sheetProperties->getTitle()}'!" . join(':', $range);

        $chunk = $this->options->getService()->spreadsheets_values
            ->get($this->options->getSpreadsheetId(), $range);

        foreach ($chunk->getValues() ?? [] as $row => $rowData) {
            if ($this->offset == 0 && $row == 0) {
                $this->parseHeaderRow($rowData, $this->options);
                ++$this->rowIndex;
                continue;
            }

            $this->currentChunk[$row + 1 + $this->offset] = $rowData;
        }

        $this->offset += $this->options->getChunkSize();
    }

    /**
     * @throws OutOfBoundsException
     * @throws InvalidArgumentException
     * @throws OutOfRangeException
     */
    protected function initSheetData()
    {
        if ($this->sheetProperties !== null) {
            return;
        }

        $spreadsheet = $this->options->getService()
            ->spreadsheets->get($this->options->getSpreadsheetId());

        if ($this->options->getSheetId() == null) {
            $this->sheetProperties = $spreadsheet->getSheets()[0]->getProperties();
            $this->options->setSheetId($this->sheetProperties->getSheetId());
        } else {
            foreach ($spreadsheet->getSheets() as $sheet) {
                if ($sheet->getProperties()->getSheetId() == $this->options->getSheetId()) {
                    $this->sheetProperties = $sheet->getProperties();
                    break;
                }
            }

            if ($this->sheetProperties == null) {
                throw new OutOfBoundsException(
                    "Sheet with {$this->options->getSheetId()} not exists "
                    . "in spreadsheet {$this->options->getSpreadsheetId()}"
                );
            }
        }

        $lastSheetColumn = $this->indexToA1Notation(
            $this->sheetProperties->getGridProperties()->columnCount
        );

        if ($this->options->getRange() == null) {
            $this->options->setRange("A:$lastSheetColumn");
        } else {
            [$firstColumn, $lastColumn] = explode(':', $this->options->getRange());
            $firstColumnIndex = $this->a1NotationToIndex($firstColumn);
            $lastColumnIndex = $this->a1NotationToIndex($lastColumn);
            $columnsCount = $this->sheetProperties->getGridProperties()->columnCount;

            if ($firstColumnIndex > $columnsCount
                || $lastColumnIndex > $columnsCount
            ) {
                throw new OutOfRangeException(
                    "Range {$this->options->getRange()} is out of ranges sheet "
                    . "{$this->options->getSheetId()} (A:$lastSheetColumn)"
                );
            }
        }
    }

    protected function indexToA1Notation($index): string
    {
        $columnName = '';

        while ($index > 0) {
            $remainder = ($index - 1) % 26;
            $columnName = chr(65 + $remainder) . $columnName;
            $index = intval(($index - $remainder) / 26);
        }

        return $columnName;
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
}
