<?php

namespace Gekkone\TdaLib\Accessor;

use Gekkone\TdaLib\Accessor\Csv\TableOptions;
use Gekkone\TdaLib\ResolveColumnNameTrait;
use Gekkone\TdaLib\TableIteratorInterface;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use UnexpectedValueException;

class Csv implements TableIteratorInterface
{
    use ResolveColumnNameTrait;

    protected TableOptions $options;
    protected StreamInterface $source;
    protected string $encoding;

    protected ?int $rowIndex = null;
    protected array $currentRow = [];

    public function __construct(TableOptions $options)
    {
        $this->options = clone $options;
        $this->source = $this->options->getSource();
    }

    /**
     * @throws UnexpectedValueException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function rewind(): void
    {
        $this->rowIndex = 0;
        $this->options->getSource()->seek(0);
        $this->parseHeaderRow($this->readCsvRow(), $this->options);
        $this->next();
    }

    /**
     * @throws UnexpectedValueException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function next(): void
    {
        $this->currentRow = $this->resolveColumnNames($this->readCsvRow());
    }

    public function key(): int
    {
        return $this->rowIndex;
    }

    public function valid(): bool
    {
        if (!$this->source->eof()) {
            return true;
        }

        //csv file can only contain \n symbol in the last line
        return !empty(array_filter($this->currentRow));
    }

    public function current(): array
    {
        return $this->currentRow;
    }

    /**
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function fetchRowCount(bool $skipEmpty = false): int
    {
        $pos = $this->source->tell();
        $rowIndex = $this->rowIndex;

        $this->source->seek(0);
        $this->rowIndex = 0;
        $count = 0;

        while (!$this->source->eof()) {
            if ($skipEmpty) {
                $line = array_filter(array_map('trim', $this->readCsvRow()));
                if (!empty($line)) {
                    ++$count;
                }
            } else {
                $this->readLine();
            }
        }

        if (!$skipEmpty) {
            $count = $this->rowIndex;
        }

        $this->source->seek($pos);
        $this->rowIndex = $rowIndex;
        return --$count; //without headers
    }

    public function currentField(string $field, $default = null)
    {
        return $this->current()[$field] ?? $default;
    }

    /**
     * @throws UnexpectedValueException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    protected function readCsvRow(): array
    {
        $line = $this->readLine();
        if (empty(trim($line))) {
            return [];
        }

        if ($this->rowIndex === 1) {
            //check and remove byte order mark (U+FEFF)
            $line = self::removeBOM($line);
        }
        $encoding = mb_detect_encoding($line, ['UTF-8', 'Windows-1251'], true);
        //remove boom from first line
        if ($encoding == 'UTF-8' && $this->rowIndex == 1) {
            $line = self::removeBOM($line);
        }

        if ($encoding !== 'UTF-8') {
            $line = mb_convert_encoding($line, 'UTF-8', $encoding);
            if ($line === false) {
                throw new UnexpectedValueException(
                    "failed convert encoding for line $this->rowIndex"
                );
            }
        }

        if ($this->options->getColumnSeparator() === null) {
            $this->options->setColumnSeparator(
                $this->defineColumnSeparator($line)
            );
        }

        return str_getcsv($line, $this->options->getColumnSeparator());
    }

    /**
     * @throws RuntimeException
     */
    protected function readLine(string $quotationMark = '"'): string
    {
        $openQuotationMark = false;
        $line = '';

        do {
            $symbol = $this->source->read(1);

            if ($symbol == "\n" && !$openQuotationMark) {
                break;
            } else {
                $line .= $symbol;
            }

            if ($symbol == $quotationMark) {
                $openQuotationMark = !$openQuotationMark;
            }
        } while (!$this->source->eof());

        if (!$this->source->eof() || !empty(trim($line))) {
            ++$this->rowIndex;
        }

        return $line;
    }

    protected function removeBOM(string $data): string
    {
        if (substr($data, 0, 3) === pack('CCC', 0xEF, 0xBB, 0xBF)) {
            $data = substr($data, 3);
        }

        return $data;
    }

    protected function stripScvContent(string $content, $quotationMark = '"'): string
    {
        $openQuotationMark = false;
        $line = '';
        $i = 0;

        while (isset($content[$i])) {
            $symbol = $content[$i];
            ++$i;

            if ($symbol == $quotationMark) {
                if (!$openQuotationMark) {
                    $openQuotationMark = true;
                } elseif (!isset($content[$i]) || $content[$i] !== $quotationMark) {
                    $openQuotationMark = false;
                }
                continue;
            }

            if (!$openQuotationMark) {
                $line .= $symbol;
            }
        }

        return $line;
    }

    /**
     * @throws RuntimeException
     */
    private function defineColumnSeparator(string $content): string
    {
        $content = $this->stripScvContent($content);
        $intended = array_fill_keys(TableOptions::AVAILABLE_COLUMN_SEPARATORS, 0);

        $i = 0;
        while (isset($content[$i])) {
            $symbol = $content[$i];
            ++$i;

            if (in_array($symbol, TableOptions::AVAILABLE_COLUMN_SEPARATORS)) {
                ++$intended[$symbol];
            }
        }

        arsort($intended, SORT_NUMERIC);
        $keys = array_keys($intended);
        if ($intended[$keys[0]] == $intended[$keys[1]]) {
            throw new RuntimeException('Failed define scv separator');
        }

        return $keys[0];
    }
}
