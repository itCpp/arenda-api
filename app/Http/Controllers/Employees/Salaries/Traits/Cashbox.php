<?php

namespace App\Http\Controllers\Employees\Salaries\Traits;

use App\Http\Controllers\Employees\Salaries;
use App\Models\CashboxTransaction;

trait Cashbox
{
    /**
     * Подсчитывает результаты
     * 
     * @return $this
     */
    public function getPrepayments()
    {
        CashboxTransaction::selectRaw('sum(sum) as sum, expense_subtype_id as user_id')
            ->whereIsExpense(true)
            ->whereExpenseTypeId(1)
            ->where(function ($query) {
                $query->where('month', now()->create(request()->start ?: now())->format("Y-m"));
            })
            ->where(function ($query) {
                $query->whereIn('purpose_pay', Salaries::salaryCountPaysIds())
                    ->orWhere('purpose_pay', null);
            })
            ->groupBy('expense_subtype_id')
            ->get()
            ->each(function ($row) use (&$prepayments) {
                $prepayments[$row->user_id] = abs($row->sum);
            });

        $this->data['data']['prepayments'] = $prepayments ?? [];

        CashboxTransaction::selectRaw('sum(sum) as sum, expense_subtype_id as user_id')
            ->whereIsExpense(true)
            ->whereExpenseTypeId(1)
            ->where(function ($query) {
                $query->where('month', now()->create(request()->start ?: now())->format("Y-m"));
            })
            ->where(function ($query) {
                $query->whereNotIn('purpose_pay', Salaries::salaryCountPaysIds())
                    ->where('purpose_pay', '!=', null);
            })
            ->groupBy('expense_subtype_id')
            ->get()
            ->each(function ($row) use (&$premiums) {
                $premiums[$row->user_id] = abs($row->sum);
            });

        $this->data['data']['premiums'] = $premiums ?? [];

        return $this;
    }
}
