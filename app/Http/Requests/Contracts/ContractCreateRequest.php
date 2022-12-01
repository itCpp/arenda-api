<?php

namespace App\Http\Requests\Contracts;

use Illuminate\Foundation\Http\FormRequest;

class ContractCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'client_id' => "required|integer",
            'type' => "required",
            'number' => "required",
            'date' => "required|date",
            'date_start' => "date",
            'date_stop' => "date",
            'day_payment' => "nullable",
            'price' => "required|numeric",
            'comment' => "nullable",
        ];
    }
}
