<?php

namespace App\Http\Controllers\Employees\Salaries\Traits;

use App\Models\Employee;

trait Result
{
    /**
     * Подсчитывает результаты
     * 
     * @return $this
     */
    public function getResult()
    {
        $this->data['rows'] = collect($this->data['users'] ?? [])
            ->sortBy([
                ["job_title", "asc"],
                ["fullname", "asc"],
            ])
            ->map(function ($row) {
                return $this->getRowResult($row);
            })
            ->values()
            ->all();

        return $this;
    }

    /**
     * Формирование итоговой строки
     * 
     * @param  \App\Models\Employee  $row
     * @return \App\Models\Employee
     */
    public function getRowResult(Employee $row)
    {
        $this->addedOtherData($row);

        /** К выплате */
        $row->toPayoff = $this->getToPayoff($row);

        /** Остаток получки */
        $row->balance = round($row->toPayoff - $row->prepayment + $row->duty);

        /** Долг на конец периода */
        $row->duty_now = $row->balance - $row->duty;

        /** Уволенный сотрудник */
        $row->is_fired = ($row->work_stop and $row->work_stop > request()->start);

        return $row;
    }

    /**
     * Дополнение всяческими данными
     * 
     * @param  \App\Models\Employee  $row
     * @return \App\Models\Employee
     */
    public function addedOtherData(Employee &$row)
    {
        /** График работы */
        $row->shedule = $this->data['data']['shedule'][$row->id] ?? [];

        $row->parts = $this->getSalaryParts($row);

        /** Выплаченные довольствия за период */
        $row->prepayment = $this->data['data']['prepayments'][$row->id] ?? 0;

        /** Выданные премии */
        $row->premium = $this->data['data']['premiums'][$row->id] ?? 0;

        /** Долги за предыдущие периоды */
        $row->duty = round($this->data['data']['duties'][$row->id] ?? 0, 2);

        return $row;
    }

    /**
     * Получает Размер части оклада по его истории
     * 
     * @param  \App\Models\Employee  $row
     * @return \App\Models\Employee
     */
    public function getSalaryPartAllDays(&$row)
    {
        $days = request()->days ?: 30;

        $salary = $this->data['data']['salary_first'][$row->id]['salary'] ?? 0;
        $is_one_day = $this->data['data']['salary_first'][$row->id]['is_one_day'] ?? false;

        for ($i = request()->start; $i <= request()->stop; $i = now()->create($i)->addDay()->format("Y-m-d")) {

            $salary = $this->data['data']['salary'][$row->id][$i]['salary'] ?? $salary;
            $is_one_day = is_bool($this->data['data']['salary'][$row->id][$i]['is_one_day'] ?? null)
                ? $this->data['data']['salary'][$row->id][$i]['is_one_day']
                : $is_one_day;

            $part = $is_one_day ? $salary : $salary / $days;

            $parts[$i] = $part;
            $parts_one_day[$i] = $is_one_day;
        }

        $row->parts_salaries = $parts ?? [];
        $row->parts_salaries_one_day = $parts_one_day ?? [];

        if ($first = ($this->data['data']['salary_first'][$row->id] ?? null)) {
            $row->salary = $first['salary'] ?? $row->salary;
            $row->salary_one_day = $first['salary_one_day'] ?? $row->salary_one_day;
            $row->salary_date = $first['salary_date'] ?? $row->salary_date;
        }

        foreach ($this->data['data']['salary'][$row->id] ?? [] as $salary) {

            if ($salary['start_date'] == request()->start)
                continue;

            $salary_story[] = [
                'date' => $salary['start_date'],
                'salary' => $salary['salary'],
                'salary_one_day' => $salary['is_one_day'],
            ];
        }

        $row->salary_story = $salary_story ?? null;

        return $row;
    }

    /**
     * Расчет ежедневной части оклада
     * 
     * @param  \App\Models\Employee  $row
     * @return array
     */
    public function getSalaryParts(Employee &$row)
    {
        $days = request()->days ?: 30;
        $date = now()->create(request()->start);
        $part = $row->salary_one_day ? $row->salary : $row->salary / $days;

        $this->getSalaryPartAllDays($row);

        for ($i = 1; $i <= $days; $i++) {

            $key = $date->copy()->setDay($i)->format("Y-m-d");

            $is_one_day = isset($row->parts_salaries_one_day[$key])
                ? $row->parts_salaries_one_day[$key]
                : $row->salary_one_day;

            $salary_part = isset($row->parts_salaries[$key])
                ? $row->parts_salaries[$key]
                : ($is_one_day ? 0 : $part);

            $parts[$i] = $is_one_day ? 0 : $salary_part;
            $parts_one_day[$i] = $is_one_day;
        }

        foreach ($row->shedule ?? [] as $day => $data) {

            $day = (int) $day;
            $is_one_day = $parts_one_day[$i] ?? $row->salary_one_day;
            $part = $parts[$i] ?? $part;

            /** Рабочий день */
            if ($data['type'] == 1) {
                $parts[$day] = $part;
            }
            /** Неполный рабочий день */
            else if ($data['type'] == 2) {
                $parts[$day] = $part / 2;
            }
            /** Выходной */
            else if ($data['type'] == 3) {
                $parts[$day] = 0;
            }
            /** Неполный выходной */
            else if ($data['type'] == 4) {
                $parts[$day] = $part / 2;
            }
            /** Переработка */
            else if ($data['type'] == 5) {
                $parts[$day] = $part * 2;
            }
            /** По графику */
            else if (!$is_one_day) {
                $parts[$day] = 0;
            }
        }

        $row->ss = $ss ?? null;

        $start_day = $row->work_start ? now()->create($row->work_start) : null;
        $stop_day = $row->work_stop ? now()->create($row->work_stop) : null;

        /** Корректировка по дате начала и окончания работы */
        foreach ($parts ?? [] as $day => $part) {

            if ($start_day and $start_day > now()->create(request()->month)->setDay($day))
                $parts[$day] = 0;

            if ($stop_day and $stop_day < now()->create(request()->month)->setDay($day))
                $parts[$day] = 0;
        }

        $row->parts_data = collect($parts ?? [])->map(function ($part, $day) {
            return [
                'date' => now()->create(request()->month)->setDay($day)->format("Y-m-d"),
                'sum' => $part,
            ];
        })->values()->all();

        return $parts ?? [];
    }

    /**
     * Расчет ежедневной части оклада
     * 
     * @param  \App\Models\Employee  $row
     * @return int
     */
    public function getToPayoff(Employee $row)
    {
        $toPayoff = 0;

        foreach ($row->parts ?? [] as $part) {
            $toPayoff += $part;
        }

        return round($toPayoff, 2);
    }
}
