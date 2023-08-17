<?php

namespace Gekkone\TdaLib\Accessor\Csv;

use Gekkone\TdaLib\Accessor;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

class TableOptions extends Accessor\TableOptions
{
    public const AVAILABLE_COLUMN_SEPARATORS = [',', ';', "\t"];

    protected StreamInterface $source;
    protected ?string $columnSeparator = null;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(StreamInterface $source, ?string $columnSeparator = null)
    {
        parent::__construct();

        if (!$source->isSeekable() || !$source->isReadable()) {
            throw new InvalidArgumentException(
                'Source must have isSeekable and isReadable'
            );
        }


        $this->source = $source;
        if (is_string($columnSeparator)) {
            $this->setColumnSeparator($columnSeparator);
        }
    }

    public function getSource(): StreamInterface
    {
        return $this->source;
    }

    public function getColumnSeparator(): ?string
    {
        return $this->columnSeparator;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setColumnSeparator(string $columnSeparator): self
    {
        if (in_array($columnSeparator, self::AVAILABLE_COLUMN_SEPARATORS)) {
            $this->columnSeparator = $columnSeparator;
        } else {
            throw new InvalidArgumentException(
                "Invalid column separator '" . addslashes($columnSeparator) . "',"
                . 'available only ' . join(
                    ', ',
                    array_map(
                        fn ($s) => "'" . str_replace("\t", '\t', $s) . "'",
                        self::AVAILABLE_COLUMN_SEPARATORS
                    )
                )
            );
        }

        return $this;
    }
}
