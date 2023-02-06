<?php

namespace App\Observers;

use App\Http\Controllers\Employees\Salaries\Salaries;
use App\Models\CashboxTransaction;
use Exception;
use Illuminate\Support\Facades\Log;

class CashboxTransactionsObserver
{
    /**
     * Handle the CashboxTransaction "created" event.
     *
     * @param  \App\Models\CashboxTransaction  $row
     * @return void
     */
    public function created(CashboxTransaction $row)
    {
        if ($row->expense_type_id == 1) {
            $this->recountDutyUser($row->expense_subtype_id, $row->month);
        }
    }

    /**
     * Handle the CashboxTransaction "updated" event.
     *
     * @param  \App\Models\CashboxTransaction  $row
     * @return void
     */
    public function updated(CashboxTransaction $row)
    {
        if ($row->expense_type_id == 1) {
            $this->recountDutyUser($row->expense_subtype_id, $row->month);
        }
    }

    /**
     * Handle the CashboxTransaction "deleted" event.
     *
     * @param  \App\Models\CashboxTransaction  $row
     * @return void
     */
    public function deleted(CashboxTransaction $row)
    {
        if ($row->expense_type_id == 1) {
            $this->recountDutyUser($row->expense_subtype_id, $row->month);
        }
    }

    /**
     * Пересчитать остатки
     * 
     * @param  int  $emplouee_id
     * @param  string  $month
     * @return void
     */
    public function recountDutyUser($employee_id, $month)
    {
        try {
            Salaries::recountDutyUser($employee_id, $month);
        } catch (Exception $e) {
            \Log::error("DUTY {$employee_id} ERROR " . $e->getMessage(), $e->getTrace());
        }
    }
}
