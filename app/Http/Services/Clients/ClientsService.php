<?php

namespace App\Http\Services\Clients;

use App\Models\Client;

class ClientsService
{
    public function create(array $data): Client
    {
        return Client::create($data);
    }
}
