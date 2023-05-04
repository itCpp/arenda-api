<?php

namespace App\Http\Controllers\Cashbox;

use App\Http\Controllers\Controller;
use App\Models\CashboxTransaction;
use Illuminate\Http\Request;

trait Statistics
{
    /**
     * Формирует статистику по датам
     * 
     * @param  array|string $dates
     * @param  null|string $date_to
     * @return array
     */
    public function getStatistics($dates = [], $date_to = null)
    {
        $data = [];

        CashboxTransaction::selectRaw('sum(sum) as sum, type_pay, is_income, date, purpose_pay')
            ->when(is_array($dates), function ($query) use ($dates) {
                $query->whereIn('date', $dates);
            })
            ->when(is_string($dates) and is_string($date_to), function ($query) use ($dates, $date_to) {
                $query->whereBetween('date', [$dates, $date_to]);
            })
            ->groupBy(['type_pay', 'is_income', 'date', 'purpose_pay'])
            ->get()
            ->each(function ($row) use (&$data) {

                $date = now()->create($row->date)->format("Ymd");

                if (!isset($data[$date]))
                    $data[$date] = $this->getStatisticDateRow($row->date);

                $stat = &$data[$date];

                if ($row->is_income) {
                    if (in_array($row->purpose_pay, [4, 5])) {
                        $row->sum = 0;
                    }
                }

                // if ($row->is_income == false && $row->purpose_pay == 5) {
                //     $row->is_income = true;
                //     $row->is_expense = false;
                // }

                if ($row->is_income) {

                    $stat['incoming'] += $row->sum;

                    if ($row->type_pay == 2) {
                        $stat['incomingCard'] += $row->sum;
                    } elseif (!$row->type_pay or $row->type_pay == 1) {
                        $stat['incomingCash'] += $row->sum;
                    } elseif ($row->type_pay == 3) {
                        $stat['incomingCheckingAccount'] += $row->sum;
                    } elseif (!isset($stat['incomingType' . $row->type_pay])) {
                        $stat['incomingType' . $row->type_pay] = $row->sum;
                    } elseif (isset($stat['incomingType' . $row->type_pay])) {
                        $stat['incomingType' . $row->type_pay] += $row->sum;
                    }
                } elseif (!$row->is_income) {

                    $stat['expense'] += $row->sum;

                    if ($row->type_pay == 2) {
                        $stat['expenseCard'] += $row->sum;
                    } elseif (!$row->type_pay or $row->type_pay == 1) {
                        $stat['expenseCash'] += $row->sum;
                    } elseif ($row->type_pay == 3) {
                        $stat['expenseCheckingAccount'] += $row->sum;
                    } elseif (!isset($stat['expenseType' . $row->type_pay])) {
                        $stat['expenseType' . $row->type_pay] = $row->sum;
                    } elseif (isset($stat['expenseType' . $row->type_pay])) {
                        $stat['expenseType' . $row->type_pay] += $row->sum;
                    }
                }
            });

        return $data;
    }

    /**
     * Выводит массив данных статистики одного дня
     * 
     * @param  string $date
     * @return array
     */
    public function getStatisticDateRow($date)
    {
        return [
            'date' => $date,
            'incoming' => 0,
            'expense' => 0,
            'incomingCash' => 0,
            'expenseCash' => 0,
            'incomingCard' => 0,
            'expenseCard' => 0,
            'incomingCheckingAccount' => 0,
            'expenseCheckingAccount' => 0,
        ];
    }
}
