<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    private const MIDWAY_API_USER = 'midway_api_user@example.com';

    /**
     * Automatically hash passwords when you set them.
     */
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::make($password);
    }

    /**
     * We want to use Sanctum's token abilities functions to restrict access
     * for a token to only the course context and tool they were launched.
     */
    public function getLookupAbility(int $courseContextId, int $toolId): string
    {
        return 'lookup:' . $courseContextId . ':' . $toolId;
    }

    /**
     * A Sanctum's token ability string that indicates this token is allowed
     * to set anonymization optiosn for the given LtiFakeUser.
     */
    public function getSelectAnonymizationAbility(int $ltiFakeUserId): string
    {
        return 'select:anonymization:' . $ltiFakeUserId;
    }

    public static function addUserIfNotExist(
        string $name,
        string $email,
        string $password
    ): self {
        $user = User::firstWhere('email', $email);
        if ($user) return $user; # user already exists
        # user does not exist, create one
        $user = new User;
        $user->name = $name;
        $user->email = $email;
        $user->password = $password;
        $user->save();
        return $user;
    }

    public static function getMidwayApiUser(): self
    {
        return self::addUserIfNotExist('Midway API User',
            self::MIDWAY_API_USER, Str::random(40));
    }
}
