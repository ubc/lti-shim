<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use UBC\LTI\EncryptedState;
use UBC\LTI\LTIException;
use UBC\LTI\Param;

class LtiSession extends Model
{
    // need to tell Laravel to auto decode our JSON column
    protected $casts = [
        'token' => 'array'
    ];

    public static function getSession($request): self
    {
        if (!$request->has(Param::LTI_MESSAGE_HINT)) {
            throw new LTIException('No LTI session found.');
        }
        $state = EncryptedState::decrypt(
            $request->input(Param::LTI_MESSAGE_HINT));
        $ltiSession = self::find($state->claims->get('lti_session'));
        if (!$ltiSession) {
            // TODO: actually expire sessions
            throw new LTIException('Invalid LTI session, is it expired?');
        }
        return $ltiSession;
    }

    public function deployment()
    {
        return $this->belongsTo('App\Models\Deployment');
    }
    public function lti_real_user()
    {
        return $this->belongsTo('App\Models\LtiRealUser');
    }
    public function tool()
    {
        return $this->belongsTo('App\Models\Tool');
    }
}
