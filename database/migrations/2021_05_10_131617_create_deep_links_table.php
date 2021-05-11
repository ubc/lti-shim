<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeepLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deep_links', function (Blueprint $table) {
            $table->id();

            $table->text('return_url')
                  ->comment('Return URL on the originating platform.');
            $table->text('state')->nullable()
                  ->comment("The 'data' claim, opaque state for the platform.");

            // what platform and tool is this for
            $table->unsignedBigInteger('deployment_id');
            $table->unsignedBigInteger('tool_id');
            $table->foreign('deployment_id')->references('id')
                  ->on('deployments')->onDelete('cascade');
            $table->foreign('tool_id')->references('id')->on('tools')
                  ->onDelete('cascade');

            $table->unique([
                'return_url',
                'deployment_id',
                'tool_id',
                'state'
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
        Schema::dropIfExists('deep_links');
    }
}
