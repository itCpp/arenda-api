<?php

namespace App\Http\Controllers\Employees\Salaries;

use App\Http\Controllers\Employees;
use App\Models\Employee;

trait Users
{
    /**
     * Формирует список сотрудников
     * 
     * @return $this
     */
    public function getUsers()
    {
        Employee::withTrashed()
            ->select(
                'employees.*',
                'employee_work_dates.work_start',
                'employee_work_dates.work_stop'
            )
            ->leftjoin('employee_work_dates', 'employee_work_dates.employee_id', '=', 'employees.id')
            ->where(function ($query) {
                $query->where('employee_work_dates.work_start', '<=', request()->stop)
                    ->orWhere('employee_work_dates.work_start', null);
            })
            ->orderBy('employee_work_dates.work_start')
            ->distinct()
            ->get()
            ->each(function ($row) use (&$rows) {
                $rows[$row->id] = Employees::employee($row);
            });

        foreach ($rows ?? [] as $row)
            $users[] = $row;

        $this->data['users'] = $users ?? [];

        return $this;
    }
}