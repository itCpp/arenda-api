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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('requisites')->nullable();
            $table->string('contacts_name')->nullable();
            $table->string('contacts_phone')->nullable();
            $table->string('contacts_email')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('client_id')->nullable();
            $table->string('type')->nullable();
            $table->string('number')->index();
            $table->date('date');
            $table->date('date_start')->nullable();
            $table->date('date_stop')->nullable();
            $table->integer('day_payment')->nullable();
            $table->float('price', 10)->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('client_income_source', function (Blueprint $table) {
            $table->bigInteger('client_id');
            $table->bigInteger('income_source_id');
        });

        Schema::table('cashbox_transactions', function (Blueprint $table) {
            $table->bigInteger('client_id')->nullable()->after('income_source_service_id');
            $table->bigInteger('contract_id')->nullable()->after('client_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cashbox_transactions', function (Blueprint $table) {
            $table->dropColumn(['client_id', 'contract_id']);
        });

        Schema::dropIfExists('client_income_source');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('clients');
    }
};
