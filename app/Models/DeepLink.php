<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use UBC\LTI\Utils\EncryptedState;
use UBC\LTI\Utils\LtiException;

class DeepLink extends Model
{
    use HasFactory;

    public function deployment()
    {
        return $this->belongsTo('App\Models\Deployment');
    }

    public function tool()
    {
        return $this->belongsTo('App\Models\Tool');
    }

    public function getShimReturnUrlAttribute()
    {
        return route('lti.launch.deepLinkReturn', ['deepLink' => $this->id]);
    }

    /**
     * Store the DeepLink's id inside an encrypted JWT.
     */
    public function createEncryptedId(): string
    {
        return EncryptedState::encrypt(['deepLinkId' => $this->id]);
    }

    /**
     * Retrieve the DeepLink entry based on the id stored inside the
     * encrypted JWT.
     */
    public static function decodeEncryptedId(string $token): self
    {
        $jwt = EncryptedState::decrypt($token);
        $sessionId = $jwt->claims->get('deepLinkId');
        if (!$sessionId) {
            throw new LtiException('Missing Deep Link');
        }
        $session = self::find($sessionId);
        if (!$sessionId) {
            throw new LtiException('Invalid Deep Link');
        }
        return $session;
    }

    /**
     * Try to retrieve the DeepLink with the given info, if not found, create
     * an entry with the given info.
     */
    public static function createOrGet(
        string $returnUrl,
        int $deploymentId,
        int $toolId,
        string $state = null
    ): self {
        $dl = self::where([
            'return_url' => $returnUrl,
            'deployment_id' => $deploymentId,
            'tool_id' => $toolId,
            'state' => $state
        ])->first();
        if (!$dl) {
            $dl = new self;
            $dl->return_url = $returnUrl;
            $dl->deployment_id = $deploymentId;
            $dl->tool_id = $toolId;
            $dl->state = $state;
            $dl->save();
        }
        return $dl;
    }
}
