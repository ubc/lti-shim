<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStateToLtiSessions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lti_sessions', function (Blueprint $table) {
            $table->json('state')->comment('params that needs to be persisted for use in later steps')->default('[]');
            // now that token is not initialized on login, it needs a default
            // value or db will complain that it's null
            $table->json('token')->default('[]')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lti_sessions', function (Blueprint $table) {
            $table->dropColumn('state');
            $table->json('token')->default(null)->change();
        });
    }
}
