<?php

namespace App\Modules\Projects\Contracts;

use Illuminate\Support\Collection;

interface EmployeeDirectoryContract
{
    public function getActiveEmployees(): Collection;
}
