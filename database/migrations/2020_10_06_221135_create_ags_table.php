<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // create table for tracking Assignment and Grade Service
        // requests
        //
        // the lineitems url is advertised in the launch message
        Schema::create('ags', function (Blueprint $table) {
            $table->id();

            // the original endpoint on the original platform
            // lineitems is required to be present if AGS is enabled
            $table->text('lineitems')
                  ->comment('Original AGS lineitems on the original platform.');
            // the advertised scopes available for this AGS endpoint
            $table->json('scopes')->default(json_encode([]));

            // we need to know what's the platform and tool pairing of this ags
            // endpoint
            $table->unsignedBigInteger('course_context_id');
            $table->unsignedBigInteger('deployment_id');
            $table->unsignedBigInteger('tool_id');
            $table->foreign('course_context_id')->references('id')
                  ->on('course_contexts')->onDelete('cascade');
            $table->foreign('deployment_id')->references('id')
                  ->on('deployments')->onDelete('cascade');
            $table->foreign('tool_id')->references('id')->on('tools')
                  ->onDelete('cascade');

            $table->unique([
                'lineitems',
                'course_context_id',
                'deployment_id',
                'tool_id'
            ]);

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        // each lineitem has their own url, this table tracks those
        Schema::create('ags_lineitems', function (Blueprint $table) {
            $table->id();

            // lineitem is used only when the resource link points to only one
            // item, so it could be empty
            $table->text('lineitem')
                  ->comment('Original AGS lineitem on the original platform.');

            // we need to know what's the platform and tool pairing of this ags
            // endpoint
            $table->unsignedBigInteger('ags_id');
            $table->foreign('ags_id')->references('id')
                  ->on('ags')->onDelete('cascade');

            $table->unique([
                'lineitem',
                'ags_id'
            ]);

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        // each lineitem has a list of results, and each result has their own
        // url, this tracks individual result's url
        Schema::create('ags_results', function (Blueprint $table) {
            $table->id();

            $table->text('result')
                  ->comment('Original AGS result url on the original platform.');

            $table->unsignedBigInteger('ags_lineitems_id');
            $table->foreign('ags_lineitems_id')->references('id')
                  ->on('ags_lineitems')->onDelete('cascade');

            $table->unique([
                'result',
                'ags_lineitems_id'
            ]);

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
        Schema::dropIfExists('ags_results');
        Schema::dropIfExists('ags_lineitems');
        Schema::dropIfExists('ags');
    }
}
