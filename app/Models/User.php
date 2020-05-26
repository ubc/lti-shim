<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

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

    public function AuthRouteAPI(Request $request){
        return $request->user();
    }

    public static function addUserIfNotExist(
        string $name,
        string $email,
        string $password
    ) {
        $user = User::firstWhere('email', $email);
        if ($user) return; # user already exists
        # user does not exist, create one
        $user = new User;
        $user->name = $name;
        $user->email = $email;
        $user->password = $password;
        $user->save();
    }

    /**
     * Automatically hash passwords when you set them.
     */
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::make($password);
    }
}
