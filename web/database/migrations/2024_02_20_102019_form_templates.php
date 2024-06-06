<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FormTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false);
            $table->string('img')->nullable(false);
            $table->string('img_url')->nullable(false);
            $table->integer('layout')->nullable(false)->default(0);
            $table->longText('form_body_html')->nullable(false);
            $table->longText('form_element_html')->nullable(false);
            $table->longText('form_submit_html')->nullable(false);
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
        Schema::dropIfExists('form_templates');
    }
}
