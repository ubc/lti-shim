<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeploymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('lti_deployment_id', 1024)
                  ->comment('Not a foreign key, this is the LTI deployment ID.');
            $table->string('fake_lti_deployment_id', 1024)->nullable()
                  ->comment('Filtered LTI deployment ID we give to tools.');
            $table->unsignedBigInteger('tool_id');
            $table->unsignedBigInteger('platform_id');

            $table->foreign('tool_id')->references('id')->on('tools')
                  ->onDelete('cascade');
            $table->foreign('platform_id')->references('id')->on('platforms')
                  ->onDelete('cascade');
            // we can't make lti_deployment_id by itself unique, specs say it is
            // only unique within each iss, so needs to be a multicolumn index
            $table->index(['lti_deployment_id', 'platform_id']);
            $table->index(['fake_lti_deployment_id', 'platform_id']);

            // using just the standard timestampsTz() to create these pair of
            // timestamps doesn't give them database defaults
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
        Schema::dropIfExists('deployments');
    }
}
