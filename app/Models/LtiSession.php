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

    // need to tell Laravel to auto decode our JSON columns
    protected $casts = [
        'state' => 'array',
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
            find($state['lti_session']);
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
    public function lti_fake_user()
    {
        return $this->belongsTo(
            'App\Models\LtiFakeUser',
            'lti_real_user_id',
            'lti_real_user_id',
        )->where([
            ['course_context_id', $this->course_context_id],
            ['tool_id', $this->tool_id],
        ]);
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

    public function getIsMidwayLookupOnlyAttribute(): bool
    {
        if (!isset($this->state[Param::SESSION_TYPE])) return false;
        return $this->state[Param::SESSION_TYPE] == Param::SESSION_TYPE_LOOKUP;
    }

    /**
     * Store the LtiSession's id inside an encrypted JWT.
     */
    public function createEncryptedId(): string
    {
        return EncryptedState::encrypt(['ltiSessionId' => $this->id]);
    }

    /**
     * Retrieve the LtiSession entry based on the id stored inside the
     * encrypted JWT.
     */
    public static function decodeEncryptedId(string $token): self
    {
        $state = EncryptedState::decrypt($token);
        $sessionId = $state['ltiSessionId'];
        if (!$sessionId) {
            throw new LtiException('Missing LTI Session');
        }
        $session = self::find($sessionId);
        if (!$sessionId) {
            throw new LtiException('Invalid LTI Session');
        }
        return $session;
    }
}
