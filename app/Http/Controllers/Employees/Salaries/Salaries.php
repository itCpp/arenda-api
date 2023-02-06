<?php

namespace App\Http\Controllers\Employees\Salaries;

use App\Models\EmployeeDuty;
use Illuminate\Http\Request;

class Salaries
{
    use Traits\Cashbox,
        Traits\Duties,
        Traits\Result,
        Traits\Shedules,
        Traits\Stories,
        Traits\Users;

    /**
     * Данные на вывод
     * 
     * @var array
     */
    protected $data = [];

    /**
     * Инициализация объекта
     * 
     * @return void
     */
    public function __construct()
    {
        $this->getUsers()
            ->getUserStory()
            ->getShedules()
            ->getDuties()
            ->getPrepayments()
            ->getResult();
    }

    /**
     * Вывод данных получки
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function index(Request $request)
    {
        $request = self::prepareDatesRequestDate($request);

        return response()->json(
            (new static)->get($request)
        );
    }

    /**
     * Формирует данные с датами
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Request
     */
    public static function prepareDatesRequestDate(Request $request)
    {
        // if ($request->has('period')) {

        //     $period = now()->create($request->period);

        //     if ((int) $period->format("j") >= 16) {
        //         $start = now()->create($request->period ?: now())->format("Y-m-16");
        //         $stop = now()->create($request->period ?: now())->format("Y-m-t");
        //     } else {
        //         $start = now()->create($request->period ?: now())->format("Y-m-01");
        //         $stop = now()->create($request->period ?: now())->format("Y-m-15");
        //     }
        // } else {
        //     $start = now()->create($request->month ?: now())->startOfMonth()->format("Y-m-d");
        //     $stop = now()->create($request->month ?: now())->endOfMonth()->format("Y-m-d");
        // }

        $start = now()->create($request->month ?: now())->startOfMonth()->format("Y-m-d");
        $stop = now()->create($request->month ?: now())->endOfMonth()->format("Y-m-d");

        $days = now()->create($start)->subDay()->diff(now()->create($stop))->days ?? 0;

        $request->merge([
            'month' => now()->create($request->month ?: now())->format("Y-m"),
            'period' => now()->create($request->period ?: now())->format("Y-m-d"),
            'start' => $start,
            'stop' => $stop,
            'days' => $days,
        ]);

        return $request;
    }

    /**
     * Расчет данных получки
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function get(Request $request)
    {
        $this->data['dates'] = [
            'month' => $request->month,
            'period' => $request->period,
            'start' => $request->start,
            'stop' => $request->stop,
            'days' => $request->days,
        ];

        if ($request->toDuty) {
            return $this->getDutyArray();
        }

        return $this->data;
    }

    /**
     * Пересчет остатков
     * 
     * @param  int  $user_id
     * @param  string  $month
     * @return null
     */
    public static function recountDutyUser($user_id, $month)
    {
        $request = request();
        $month = now()->create($month ?? now())->format("Y-m");

        $start = now()->create($month);

        \Log::debug("DUTY {$user_id} {$month}");

        while ($start < now()->startOfMonth()) {

            $request->merge([
                'id' => $user_id,
                'month' => $start->copy()->format("Y-m"),
                'toDuty' => true,
            ]);

            $request = self::prepareDatesRequestDate($request);

            $data = (new static)->get($request);

            foreach ($data as $row) {

                $m = now()->create($row['month'] ?? now())->startOfMonth()->format("Y-m-d");
                $p = now()->create($row['period'] ?? now())->startOfMonth()->format("Y-m-d");

                $duty = EmployeeDuty::firstOrCreate(
                    ['employee_id' => $row['id'], 'period' => $p, 'month' => $m],
                    ['money_first' => $row['duty_now']]
                );

                $duty->money = $row['duty_now'];
                $duty->save();

                \Log::debug("SALARY {$row['id']} {$m} DATA", $row);
            }

            $start->addMonth();
        }
    }

    /**
     * Выдает массив остатков
     * 
     * @param array
     */
    public function getDutyArray()
    {
        return collect($this->data['rows'] ?? [])
            ->map(fn ($item) => [
                'id' => $item['id'],
                'duty_now' => $item['duty_now'],
                'month' => $this->data['dates']['month'] ?? null,
                'period' => $this->data['dates']['start'] ?? null,
            ])
            ->toArray();
    }

    /**
     * Обработка несуществующего метода
     * 
     * @param mixed $name
     * @param mixed $arguments
     * @return $this
     */
    public function __call($name, $arguments)
    {
        return $this;
    }
}
