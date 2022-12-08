<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_duties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id');
            $table->float('money', 10)->default(0);
            $table->float('money_first', 10)->default(0);
            $table->float('money_payd', 10)->default(0);
            $table->date('month')->nullable();
            $table->date('period')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['employee_id', 'month', 'deleted_at']);
            $table->index(['employee_id', 'period', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_duties');
    }
};
