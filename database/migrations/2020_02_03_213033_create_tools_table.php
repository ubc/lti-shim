<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateToolsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tools', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('client_id', 1024)->unique();
            $table->text('oidc_login_url')
                  ->comment('URL for the first request to start LTI launch.');
            $table->text('auth_resp_url')
                  ->comment('URL for the final request to finish LTI launch.');
            // target_link_uri might differ based on deployment instead of by
            // tool, but putting it in tool for now until we know more
            $table->text('target_link_uri')
                  ->comment('If launch success, user redirected to this URL.');
            $table->text('jwks_url')->nullable()
                  ->comment("Where to get the tool's public keys.");

            // These are actually conditionally required fields. If the tool
            // supports LTI services, then they need to be filled in.
            $table->text('iss')->nullable()
                  ->comment("OAuth issuer, usually just the tool's URL.");

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
        Schema::dropIfExists('tools');
    }
}
