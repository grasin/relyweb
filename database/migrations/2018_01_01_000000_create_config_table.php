<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('config', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('registration');
            $table->text('theme');
            $table->integer('translations');
            $table->text('lang');
            $table->integer('views');
            $table->text('name');
            $table->text('email');
            $table->text('desc');
            $table->text('key');
            $table->text('logo');
            $table->integer('floating_cart');
            $table->text('tumblr');
            $table->text('youtube');
            $table->text('facebook');
            $table->text('instagram');
            $table->text('twitter');
            $table->text('phone');
            $table->text('address');
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
        Schema::dropIfExists('config');
    }
}