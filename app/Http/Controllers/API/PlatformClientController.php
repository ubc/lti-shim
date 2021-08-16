<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;
use App\Models\Platform;
use App\Models\PlatformClient;
use App\Models\Tool;

class PlatformClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Platform $platform)
    {
        return $platform->clients()->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Platform $platform)
    {
        $newPlatformClient = $request->validate([
            'platform_id' => ['required', 'integer', 'exists:platforms,id',
                'in:' . $platform->id],
            'tool_id' => ['required', 'integer', 'exists:tools,id'],
            'client_id' => ['required', 'string', 'max:255']
        ]);
        $platformClient = PlatformClient::create($newPlatformClient);
        return $platformClient;
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Platform $platform, PlatformClient $client)
    {
        return $client;
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(
        Request $request,
        Platform $platform,
        PlatformClient $client
    ) {
        $info = $request->validate([
            'platform_id' => ['required', 'integer', 'exists:platforms,id'],
            'tool_id' => ['required', 'integer', 'exists:tools,id'],
            'client_id' => ['required', 'string', 'max:255']
        ]);
        $client->update($info);
        return $client;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Platform $platform, PlatformClient $client)
    {
        return $client->delete();
    }
}
