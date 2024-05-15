<?php
namespace Budgetcontrol\Authtentication\Domain\Model;

use Budgetcontrol\Authtentication\Traits\Crypt;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use Crypt;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'id'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = $this->encrypt($value);
    }

    public function getEmailAttribute($value)
    {
        return $this->decrypt($value);
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $this->encrypt($value);
    }

    public function getPasswordAttribute($value)
    {
        return $this->decrypt($value);
    }
}