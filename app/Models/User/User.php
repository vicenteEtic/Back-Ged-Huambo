<?php

namespace App\Models\User;

use App\Models\Permission\Role;
use App\Notifications\CustomResetPassword;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Model implements
    AuthenticatableContract,
    CanResetPassword
{
    use Authenticatable, Authorizable, MustVerifyEmail, HasApiTokens,  Notifiable, CanResetPasswordTrait;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'is_active',
        'role_id',
        'email_verified_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_code',
        'two_factor_expires_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_expires_at' => 'datetime',
    ];


    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function can($rule)
    {
        $permissions = $this->role->permissions->contains('name', $rule);
        if ($permissions) {
            return true;
        }
        return false;
    }

    public function hasPermissions($names)
    {
        $results = $this->permissions()
            ->whereIn('name', $names)
            ->pluck('name')
            ->toArray() ?? [];

        $perms = [];

        foreach ($names as $key => $name) {
            $perms[$key] = in_array($name, $results);
        }

        return $perms;
    }


    public function cans($rule)
    {
        $permissions = $this->permissions;
        foreach ($permissions as $permission) {
            if ($permission->name == $rule) {
                return true;
            }
        }
        return false;
    }

    // Gera código de 6 dígitos e define validade de 10 min
    public function generateTwoFactorCode()
    {
        $this->two_factor_code = rand(100000, 999999);
        $this->two_factor_expires_at = now()->addMinutes(10);
        $this->save();
    }

    // Verifica se o código é válido
    public function validateTwoFactorCode($code)
    {
        return $this->two_factor_code === $code && $this->two_factor_expires_at->isFuture();
    }

    // Limpa código após uso
    public function resetTwoFactorCode()
    {
        $this->two_factor_code = null;
        $this->two_factor_expires_at = null;
        $this->save();
    }
   
    
}
