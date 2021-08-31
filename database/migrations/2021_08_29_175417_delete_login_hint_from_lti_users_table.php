<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteLoginHintFromLtiUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lti_fake_users', function (Blueprint $table) {
            $table->dropColumn('login_hint');
        });
        Schema::table('lti_real_users', function (Blueprint $table) {
            $table->dropColumn('login_hint');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lti_real_users', function (Blueprint $table) {
            $table->string('login_hint', 1024)->nullable()
                  ->comment('Real login_hint received from the platform.');
        });
        Schema::table('lti_fake_users', function (Blueprint $table) {
            $table->string('login_hint', 1024)->nullable()
                  ->comment('Fake login_hint we should send to tools.');
        });
    }
}
