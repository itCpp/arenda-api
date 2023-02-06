<?php

namespace App\Console\Commands;

use App\Http\Controllers\Employees\Salaries\Salaries;
use App\Models\EmployeeDuty;
use Illuminate\Console\Command;

class EmployeeCountDuty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employee:duty {--month= : Отчетный месяц}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Подсчет остатков сотрудникам';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        request()->merge([
            'month' => $this->option('month') ?: null,
            'toDuty' => true,
        ]);

        $data = Salaries::index(request());

        if ($data instanceof \Illuminate\Http\JsonResponse) {
            $data = $data->getData(true);
        }

        $date = now()->format("Y-m-d H:i:s");

        foreach ($data as $row) {

            $month = now()->create($row['month'] ?? null)->startOfMonth()->format("Y-m-d");
            $period = now()->create($row['period'] ?? null)->startOfMonth()->format("Y-m-d");

            $this->line("[{$date}] {$period} ID:{$row['id']} {$row['duty_now']}");

            $duty = EmployeeDuty::firstOrCreate(
                ['employee_id' => $row['id'], 'period' => $period, 'month' => $month],
                ['money_first' => $row['duty_now']]
            );

            $duty->money = $row['duty_now'];
            $duty->save();
        }

        return 0;
    }
}
