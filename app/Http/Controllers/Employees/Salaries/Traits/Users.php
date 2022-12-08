<?php

namespace App\Http\Controllers\Employees\Salaries\Traits;

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
            ->when((bool) request()->id, function ($query) {
                $query->where('employees.id', request()->id);
            })
            ->when(!(bool) request()->id, function ($query) {
                $query->where(function ($query) {
                    $query->where('employee_work_dates.work_start', '<=', request()->stop)
                        ->orWhere('employee_work_dates.work_start', null);
                })
                    ->where(function ($query) {
                        $query->where('employee_work_dates.work_stop', '>=', request()->start)
                            ->orWhere('employee_work_dates.work_stop', null);
                    });
            })
            ->whereHidden(false)
            ->orderBy('employee_work_dates.work_start')
            ->distinct()
            ->get()
            ->each(function ($row) use (&$rows) {
                $rows[$row->id] = Employees::employee($row);
            });

        foreach ($rows ?? [] as $row) {
            $users_id[] = $row->id;
            $users[] = $row;
        }

        $this->data['users'] = $users ?? [];
        $this->data['data']['ids'] = array_values(array_unique($users_id ?? []));

        return $this;
    }
}
