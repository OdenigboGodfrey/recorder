<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFundTrackerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fund_trackers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id');
            $table->string('type');
            $table->string('title');
            $table->longText('category')->default("undefined");
            $table->longText('description')->nullable();
            $table->integer('status')->default(\App\Utilities\Utility::$neutral);
            $table->integer('amount');
            $table->string('date_created');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */

    public function down()
    {
        Schema::dropIfExists('fund_trackers');
    }
}
