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
            // urls technically can be very long, so using text instead of string
            $table->text('iss')->unique()
                  ->comment("OAuth issuer, usually just the platform's URL.");
            $table->text('auth_req_url')
                  ->comment('Platform URL where we send authentication request.');
            $table->text('jwks_url')->nullable()
                  ->comment("Where to get the platform's public keys.");

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
