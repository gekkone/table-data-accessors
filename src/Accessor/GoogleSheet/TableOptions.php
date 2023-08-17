<?php

namespace Gekkone\TdaLib\Accessor\GoogleSheet;

use Gekkone\TdaLib\Accessor;
use Google;
use InvalidArgumentException;

/**
 * @method static static new(Google\Service\Sheets $service, string $spreadsheetId, ?int $sheetId = null, ?string $range = null, int $chunkSize = self::DEFAULT_CHUNK_SIZE)
 */
class TableOptions extends Accessor\TableOptions
{
    public const DEFAULT_CHUNK_SIZE = 500;

    protected const OAUTH_SCOPES = [
        Google\Service\Sheets::DRIVE,
        Google\Service\Sheets::DRIVE_READONLY,
        Google\Service\Sheets::DRIVE_FILE,
        Google\Service\Sheets::SPREADSHEETS,
        Google\Service\Sheets::SPREADSHEETS_READONLY
    ];

    protected Google\Service\Sheets $service;
    protected string $spreadsheetId;
    protected ?int $sheetId;
    protected ?string $range = null;
    protected int $chunkSize;

    /**
     * @param Google\Service\Sheets $service
     * @param string $spreadsheetId
     * @param null|int $sheetId - if null, data from the first table will be read
     * @param null|string $range - A1 notation without row indexes, example "A:C".
     * If null, data from all column table will be read
     * @param int $chunkSize
     * @throws InvalidArgumentException
     */
    public function __construct(
        Google\Service\Sheets $service,
        string $spreadsheetId,
        ?int $sheetId = null,
        ?string $range = null,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE
    ) {
        if (!$this->checkClientScopes($service->getClient())) {
            throw new InvalidArgumentException(
                'Google client must contain at least one of the following scope: '
                . join(',', self::OAUTH_SCOPES)
            );
        }

        if (empty($spreadsheetId)) {
            throw new InvalidArgumentException('Param sheetId is not be empty');
        }

        $this->service = $service;
        $this->spreadsheetId = $spreadsheetId;
        $this->setSheetId($sheetId);
        $this->setRange($range);
        $this->setChunkSize($chunkSize);
    }

    public function getSpreadsheetId(): string
    {
        return $this->spreadsheetId;
    }

    public function getService(): Google\Service\Sheets
    {
        return $this->service;
    }

    public function getSheetId(): ?int
    {
        return $this->sheetId;
    }

    public function setSheetId(?int $sheetId): self
    {
        $this->sheetId = $sheetId;
        return $this;
    }

    public function getRange(): ?string
    {
        return $this->range;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setRange(?string $range): self
    {
        if ($range === null || preg_match('/^[A-Z]+:[A-Z]+$/', $range)) {
            $this->range = $range;
        } else {
            throw new InvalidArgumentException(
                'Range is must be specified in A1 notation without row indexes, '
                . 'example "A:C"'
            );
        }

        return $this;
    }

    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setChunkSize(int $chunkSize): self
    {
        if ($chunkSize <= 0) {
            throw new InvalidArgumentException('Chunk size has to more 0');
        }

        $this->chunkSize = $chunkSize;
        return $this;
    }

    protected function checkClientScopes(Google\Client $client): bool
    {
        foreach ($client->getScopes() as $scope) {
            if (in_array($scope, self::OAUTH_SCOPES)) {
                return true;
            }
        }

        return false;
    }
}
