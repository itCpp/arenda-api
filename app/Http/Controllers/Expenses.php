<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Expenses\SearchQuery;
use App\Http\Controllers\Expenses\Types;
use App\Models\CashboxTransaction;
use App\Models\Employee;
use App\Models\ExpenseSubtype;
use App\Models\ExpenseType;
use App\Models\File;
use App\Models\Log;
use Illuminate\Http\Request;

class Expenses extends Controller
{
    use SearchQuery;

    /**
     * Список типов расхода
     * 
     * @var array
     */
    protected $expense_types = [
        0 => null
    ];

    /**
     * Список фиксированных наименований расходов
     * 
     * @var array
     */
    protected $expense_subtypes = [
        0 => null
    ];

    /**
     * Выводит строки расхода
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $data = CashboxTransaction::whereIsExpense(true);

        if ((bool) $request->search)
            $data = $this->setSearchQuery($data, $request->search);

        $data = $data->orderBy('date', 'DESC')
            ->paginate(40);

        $rows = $data->map(function ($row) {
            return $this->getRowData($row, true);
        });

        return response()->json([
            'rows' => $rows,
        ]);
    }

    /**
     * Формирует строку расхода на вывод
     * 
     * @param  \App\Models\CashboxTransaction $row
     * @param  boolean $ro_array
     * @return \App\Models\CashboxTransaction|array
     */
    public function getRowData(CashboxTransaction $row, $to_array = false)
    {
        $row->sum = abs($row->sum);

        $row->type = $this->getExpenseTypeName($row->expense_type_id);
        $row->name_type = $this->getExpenseSubTypeName($row->expense_subtype_id, $row->expense_type_id);
        $row->files = File::where('cashbox_id', $row->id)->count();

        return $to_array ? $row->toArray() : $row;
    }

    /**
     * Вывод данных одного расхода
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        $row = CashboxTransaction::find($request->id);

        if ($request->id and !$row)
            return response()->json(['message' => "Данные о раcходе не найдены"], 400);

        if ($request->modalData) {

            $response['types'] = ExpenseType::orderBy('name')
                ->get()
                ->map(function ($row) {

                    $this->expense_types[$row->id] = $row->name;

                    return $row->only('name', 'id');
                })
                ->toArray();
        }

        if ($row)
            $row = $this->getRowData($row);

        $response['row'] = $row ?? [];

        return response()->json($response);
    }

    /**
     * Сохраняет или создает данные о расходе
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        if (!$row = CashboxTransaction::find($request->id))
            $row = new CashboxTransaction;

        if ($request->id != $row->id)
            return response()->json(['message' => "Данные о расходе не найдены"], 400);

        $request->validate([
            'sum' => "required|numeric",
            'name' => "required_without:expense_subtype_id",
            'expense_type_id' => "required|numeric",
        ]);

        $request_sum = (float) $request->sum;
        $sum = $request_sum > 0 ? $request_sum * (-1) : $request_sum;

        if ($request->expense_type_id and is_string($request->expense_subtype_id))
            $request->expense_subtype_id = Types::createSubType($request->expense_type_id, $request->expense_subtype_id);

        if (!$request->expense_type_id and $request->expense_subtype_id)
            $request->expense_subtype_id = null;

        $row->name = $request->name;
        $row->sum = $sum;
        $row->is_expense = true;
        $row->type_pay = $request->type_pay;
        $row->purpose_pay = (int) $request->purpose_pay;
        $row->expense_type_id = $request->expense_type_id;
        $row->expense_subtype_id = $request->expense_subtype_id;
        $row->date = $request->date ?: now()->format("Y-m-d");
        $row->month = $request->month ?: now()->create($row->date)->format("Y-m");
        $row->user_id = $row->user_id ?: $request->user()->id;
        $row->period_start = $request->period_start;
        $row->period_stop = $request->period_stop;

        $row->save();

        Log::write($row, $request);

        return response()->json([
            'row' => $this->getRowData($row),
        ]);
    }

    /**
     * Выводит наименоватие типа расхода
     * 
     * @param  int|null $id
     * @return string|null
     */
    public function getExpenseTypeName($id)
    {
        $id = (int) $id;

        if (isset($this->expense_types[$id]))
            return $this->expense_types[$id];

        $this->expense_types_object[$id] = ExpenseType::find($id);

        return $this->expense_types[$id] = $this->expense_types_object[$id]->name ?? null;
    }

    /**
     * Выводит фиксированное наименоватие расхода
     * 
     * @param  int|null $id
     * @param  int|null $type_id
     * @return string|null
     */
    public function getExpenseSubTypeName($id, $type_id)
    {
        $id = (int) $id;
        $type_id = (int) $type_id;

        if (isset($this->expense_subtypes[$type_id][$id]))
            return $this->expense_subtypes[$type_id][$id];

        $type_subtypes = ($this->expense_types_object[$type_id]->type_subtypes ?? null);

        if ($type_subtypes == "users") {

            $row = Employee::find($id);
            $name = ((string) ($row->surname ?? null)) . " ";
            $name .= ((string) ($row->name ?? null)) . " ";
            $name .= (string) ($row->middle_name ?? null);

            return $this->expense_subtypes[$type_id][$id] = trim($name);
        }

        return $this->expense_subtypes[$type_id][$id] = ExpenseSubtype::find($id)->name ?? null;
    }

    /**
     * Удаляет строку
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function drop(Request $request)
    {
        if (!$row = CashboxTransaction::find($request->id))
            return response()->json(['message' => "Данные о расходе не найдены"], 400);

        $row->delete();

        Log::write($row, $request);

        return response()->json([
            'row' => $this->getRowData($row),
        ]);
    }
}
