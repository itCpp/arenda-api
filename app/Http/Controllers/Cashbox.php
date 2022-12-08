<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Cashbox\Statistics;
use App\Http\Controllers\Employees\Salaries;
use App\Http\Controllers\Expenses\Types;
use App\Http\Controllers\Incomes\Purposes;
use App\Http\Controllers\Tenants\AdditionalServices;
use App\Models\AdditionalService;
use App\Models\CashboxTransaction;
use App\Models\Employee;
use App\Models\ExpenseSubtype;
use App\Models\ExpenseType;
use App\Models\IncomeSource;
use App\Models\IncomeSourceParking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Cashbox extends Controller
{
    use Statistics;

    /**
     * Вывод данных на главной странице
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $data = CashboxTransaction::orderBy('date', "DESC")
            ->when((bool) $request->search, function ($query) use ($request) {

                $search = $request->search;

                $query->when((bool) ($search['name'] ?? null), function ($query) use ($search) {

                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search['name']}%")
                            ->orWhere(function ($query) use ($search) {

                                $types = [];

                                ExpenseSubtype::where('expense_type_id', '!=', 1)
                                    ->where('name', 'like', "%{$search['name']}%")
                                    ->get()
                                    ->each(function ($row) use (&$types) {
                                        $types[$row->expense_type_id][] = $row->id;
                                    });

                                Employee::withTrashed()
                                    ->where(DB::raw("CONCAT(surname,' ',name,' ',middle_name)"), 'like', "%{$search['name']}%")
                                    ->get()
                                    ->each(function ($row) use (&$types) {
                                        $types[1][] = $row->id;
                                    });

                                foreach ($types as $type => $subtypes) {
                                    $query->orWhere(function ($query) use ($type, $subtypes) {
                                        $query->where('expense_type_id', $type)
                                            ->whereIn('expense_subtype_id', $subtypes);
                                    });
                                }
                            })
                            ->orWhere(function ($query) use ($search) {

                                $types = [];

                                IncomeSource::withTrashed()
                                    ->where('name', 'like', "%{$search['name']}%")
                                    ->get()
                                    ->each(function ($row) use (&$types) {
                                        $types[] = $row->id;
                                    });

                                $query->orWhereIn('income_source_id', $types);
                            });
                    });
                })
                    ->when((bool) ($search['date'] ?? null), function ($query) use ($search) {
                        $query->where('date', $search['date']);
                    });
            })
            ->orderBy('id', "DESC")
            ->paginate(40);

        $dates = [];

        $rows = $data->map(function ($row) use (&$dates) {

            $dates[] = $row->date;

            return $this->row($row);
        });

        $statistics = $this->getStatistics($dates);

        return response()->json([
            'rows' => $rows ?? [],
            'page' => $data->currentPage(),
            'pages' => $data->lastPage(),
            'end' => $data->currentPage() == $data->lastPage(),
            'statistics' => $statistics,
        ]);
    }

    /**
     * Формирование данных одной строки
     * 
     * @param  \App\Models\CashboxTransaction $row
     * @return \App\Models\CashboxTransaction
     */
    public function row(CashboxTransaction $row)
    {
        if ($row->is_income)
            return $this->incomeRow($row);
        else if ($row->is_expense)
            return $this->expenseRow($row);

        return $row;
    }

    /**
     * Формирование данных строки прихода
     * 
     * @param  \App\Models\CashboxTransaction $row
     * @return \App\Models\CashboxTransaction
     */
    public function incomeRow(CashboxTransaction $row)
    {
        $row->source = $this->getSource($row->income_source_id);

        $row->name = $row->source->name ?? $row->name;

        $purpose = Purposes::collect()->where('id', $row->purpose_pay)->values()->all()[0] ?? null;

        $row->parking = $this->getParking($row->income_source_parking_id);

        if ($row->parking and $purpose) {
            $purpose['name'] = $purpose['name'] . " " . $row->parking->parking_place;
        }

        $row->purpose = $purpose;

        $row->sub_comment = $row->comment;

        if ($row->source and $row->income_source_service_id and $row->purpose_pay != 6) {
            $row->comment = $row->source->services->where('id', $row->income_source_service_id)->all()[0]->name ?? null;
        } else if ($row->purpose_pay == 6 and $row->income_source_service_id) {

            $row->comment = AdditionalService::find($row->income_source_service_id)->name ?? null;

            if ($row->sub_comment) {
                $row->comment = trim((string) $row->comment . " (" . $row->sub_comment . ")");
            }
        }

        return $row;
    }

    /**
     * Формирование данных строки расхода
     * 
     * @param  \App\Models\CashboxTransaction $row
     * @return \App\Models\CashboxTransaction
     */
    public function expenseRow(CashboxTransaction $row)
    {
        $row->expense_type = $this->getExpenseType($row->expense_type_id);

        $row->expense_subtype = $this->getExpenseSubType(
            $row->expense_type_id,
            $row->expense_subtype_id,
            $row->expense_type->type_subtypes ?? null
        );

        if ($row->expense_subtype->name ?? null) {
            $row->comment = $row->name;
            $row->name = $row->expense_subtype->name;
        }

        if ($row->expense_type) {
            $row->purpose = [
                'name' => $row->expense_type->name,
                'color' => "orange",
                'icon' => $row->expense_type->icon ?? null,
            ];
        }

        return $row;
    }

    /**
     * Поиск источников дохода
     * 
     * @param  null|int $id
     * @return \App\Models\IncomeSource|null
     */
    public function getSource($id)
    {
        if (empty($this->get_source))
            $this->get_source = [];

        if (isset($this->get_source[$id]))
            return $this->get_source[$id];

        return $this->get_source[$id] = IncomeSource::find($id);
    }

    /**
     * Поиск данных парковочных мест
     * 
     * @param  null|int $id
     * @return \App\Models\IncomeSourceParking|null
     */
    public function getParking($id)
    {
        if (!$id)
            return null;

        if (empty($this->get_parking))
            $this->get_parking = [];

        if (isset($this->get_parking[$id]))
            return $this->get_parking[$id];

        return $this->get_parking[$id] = IncomeSourceParking::find($id);
    }

    /**
     * Поиск раздела типов расхода
     * 
     * @param  null|int $id
     * @return \App\Models\ExpenseType|null
     */
    public function getExpenseType($id)
    {
        if (!$id)
            return null;

        if (empty($this->get_expense_type))
            $this->get_expense_type = [];

        if (isset($this->get_expense_type[$id]))
            return $this->get_expense_type[$id];

        return $this->get_expense_type[$id] = ExpenseType::find($id);
    }

    /**
     * Поиск подразделов типов 
     * 
     * @param  null|int $type_id
     * @param  null|int $sub_type_id
     * @param  null|string $type_subtypes
     * @return \App\Models\ExpenseSubtype|null
     */
    public function getExpenseSubType($type_id, $sub_type_id, $type_subtypes)
    {
        if (!$type_id)
            return null;

        if (empty($this->get_expense_sub_type))
            $this->get_expense_sub_type = [];

        if (isset($this->get_expense_sub_type[$type_id][$sub_type_id]))
            return $this->get_expense_sub_type[$type_id][$sub_type_id];

        if ($type_subtypes == "users") {

            if (!$user = Employee::find($sub_type_id))
                return $this->get_expense_sub_type[$type_id][$sub_type_id] = null;

            $name = ((string) $user->surname) . " ";
            $name .= ((string) $user->name) . " ";
            $name .= (string) $user->middle_name;

            $row = new ExpenseSubtype;
            $row->id = $sub_type_id;
            $row->expense_type_id = $type_id;
            $row->name = trim($name);
            $row->created_at = $user->created_at;
            $row->updated_at = $user->updated_at;

            return $this->get_expense_sub_type[$type_id][$sub_type_id] = $row;
        }

        return $this->get_expense_sub_type[$type_id][$sub_type_id] = ExpenseSubtype::find($sub_type_id);
    }

    /**
     * Вывод строки
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        if ($request->id and !$row = CashboxTransaction::find($request->id))
            return response()->json(['message' => "Информация не найдена"], 400);

        if (!($row ?? null))
            $row = new CashboxTransaction;

        $expense_types = ExpenseType::lazy();
        $expense_subtypes = [];

        if ($row->expense_type_id ?? null) {

            $expense_type = $expense_types->where('id', $row->expense_type_id)->values()->all()[0] ?? null;

            if (!$expense_type)
                $expense_type = ExpenseType::find($row->expense_type_id);

            if ($expense_type) {
                $expense_subtypes = (new Types)->getSubTypesList(new Request(['id' => $row->expense_type_id]))->getData();
            }
        }

        $income_sources = IncomeSource::withTrashed()
            ->where([
                ['is_free', false],
                ['deleted_at', null]
            ])
            ->when((bool) $row->income_source_id ?? null, function ($query) use ($row) {
                $query->orWhere('id', $row->income_source_id);
            })
            ->orderBy('name')
            ->lazy();

        if ($row->income_source_id ?? null) {

            $income_source = $income_sources->where('id', $row->income_source_id)->values()->all()[0] ?? null;

            if (!$income_source)
                $income_source = ExpenseType::find($row->income_source_id);

            if ($income_source->is_parking) {
                $income_source_parkings = IncomeSourceParking::where('source_id', $income_source->id)
                    ->get()
                    ->map(function ($row) {
                        return [
                            'text' => "Парковка №{$row->parking_place} ($row->car)",
                            'value' => $row->id,
                        ];
                    });
            }
        }

        return response()->json([
            'row' => $row ?? [],
            'expense_types' => $expense_types->map(function ($row) {
                return ['text' => $row->name, 'value' => $row->id];
            }),
            'expense_subtypes' => $expense_subtypes,
            'income_sources' => $income_sources,
            'income_source_parkings' => $income_source_parkings ?? [],
            'income_source_services' => (new AdditionalServices)->list($request, true, true),
            'purpose' => Purposes::getAll(),
            'purpose_salary' => Salaries::getPurposeSalariesOptions(),
        ]);
    }

    public function serviceList(Request $request, AdditionalServices $services)
    {
        return $services->list($request, true);
    }
}
