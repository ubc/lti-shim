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
        Schema::create('lti_real_users', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('login_hint', 1024)
                  ->comment('Real login_hint received from the platform.');
            $table->string('name', 1024)->nullable()
                  ->comment('Real name received from the platform.');
            $table->string('email', 1024)->nullable()
                  ->comment('Real email received from the platform.');

            $table->string('sub', 1024)
                  ->comment("The real 'sub' param in the id_token JWT, since it might differ from login_hint.");
            $table->string('non_lti_id', 1024)->nullable()->unique()
                  ->comment('Optional ID for other university systems since login_hint/sub are LTI platform specific.');

            // users should be platform specific
            $table->unsignedBigInteger('platform_id');
            $table->foreign('platform_id')->references('id')->on('platforms')
                  ->onDelete('cascade');

            // we're going to do a lot of lookups via login_hint and sub
            $table->unique(['login_hint', 'platform_id']);
            $table->unique(['sub', 'platform_id']);

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });
        // each real user will have a lot of fake users, so we split fake info
        // off to its own table to avoid duplicating real user info
        Schema::create('lti_fake_users', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('login_hint', 1024)
                  ->comment('Fake login_hint we should send to tools.');
            $table->string('name', 1024)
                  ->comment('Fake name we should send to tools.');
            $table->string('email', 1024)
                  ->comment('Fake email we should send to tools.');

            // link to a real user
            $table->unsignedBigInteger('lti_real_user_id');
            $table->foreign('lti_real_user_id')->references('id')
                  ->on('lti_real_users')->onDelete('cascade');
            // user should have a different fake id on every tool
            $table->unsignedBigInteger('tool_id');
            $table->foreign('tool_id')->references('id')->on('tools')
                  ->onDelete('cascade');

            // we're going to do a lot of lookups via these
            $table->unique(['lti_real_user_id', 'tool_id']);
            // prevent duplicates
            $table->unique(['login_hint', 'tool_id']);

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
        Schema::dropIfExists('lti_fake_users');
        Schema::dropIfExists('lti_real_users');
    }
}
