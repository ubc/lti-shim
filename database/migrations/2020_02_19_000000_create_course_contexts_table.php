<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseContextsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_contexts', function (Blueprint $table) {
            $table->bigIncrements('id');
            // looks like they're restricting all claim IDs to 255 ASCII chars
            $table->string('real_context_id', 255)->comment('The original ID');
            $table->string('fake_context_id', 255)->nullable()
                  ->comment('We mask the original ID using this ID.');

            $table->unsignedBigInteger('tool_id');
            $table->foreign('tool_id')->references('id')->on('tools')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('deployment_id');
            $table->foreign('deployment_id')->references('id')->on('deployments')
                  ->onDelete('cascade');

            $table->unique(['real_context_id', 'fake_context_id']);
            $table->unique(['fake_context_id', 'tool_id']);

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
        Schema::dropIfExists('course_contexts');
    }
}
