<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnonymizationIndicatorsToLtiFakeUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lti_fake_users', function (Blueprint $table) {
            $table->boolean('enable_first_time_setup')->default(true)
                  ->comment('Whether this identity needs first time setup, basically for configuring is_anonymized');
            $table->boolean('is_anonymized')->default(true)
                  ->comment('Whether this user chose to anonymize this identity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lti_fake_users', function (Blueprint $table) {
            $table->dropColumn('is_anonymized');
            $table->dropColumn('enable_first_time_setup');
        });
    }
}
