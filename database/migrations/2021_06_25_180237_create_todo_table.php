<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTodoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $randGenerator = new \App\Utilities\RandomGenerator();
        Schema::create('todos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id');
            $table->string('category')->nullable();
            $table->string('title');
            $table->timestamp('due_date')->nullable();
            $table->longText('description')->nullable();
            $table->integer('constraint_personal')->default(3);
            $table->integer('constraint_value')->default(randGenerator::generate_random_number(1,3));
            $table->integer('constraint_urgency')->default(randGenerator::generate_random_number(1,3));
            $table->integer('constraint_importance')->default(randGenerator::generate_random_number(1,3));
            $table->integer('status')->default(\App\Utilities\Utility::$neutral);
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
        Schema::dropIfExists('todos');
    }
}
