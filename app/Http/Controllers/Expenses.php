<?php

namespace App\Http\Controllers;

use App\Models\CashboxTransaction;
use App\Models\ExpenseType;
use Illuminate\Http\Request;

class Expenses extends Controller
{
    /**
     * Список типов расхода
     * 
     * @var array
     */
    protected $expense_types = [
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
        $paginate = CashboxTransaction::whereIsExpense(true)
            ->orderBy('id', 'DESC')
            ->paginate(60);

        $rows = $paginate->map(function ($row) {
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

        $row->name = $request->name;
        $row->sum = $sum;
        $row->is_expense = true;
        $row->expense_type_id = $request->expense_type_id;
        $row->expense_subtype_id = $request->expense_subtype_id;
        $row->date = $row->date ?: now()->format("Y-m-d");
        $row->user_id = $row->user_id ?: $request->user()->id;

        $row->save();

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

        return $this->expense_types[$id] = ExpenseType::find($id)->name ?? null;
    }
}
