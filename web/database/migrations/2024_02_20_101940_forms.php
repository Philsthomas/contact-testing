<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Forms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false);
            $table->integer('template_id')->nullable(false);
            $table->integer('shop_id')->nullable(false);
            $table->text('fields')->nullable(false);
            $table->text('code')->nullable(false);
            $table->integer('status')->nullable(false)->default(1);
            $table->integer('after_submission')->nullable(false)->default(1);
            $table->string('redirect_url')->nullable(false);
            $table->string('thanks_message')->nullable(false);
            $table->string('submit_button_text')->nullable(false);
            $table->string('submit_button_class')->nullable(false); 
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
        Schema::dropIfExists('forms');
    }
}
