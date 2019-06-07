<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('customer')->default('0');
            $table->text('name');
            $table->text('email');
            $table->text('city');
            $table->text('address');
            $table->string('mobile', 255);
            $table->text('products');
            $table->decimal('shipping', 11, 2)->default('0.00');
            $table->decimal('summ', 11, 2)->default('0.00');
            $table->text('date');
            $table->integer('time');
            $table->integer('stat')->default('1');
            $table->text('country');
            $table->text('payment');
            $table->timestamps();
            $table->unique(["id"]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}