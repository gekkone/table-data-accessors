# Table Data Accessors
A common interface for accessing table data on PHP. Contains implementations for csv and google spreadsheets

## Details
An extended iterator interface is provided to access tabular data.
<details>
  <summary>src/TableIteratorInterface.php line 7:11</summary>

  ```php
  interface TableIteratorInterface extends Iterator
  {
      public function fetchRowCount(bool $skipEmpty = false): int;
      public function currentField(string $field, $default = null);
  }
  ```
</details>

> :warning: **Attention:** The data of the first row is used as keys in the associative array returned for each of the rows

## Code Examples
Simple read from csv file:
```php
use Gekkone\TdaLib\Accessor\Csv;
use GuzzleHttp\Psr7\Stream;

$accessor = new Csv(
    Csv\TableOptions::new(new Stream(fopen(__DIR__ . '/filename.csv', 'r')))
);

foreach ($accessor as $row => $fields) {
    // $fields = ['columnHeader' => mixed, ...] or [(int) columnIndex => mixed, ...]
}
```
<br>

Simple read from Google Sheet
```php
use Gekkone\TdaLib\Accessor\GoogleSheet;
use Google\Client;
use Google\Service\Sheets;

$googleCline = new Client([
    'scopes' => Sheets::SPREADSHEETS_READONLY
]);

// for read first sheet
$accessor = new GoogleSheet(
    GoogleSheet\TableOptions::new(new Sheets($googleCline), 'spreadsheetId')
);

// for read concreate sheet set sheetId, find it in url after '#gid=',
// example https://docs.google.com/spreadsheets/d/<spreadsheetID>/edit#gid=1737163713)
$accessor = new GoogleSheet(
    GoogleSheet\TableOptions::new(new Sheets($googleCline), 'spreadsheetId', 1737163713)
);

foreach ($accessor as $row => $fields) {
    // $fields = ['columnHeader' => mixed, ...] or [(int) columnIndex => mixed, ...]
}
```
<br>
<a rel="license" href="http://creativecommons.org/licenses/by/4.0/">
  <img alt="Creative Commons License" style="border-width:0" src="https://i.creativecommons.org/l/by/4.0/88x31.png">
</a>
<br>
This work is licensed under a 
<a rel="license" href="http://creativecommons.org/licenses/by/4.0/">Creative Commons Attribution 4.0 International License</a>
