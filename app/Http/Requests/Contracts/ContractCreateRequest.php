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
            'client_id' => "required",
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

    public function attributes()
    {
        return [
            'client_id' => "клиент",
            'type' => "тип договора",
            'number' => "номер договора",
            'date' => "дата договора",
            'date_start' => "дата начала",
            'date_stop' => "дата окончания",
            'day_payment' => "день оплаты",
            'price' => "стоимость",
            'comment' => "комментарий",
        ];
    }
}
