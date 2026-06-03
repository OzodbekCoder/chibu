<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'telegraph_chats';

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'password' => 'hashed',
    ];

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'created_by_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'created_by_id');
    }
}
