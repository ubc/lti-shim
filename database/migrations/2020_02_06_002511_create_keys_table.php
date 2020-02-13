<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Couldn't figure out a simple way for platform and tool to share a keys
        // table that preserves the relations. Easiest alternative was to just
        // have two keys table, one each for platform and tool.
        Schema::create('platform_keys', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('kid', 1024)
                  ->comment('Identifies the key in a JWK set.');
            $table->json('key')
                  ->comment('JWK of the RSA key, can include private key.');
            $table->unsignedBigInteger('platform_id');
            $table->foreign('platform_id')->references('id')->on('platforms')
                  ->onDelete('cascade');

            $table->index(['kid', 'platform_id']);

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('tool_keys', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('kid', 1024)
                  ->comment('Identifies the key in a JWK set.');
            $table->json('key')
                  ->comment('JWK of the RSA key, can include private key.');
            $table->unsignedBigInteger('tool_id');
            $table->foreign('tool_id')->references('id')->on('tools')
                  ->onDelete('cascade');

            $table->index(['kid', 'tool_id']);

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
        Schema::dropIfExists('platform_keys');
        Schema::dropIfExists('tool_keys');
    }
}
