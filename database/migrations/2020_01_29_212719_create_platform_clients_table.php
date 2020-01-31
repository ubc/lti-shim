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
        Schema::create('platform_clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('platform_id');
            $table->foreign('platform_id')->references('id')->on('platforms')
                ->onDelete('cascade');
            $table->string('client_id');

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
