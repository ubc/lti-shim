<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLtiSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lti_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->json('token')->comment('id_token payload');

            $table->unsignedBigInteger('deployment_id');
            $table->foreign('deployment_id')->references('id')
                  ->on('deployments')->onDelete('cascade');
            $table->unsignedBigInteger('tool_id');
            $table->foreign('tool_id')->references('id')->on('tools')
                  ->onDelete('cascade');
            $table->unsignedBigInteger('lti_real_user_id');
            $table->foreign('lti_real_user_id')->references('id')
                  ->on('lti_real_users')->onDelete('cascade');

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
        Schema::dropIfExists('lti_sessions');
    }
}
