<?php

namespace App\Http\Controllers\Employees\Salaries\Traits;

use App\Models\EmployeeDuty;

trait Duties
{
    /**
     * Получает историю пользователей
     * 
     * @return $this
     */
    public function getDuties()
    {
        EmployeeDuty::selectRaw('sum(money) as money, employee_id')
            ->whereIn('employee_id', $this->data['data']['ids'] ?? [null])
            ->where('month', '<', request()->start)
            ->orderBy('employee_id')
            ->groupBy('employee_id')
            ->each(function ($row) {
                $this->data['data']['duties'][$row->employee_id] = $row->money;
            });

        return $this;
    }
}
