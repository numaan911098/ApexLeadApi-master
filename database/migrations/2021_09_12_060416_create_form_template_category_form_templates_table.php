<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormTemplateCategoryFormTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_template_category_form_templates', function (Blueprint $table) {
            $table->bigInteger('form_category_id')->unsigned();
            $table->bigInteger('form_template_id')->unsigned();
            $table->boolean('is_primary_category');
            $table->foreign('form_category_id')
                ->references('id')
                ->on('form_template_categories')
                ->onDelete('cascade');
            $table->foreign('form_template_id')
                ->references('id')
                ->on('form_templates')
                ->onDelete('cascade');
            $table->primary(['form_category_id', 'form_template_id'], 'category_table_primary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_template_category_form_templates');
    }
}
