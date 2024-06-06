<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Shops extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id')->nullable(false);
            $table->string('shop_domain')->nullable(false);
            $table->string('shop_name')->nullable(false);
            $table->string('shop_token')->nullable(false);
            $table->string('shop_hmac')->nullable(false);
            $table->string('shop_owner_name')->nullable(false);
            $table->string('email')->nullable(false);
            $table->integer('install_time')->nullable(false)->default(0);
            $table->integer('uninstall_time')->nullable(false)->default(0);
            $table->integer('status')->nullable(false)->default(1);
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
        Schema::dropIfExists('shops');
    }
}
