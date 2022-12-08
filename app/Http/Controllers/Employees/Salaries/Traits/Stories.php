<?php

namespace App\Http\Controllers\Employees\Salaries\Traits;

use App\Models\EmployeeSalary;

trait Stories
{
    /**
     * Получает историю пользователей
     * 
     * @return $this
     */
    public function getUserStory()
    {
        foreach ($this->data['data']['ids'] ?? [] as $id) {

            $story = EmployeeSalary::whereEmployeeId($id)
                ->where('start_date', '<=', request()->start)
                ->orderBy('start_date', "DESC")
                ->first();

            if ($story) {
                $this->data['data']['salary_first'][$id] = [
                    'salary' => $story->salary,
                    'is_one_day' => $story->is_one_day,
                    'start_date' => $story->start_date,
                ];
            }
        }

        EmployeeSalary::whereBetween('start_date', [request()->start, request()->stop])
            ->when(is_array($this->data['data']['ids'] ?? null), function ($query) {
                $query->whereIn('employee_id', $this->data['data']['ids']);
            })
            ->orderBy('start_date')
            ->each(function ($row) {
                $this->data['data']['salary'][$row->employee_id][$row->start_date] = $row->toArray();
            });

        return $this;
    }
}
