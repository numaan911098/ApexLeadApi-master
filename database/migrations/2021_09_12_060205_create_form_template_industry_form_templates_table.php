<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormTemplateIndustryFormTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_template_industry_form_templates', function (Blueprint $table) {
            $table->bigInteger('form_industry_id')->unsigned();
            $table->bigInteger('form_template_id')->unsigned();
            $table->foreign('form_industry_id')
                ->references('id')
                ->on('form_template_industries')
                ->onDelete('cascade');
            $table->foreign('form_template_id')
                ->references('id')
                ->on('form_templates')
                ->onDelete('cascade');
            $table->primary(['form_industry_id', 'form_template_id'], 'industry_table_primary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_template_industry_form_templates');
    }
}
