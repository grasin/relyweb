<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStyleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('style', function (Blueprint $table) {
            $table->text('slogan');
            $table->text('desc');
            $table->text('background');
            $table->text('button');
            $table->text('media');
            $table->text('css');
            $table->text('js');
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
        Schema::dropIfExists('style');
    }
}