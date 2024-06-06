<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FormInstallations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_installations', function (Blueprint $table) {
            $table->id();
            $table->integer('form_id')->nullable(false);
            $table->integer('page_type')->nullable(false)->default(0);
            $table->string('page_id')->nullable(false);
            $table->string('theme_id')->nullable(false);
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
        Schema::dropIfExists('form_installations');
    }
}
