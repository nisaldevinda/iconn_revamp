<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    protected $table = 'user';
    public $timestamps = false;
    use Authenticatable, Authorizable, HasFactory;

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'email','emailVerified','firstName', 'middleName','lastName',
        'fullName', 'nickname','password','inactive', 'blocked','expired',
        'lastIp', 'lastLogin','lastFailedLogin','lastPasswordReset', 'loginsCount',
        'failedLoginsCount', 'multifactor','phoneNumber','phoneVerified', 'picture','identities',
        'userMetadata', 'createdAt','createdBy','updatedAt','updatedBy', 
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];
}