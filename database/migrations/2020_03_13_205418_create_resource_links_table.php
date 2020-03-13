<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResourceLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resource_links', function (Blueprint $table) {
            $table->bigIncrements('id');

            // spec limits the id to 255 ASCII characters
            $table->string('real_link_id', 255)
                  ->comment('The real resource link ID in the original launch');
            $table->string('fake_link_id', 255)->nullable()
                  ->comment('The fake resource link ID we forward.');

            // LTI links are associated with deployments according to spec
            $table->unsignedBigInteger('deployment_id');
            $table->foreign('deployment_id')->references('id')
                  ->on('deployments')->onDelete('cascade');

            $table->unique(['real_link_id', 'deployment_id']);
            $table->unique(['fake_link_id', 'deployment_id']);

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
        Schema::dropIfExists('resource_links');
    }
}
