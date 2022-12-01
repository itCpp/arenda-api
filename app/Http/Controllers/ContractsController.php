<?php

namespace App\Http\Controllers;

use App\Http\Requests\Contracts\ContractCreateRequest;
use App\Http\Resources\Cotracts\ContractResource;
use App\Http\Services\Contracts\ContractsService;
use Illuminate\Http\Request;

class ContractsController extends Controller
{
    public function __construct(
        protected ContractsService $service
    ) {
    }

    public function create(ContractCreateRequest $request)
    {
        $data = $request->only([
            'client_id',
            'type',
            'number',
            'date',
            'date_start',
            'date_stop',
            'day_payment',
            'price',
            'comment',
        ]);

        return new ContractResource(
            $this->service->create($data)
        );
    }
}