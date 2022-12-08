<?php

namespace App\Http\Controllers\Employees\Salaries;

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
        if ($request->has('period')) {

            $period = now()->create($request->period);

            if ((int) $period->format("j") >= 16) {
                $start = now()->create($request->period ?: now())->format("Y-m-16");
                $stop = now()->create($request->period ?: now())->format("Y-m-t");
            } else {
                $start = now()->create($request->period ?: now())->format("Y-m-01");
                $stop = now()->create($request->period ?: now())->format("Y-m-15");
            }
        } else {
            $start = now()->create($request->month ?: now())->startOfMonth()->format("Y-m-d");
            $stop = now()->create($request->month ?: now())->endOfMonth()->format("Y-m-d");
        }

        $days = now()->create($start)->subDay()->diff($stop)->days ?? 0;

        $request->merge([
            'month' => now()->create($request->month ?: now())->format("Y-m"),
            'period' => now()->create($request->period ?: now())->format("Y-m-d"),
            'start' => $start,
            'stop' => $stop,
            'days' => $days,
        ]);

        return response()->json(
            (new static)->get($request)
        );
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

        return $this->data;
    }

    /**
     * Пересчет остатков
     * 
     * @param  int  $user_id
     * @param  string  $date
     * @return null
     */
    public static function recountDutyUser($user_id, $date)
    {
        
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
