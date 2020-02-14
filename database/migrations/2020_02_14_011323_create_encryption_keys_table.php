<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEncryptionKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // stores RSA keys for encrypting JWT used to pass state around without
        // using cookies
        Schema::create('encryption_keys', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->json('key')->comment('JWK of the RSA key.');

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
        Schema::dropIfExists('encryption_keys');
    }
}
