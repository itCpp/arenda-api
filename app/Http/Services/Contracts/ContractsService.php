<?php

namespace App\Http\Services\Contracts;

use App\Models\Contract;

class ContractsService
{
    public function create(array $data): Contract
    {
        return Contract::create($data);
    }
}
