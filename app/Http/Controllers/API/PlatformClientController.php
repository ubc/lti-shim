<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\PlatformClient;

class PlatformClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return PlatformClient::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $newPlatformClient = $request->validate([
            'platform_id' => ['required', 'integer', 'exists:platforms,id'],
            'tool_id' => ['required', 'integer', 'exists:tools,id'],
            'client_id' => ['required', 'string', 'max:255']
        ]);
        $platformClient = PlatformClient::create($newPlatformClient);
        return $platformClient;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(PlatformClient $platformClient)
    {
        return $platformClient;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PlatformClient $platformClient)
    {
        $info = $request->validate([
            'platform_id' => ['required', 'integer', 'exists:platforms,id'],
            'tool_id' => ['required', 'integer', 'exists:tools,id'],
            'client_id' => ['required', 'string', 'max:255']
        ]);
        $platformClient->update($info);
        return $platformClient;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(PlatformClient $platformClient)
    {
        return $platformClient->delete();
    }
}
