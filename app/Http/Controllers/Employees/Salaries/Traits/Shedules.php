<?php

namespace App\Http\Controllers\Employees\Salaries\Traits;

use App\Models\EmployeeShedule;

trait Shedules
{
    /**
     * Подсчитывает результаты
     * 
     * @return $this
     */
    public function getShedules()
    {
        EmployeeShedule::where('month', request()->month ?: now()->format("Y-m"))
            ->each(function ($row) use (&$shedule) {
                $shedule[$row->employee_id] = $row->days ?: [];
            });

        $this->data['data']['shedule'] = $shedule ?? [];

        return $this;
    }
}
