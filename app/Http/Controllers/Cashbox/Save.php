<?php

namespace App\Http\Controllers\Cashbox;

use App\Http\Controllers\Cashbox;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Expenses\Types;
use App\Models\AdditionalService;
use App\Models\CashboxTransaction;
use App\Models\IncomeSource;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;

class Save extends Controller
{
    /**
     * Сохранение или создание
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        $rules = [
            'is_income' => Rule::requiredIf(!$request->is_expense),
            'is_expense' => Rule::requiredIf(!$request->is_income),
            'sum' => "required|numeric",
            'date' => "required",
            'expense_type_id' => "required_if:is_expense,true",
        ];

        if ($request->is_income) {
            $rules['income_source_parking_id'] = "required_if:purpose_pay,2";
        }

        if ($request->income_type_pay == "tenant") {
            $rules['income_source_id'] = "required_if:is_income,true";
        }

        if ($request->income_type_pay != "parking_one") {
            $rules['purpose_pay'] = "required_with:income_source_id";
        }

        $request->validate($rules);

        $row = CashboxTransaction::find($request->id);

        if ($request->id and !$row)
            return response()->json(['message' => "Данная строка не найдена"], 400);

        if (!$row)
            $row = new CashboxTransaction;

        $row->type_pay = $request->type_pay ?: 1;
        $row->date = $request->date ?: now()->format("Y-m-d");
        $row->month = $request->month ?: now()->create($row->date)->format("Y-m");
        $row->period_start = $request->period_start ?: now()->create($row->month)->startOfMonth()->format("Y-m-d");
        $row->period_stop = $request->period_stop ?: now()->create($row->month)->endOfMonth()->format("Y-m-d");

        if ($request->is_income)
            $row = $this->saveIncome($row, $request);

        if ($request->is_expense)
            $row = $this->saveExpense($row, $request);

        $row->user_id = $row->user_id ?: $request->user()->id;

        $row->save();

        Log::write($row, $request);

        if ($row->income_source_id)
            Artisan::call("pays:overdue {$row->income_source_id}");

        $cashbox = new Cashbox;

        return response()->json([
            'row' => $cashbox->row($row),
            'statistics' => $cashbox->getStatistics([$row->date]),
        ]);
    }

    /**
     * Сохранение прихода
     * 
     * @param  \App\Models\CashboxTransaction $row
     * @param  \Illuminate\Http\Request $request
     * @return \App\Models\CashboxTransaction
     */
    public function saveIncome(CashboxTransaction $row, Request $request)
    {
        $row->is_income = true;
        $row->sum = abs($request->sum);
        $row->purpose_pay = $request->purpose_pay;
        $row->income_source_id = $request->income_source_id;
        $row->income_source_parking_id = $request->income_source_parking_id;
        $row->income_type_pay = $request->income_type_pay;

        if ($source = IncomeSource::find($row->income_source_id)) {
            $row->income_part_id = $source->part_id;
            $row->name = $source->name;
        } else {
            $row->name = null;
        }

        if (is_string($request->income_source_service_id)) {

            $additional_service = AdditionalService::create([
                'name' => $request->income_source_service_id,
                'is_one' => true,
            ]);

            $request->income_source_service_id = $additional_service->id;
        }

        $row->income_source_service_id = $request->income_source_service_id;
        $row->comment = $request->comment;

        /** Обнуление данных расхода */
        $row->is_expense = false;
        $row->expense_type_id = null;
        $row->expense_subtype_id = null;

        if ($row->income_type_pay == "parking_one") {
            $row->period_start = null;
            $row->period_stop = null;
            $row->name = "Гостевая парковка";
        }

        return $row;
    }

    /**
     * Сохранение расхода
     * 
     * @param  \App\Models\CashboxTransaction $row
     * @param  \Illuminate\Http\Request $request
     * @return \App\Models\CashboxTransaction
     */
    public function saveExpense(CashboxTransaction $row, Request $request)
    {
        $row->is_expense = true;
        $row->sum = abs($request->sum) * (-1);
        $row->name = $request->name;
        $row->expense_type_id = $request->expense_type_id;
        $row->expense_subtype_id = $request->expense_subtype_id;

        if (is_string($row->expense_subtype_id))
            $row->expense_subtype_id = $this->createExpenseSubtype($request->expense_type_id, $request->expense_subtype_id);

        /** Обнуление прихода */
        $row->is_income = false;
        $row->purpose_pay = $row->expense_type_id == 1 ? $request->purpose_pay : null;
        $row->income_source_id = null;
        $row->income_source_parking_id = null;
        $row->income_type_pay = null;

        return $row;
    }

    /**
     * Создает новое фиксированное наименование типа расходов
     * 
     * @param  int $id
     * @param  string $name
     * @return int
     */
    public function createExpenseSubtype($id, $name)
    {
        return (new Types)->createSubType($id, $name);
    }

    /**
     * Удаляет или восстанавливает строку
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(Request $request)
    {
        if (!$row = CashboxTransaction::withTrashed()->find($request->id))
            return response()->json(['message' => "Строка не найдена"], 401);

        $row->deleted_at ? $row->restore() : $row->delete();

        return response()->json([
            'row' => $row,
        ]);
    }
}
