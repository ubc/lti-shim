<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnUrlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('return_urls', function (Blueprint $table) {
            $table->id();

            $table->text('url')
                  ->comment('The original url we should redirect to.');
            $table->string('token', 1024)
                  ->comment('Really basic security check.');

            // probably won't need this info, but just in case, we'd want
            // to know what created this return url
            $table->unsignedBigInteger('course_context_id');
            $table->unsignedBigInteger('deployment_id');
            $table->unsignedBigInteger('tool_id');
            $table->foreign('course_context_id')->references('id')
                  ->on('course_contexts')->onDelete('cascade');
            $table->foreign('deployment_id')->references('id')
                  ->on('deployments')->onDelete('cascade');
            $table->foreign('tool_id')->references('id')->on('tools')
                  ->onDelete('cascade');

            $table->unique([
                'url',
                'course_context_id',
                'deployment_id',
                'tool_id'
            ]);

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('return_urls');
    }
}
