<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJurnalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jurnal', function (Blueprint $table) {
            $table->uuid('jurnal_id')->primary(); // Use UUID for jurnal_id
            $table->string('comp_id'); // Use UUID for comp_id and make it unique
            $table->string('account_code');
            $table->string('financial_type');
            $table->integer('amount');
            $table->date('transaction_date');
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jurnal');
    }
}
