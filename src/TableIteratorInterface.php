<?php

namespace Gekkone\TdaLib;

use Iterator;

interface TableIteratorInterface extends Iterator
{
    public function fetchRowCount(bool $skipEmpty = false): int;
    public function currentField(string $field, $default = null);
}
