<?php
namespace Budgetcontrol\Authtentication\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{

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

    //relations with workspaces
    public function workspaces()
    {
        return $this->hasMany(Workspace::class);
    }
}