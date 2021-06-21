<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnableMidwayLookupToTools extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tools', function (Blueprint $table) {
            // config to allow instructors access to the user lookup tool
            $table->boolean('enable_midway_lookup')->default(false)
                  ->comment('Allow a stop midway during launch to lookup fake/real user mappings.');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->dropColumn('enable_midway_lookup');
        });
    }
}
