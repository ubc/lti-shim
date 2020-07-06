<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlatformsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('platforms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            // iss could technically be longer, but 1024 should be sufficient
            // for real world use cases, we need it to be a string for compat
            // with mysql, as mysql doesn't support indexed text fields
            $table->string('iss', 1024)->unique()
                  ->comment("OAuth issuer, usually just the platform's URL.");
            $table->text('auth_req_url')
                  ->comment('Platform URL where we send authentication request.');
            $table->text('jwks_url')->nullable()
                  ->comment("Where to get the platform's public keys.");
            // the shim's tool side needs to be registered on the platform,
            // the platform will give us a client_id to use
            $table->text('shim_client_id')->unique()
                  ->comment("Shim's OAuth client_id given by the platform.");

            // These are actually conditionally required fields. If the tool
            // supports LTI services, then they need to be filled in.
            $table->text('oauth_token_url')->nullable()
                  ->comment("Where to get OAuth2 token for LTI service calls.");

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
        Schema::dropIfExists('platforms');
    }
}
