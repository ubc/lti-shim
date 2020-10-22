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
        // create table for tracking Names and Role Provisioning Service
        // requests
        Schema::create('ags', function (Blueprint $table) {
            $table->id();

            // the original endpoint on the original platform
            // lineitems is required to be present if AGS is enabled
            $table->text('lineitems')
                  ->comment('Original AGS lineitems on the original platform.');
            // lineitem is used only when the resource link points to only one
            // item, so it could be empty
            $table->text('lineitem')->nullable()
                  ->comment('Original AGS lineitem on the original platform.');
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
                'lineitem',
                'course_context_id',
                'deployment_id',
                'tool_id'
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
        Schema::dropIfExists('ags');
    }
}
