<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLtiUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // maps real user to fake id
        Schema::create('lti_users', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('real_login_hint', 1024)
                  ->comment('Real login_hint received from the platform.');
            $table->string('real_name', 1024)->nullable()
                  ->comment('Real name received from the platform.');
            $table->string('real_email', 1024)->nullable()
                  ->comment('Real email received from the platform.');
            $table->string('fake_login_hint', 1024)
                  ->comment('Fake login_hint we should send to tools.');
            $table->string('fake_name', 1024)
                  ->comment('Fake name we should send to tools.');
            $table->string('fake_email', 1024)
                  ->comment('Fake email we should send to tools.');

            $table->string('sub', 1024)
                  ->comment("The real 'sub' param in the id_token JWT, since it might differ from login_hint.");
            $table->string('non_lti_id', 1024)->nullable()->unique()
                  ->comment('Optional ID for other university systems since login_hint/sub are LTI platform specific.');

            // user should have a different fake id on every tool, we track this
            // by deployment
            $table->unsignedBigInteger('deployment_id');
            $table->foreign('deployment_id')->references('id')->on('deployments')
                  ->onDelete('cascade');

            // we're going to do a lot of lookups via login_hint and sub
            $table->unique(['real_login_hint', 'deployment_id']);
            $table->unique(['sub', 'deployment_id']);
            $table->unique(['fake_login_hint', 'deployment_id']);

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
        Schema::dropIfExists('lti_users');
    }
}