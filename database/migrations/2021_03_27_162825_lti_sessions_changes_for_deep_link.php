<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LtiSessionsChangesForDeepLink extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lti_sessions', function (Blueprint $table) {
            // since we're creating a session entry during the OIDC login, a lot
            // of columns in lti_sessions are unknown and hence needs to be
            // nullable
            $table->unsignedBigInteger('deployment_id')->nullable()->change();
            $table->unsignedBigInteger('course_context_id')->nullable()
                                                           ->change();
            $table->unsignedBigInteger('lti_real_user_id')->nullable()
                                                          ->change();
            // this was previously stored in the state JWE since session didn't
            // exist when we need it, but now that we're creating a session
            // from the start, we can store it in the table instead. Note that
            // we need a 2 step process to make this a foreign key to avoid
            // getting an error on migration about existing data having null
            // for this field.
            $table->unsignedBigInteger('platform_client_id')->nullable();
        });

        // shouldn't be enough deployments or platform_clients to need chunking
        $deployments = DB::table('deployments')->get();
        $deploymentIdToPlatformId = $deployments->mapWithKeys(
            function($deployment) {
                return [$deployment->id => $deployment->platform_id];
            }
        );

        $toolIdAndPlatformIdToPlatformClientId = [];
        $clients = DB::table('platform_clients')->get();
        foreach ($clients as $client) {
            $toolIdAndPlatformIdToPlatformClientId[$client->tool_id][$client->platform_id] = $client->id;
        }

        // now set a value for platform_client_id for all existing rows
        $this->initializePlatformClientIds(
            $deploymentIdToPlatformId,
            $toolIdAndPlatformIdToPlatformClientId
        );

        // now that all existing rows should have a value for the new
        // platform_client_id column, we can turn it into a non-nullable
        // foreign key
        Schema::table('lti_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('platform_client_id')->nullable(false)
                                                            ->change();
            $table->foreign('platform_client_id')->references('id')
                  ->on('platform_clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lti_sessions', function (Blueprint $table) {
            $table->dropForeign(['platform_client_id']);

            $table->unsignedBigInteger('deployment_id')->nullable(false)
                                                       ->change();
            $table->unsignedBigInteger('course_context_id')->nullable(false)
                                                           ->change();
            $table->unsignedBigInteger('lti_real_user_id')->nullable(false)
                                                          ->change();
        });
    }

    /**
     * Set a value for the new platform_client_id column for existing rows in
     * lti_sessions.
     */
    private function initializePlatformClientIds(
        $deploymentIdToPlatformId,
        $toolIdAndPlatformIdToPlatformClientId
    ) {
        // there could be a lot of lti_sessions, so should chunk it
        DB::table('lti_sessions')->chunkById(
            100,
            function (
                $sessions
            ) use (
                $deploymentIdToPlatformId,
                $toolIdAndPlatformIdToPlatformClientId
            ) {
                $updates = [];
                foreach ($sessions as $session) {
                    $platformId =
                        $deploymentIdToPlatformId[$session->deployment_id];
                    $platformClientId =
                        $toolIdAndPlatformIdToPlatformClientId[$session->tool_id][$platformId];
                    $session->platform_client_id = $platformClientId;
                    $updates[] = (array)$session;
                }
                DB::table('lti_sessions')->upsert(
                    $updates,
                    ['id'],
                    ['platform_client_id']
                );
            }
        );
    }
}
