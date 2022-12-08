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
        Schema::table('additional_services', function (Blueprint $table) {
            $table->boolean('is_one')->default(false)->after('icon');
        });

        Schema::table('cashbox_transactions', function (Blueprint $table) {
            $table->string('comment')->nullable()->after('income_source_service_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('additional_services', function (Blueprint $table) {
            $table->dropColumn('is_one');
        });

        Schema::table('cashbox_transactions', function (Blueprint $table) {
            $table->dropColumn('comment');
        });
    }
};
