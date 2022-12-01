<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clients\ClientsCreateRequest;
use App\Http\Resources\Clients\ClientResource;
use App\Http\Services\Clients\ClientsService;
use Illuminate\Http\Request;

class ClientsController extends Controller
{
    public function __construct(
        protected ClientsService $service
    ) {
    }

    public function create(ClientsCreateRequest $request)
    {
        return new ClientResource(
            $this->service->create([
                'name' => $request->name,
                'requisites' => $request->requisites,
                'contacts_name' => $request->contacts_name,
                'contacts_phone' => $request->contacts_phone,
                'contacts_email' => $request->contacts_email,
                'comment' => $request->comment,
            ])
        );
    }
}
