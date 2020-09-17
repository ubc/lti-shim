<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTitleLabelToCourseContext extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('course_contexts', function (Blueprint $table) {
            $table->text('title')->nullable()->
                comment('Course title from the LTI course context');
            $table->text('label')->nullable()->
                comment('Course label from the LTI course context');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('course_context', function (Blueprint $table) {
            $table->dropColumn('title');
            $table->dropColumn('label');
        });
    }
}
