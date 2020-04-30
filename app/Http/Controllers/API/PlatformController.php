<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use App\Http\Controllers\Controller;
use App\Models\Platform;


class PlatformController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Platform::getAllEditable();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $newPlatform = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'iss' => ['required', 'string', 'unique:platforms'],
            'auth_req_url' => ['required', 'string', 'url'],
            'jwks_url' => ['string', 'url', 'nullable'],
            'clients' => ['array'],
            'keys' => ['array'],
            'clients.*.client_id' => ['required', 'string', 'max:255'],
            'keys.*.kid' => ['required', 'string', 'max:1024'],
            'keys.*.key' => ['required'],
        ]);
        $platform = Platform::create($newPlatform);
        $platform->clients()->createMany($newPlatform['clients']);
        $platform->keys()->createMany($newPlatform['keys']);
        return $this->show($platform->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Platform::find($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $platform = Platform::find($id);
        $info = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'iss' => ['required', 'string',
                Rule::unique('platforms')->ignore($platform)],
            'auth_req_url' => ['required', 'string', 'url'],
            'jwks_url' => ['string', 'url', 'nullable'],
            'clients' => ['array'],
            'keys' => ['array'],
            'clients.*.client_id' => ['required', 'string', 'max:255'],
            'keys.*.kid' => ['required', 'string', 'max:1024'],
            'keys.*.key' => ['required'],
        ]);
        $platform->updateWithRelations($info);
        return $platform;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return Platform::destroy($id);
    }
}
