<?php

namespace App\Modules\Projects\Contracts;

use Illuminate\Support\Collection;

interface ZohoClientDirectoryContract
{
    public function getSelectableClients(): Collection;

    public function existsById(int $id): bool;
}
