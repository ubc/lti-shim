<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNrpsTable extends Migration
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
        Schema::create('nrps', function (Blueprint $table) {
            $table->id();

            // the original endpoint on the original platform
            $table->text('context_memberships_url')
                  ->comment('Original NRPS endpoint on the original platform.');

            // we need to know what's the platform and tool pairing of this nrps
            // endpoint
            $table->unsignedBigInteger('deployment_id');
            $table->unsignedBigInteger('tool_id');
            $table->foreign('deployment_id')->references('id')
                  ->on('deployments')->onDelete('cascade');
            $table->foreign('tool_id')->references('id')->on('tools')
                  ->onDelete('cascade');

            $table->unique([
                'context_memberships_url',
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
        Schema::dropIfExists('nrps');
    }
}
