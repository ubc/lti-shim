<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use UBC\LTI\Utils\EncryptedState;
use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\Param;

class LtiSession extends Model
{
    use HasFactory;

    // need to tell Laravel to auto decode our JSON column
    protected $casts = [
        'token' => 'array'
    ];

    public static function getSession($request): self
    {
        if (!$request->has(Param::LTI_MESSAGE_HINT)) {
            throw new LtiException('No LTI session found.');
        }
        $state = EncryptedState::decrypt(
            $request->input(Param::LTI_MESSAGE_HINT));
        $ltiSession = self::with(['tool', 'deployment'])->
            find($state->claims->get('lti_session'));
        if (!$ltiSession) {
            // TODO: actually expire sessions
            throw new LtiException('Invalid LTI session, is it expired?');
        }
        return $ltiSession;
    }

    public function course_context()
    {
        return $this->belongsTo('App\Models\CourseContext');
    }
    public function deployment()
    {
        return $this->belongsTo('App\Models\Deployment');
    }
    public function lti_real_user()
    {
        return $this->belongsTo('App\Models\LtiRealUser');
    }
    public function platform_client()
    {
        return $this->belongsTo('App\Models\PlatformClient');
    }
    public function tool()
    {
        return $this->belongsTo('App\Models\Tool');
    }
}
