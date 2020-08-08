<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlatformClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // This is for when the shim acts like a tool, the platform would've
        // given us a client_id to use. The spec allows for multiple client_id
        // so I've split it off here just in case. I suspect most implementations
        // would have only a single client_id though.
        Schema::create('platform_clients', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('platform_id');
            $table->foreign('platform_id')->references('id')->on('platforms')
                ->onDelete('cascade');
            $table->unsignedBigInteger('tool_id');
            $table->foreign('tool_id')->references('id')->on('tools')
                ->onDelete('cascade');

            $table->string('client_id')
                  ->comment("The shim's client_id on this platform.");

            // prevent duplicate tools from being inserted on each platform
            $table->unique(['platform_id', 'tool_id']);
            // client_id must be unique per platform
            $table->unique(['platform_id', 'client_id']);

            // using just the standard timestampsTz() to create these pair of
            // timestamps doesn't give them database defaults
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
        Schema::dropIfExists('platform_clients');
    }
}
