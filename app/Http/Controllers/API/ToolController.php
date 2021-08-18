<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Tool;

class ToolController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Tool::getAllEditable();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $newTool = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'string', 'max:1024'],
            'oidc_login_url' => ['required', 'string', 'url'],
            'auth_resp_url' => ['required', 'string', 'url'],
            'target_link_uri' => ['required', 'string'],
            'jwks_url' => ['string', 'url', 'nullable'],
            'keys' => ['array'],
            'keys.*.kid' => ['required', 'string', 'max:1024'],
            'keys.*.key' => ['required'],
            'enable_midway_lookup' => ['required', 'boolean'],
        ]);
        $tool = Tool::create($newTool);
        $tool->keys()->createMany($newTool['keys']);
        return $tool;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Tool::find($id);
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
        $tool = Tool::find($id);
        $info = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'string', 'max:1024'],
            'oidc_login_url' => ['required', 'string', 'url'],
            'auth_resp_url' => ['required', 'string', 'url'],
            'target_link_uri' => ['required', 'string'],
            'jwks_url' => ['string', 'url', 'nullable'],
            'keys' => ['array'],
            'keys.*.kid' => ['required', 'string', 'max:1024'],
            'keys.*.key' => ['required'],
            'enable_midway_lookup' => ['required', 'boolean'],
        ]);
        $tool->updateWithRelations($info);
        return $tool;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Tool::destroy($id);
    }
}
