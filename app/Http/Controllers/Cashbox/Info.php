<?php

namespace App\Http\Controllers\Cashbox;

use App\Http\Controllers\Controller;
use App\Models\CashboxTransaction;
use Illuminate\Http\Request;

class Info extends Controller
{
    /**
     * Выводит информацию по кассе
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        CashboxTransaction::selectRaw('sum(sum) sum, IFNULL(type_pay, 1) type_pay, is_income, purpose_pay')
            ->where(function ($query) {
                $query->where('type_pay', null)
                    ->orWhereIn('type_pay', [1, 4]);
            })
            ->where('date', '>=', "2022-10-01")
            ->groupBy(['type_pay', 'is_income', 'purpose_pay'])
            ->get()
            ->each(function ($row) use (&$data) {

                if (!$row->is_income) {
                    if (in_array($row->purpose_pay, [4, 5])) {
                        $row->sum = 0;
                    }
                }

                $key = (int) $row->type_pay;

                if (!isset($data[$key])) {
                    $data[$key] = [
                        'sum' => 0,
                        'type_pay' => $key,
                    ];
                }

                $data[$key]['sum'] += $row->sum;

                // if ($key == 3)
                //     $data[$key]['sum'] += 185069.88;
            });

        CashboxTransaction::selectRaw('sum(sum) sum, IFNULL(type_pay, 1) type_pay, is_income, purpose_pay')
            ->where(function ($query) {
                $query->where('type_pay', '!=', null)
                    ->whereNotIn('type_pay', [1, 4]);
            })
            ->where('date', '>=', "2023-01-01")
            ->groupBy(['type_pay', 'is_income', 'purpose_pay'])
            ->get()
            ->each(function ($row) use (&$data) {

                if (!$row->is_income) {
                    if (in_array($row->purpose_pay, [4, 5])) {
                        $row->sum = 0;
                    }
                }

                $key = (int) $row->type_pay;

                if (!isset($data[$key])) {
                    $data[$key] = [
                        'sum' => 0,
                        'type_pay' => $key,
                    ];
                }

                $data[$key]['sum'] += $row->sum;

                if ($key == 3)
                    $data[$key]['sum'] += 93260.16;
            });

        return response()->json([
            'info' => collect($data ?? [])
                ->map(function ($row) {
                    $row['sum'] = round($row['sum'], 2);
                    return $row;
                })
                ->sortBy('type_pay')
                ->values()
                ->all()
        ]);
    }
}
