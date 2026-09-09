<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $guarded = [];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_online' => 'datetime',
    ];

    protected $appends = [
        'name'
    ];

    public function getNameAttribute()
    {
        return trim($this->f_name . ' ' . ($this->l_name ?? ''));
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function get_role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function hasRole($role)
    {
        return $this->role->name === $role;
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id')
                    ->orWhere('receiver_id', $this->id);
    }

    public function jobRequests()
    {
        return $this->hasMany(JobRequest::class, 'executive_id');
    }

    public function groups()
    {
        return $this->belongsToMany(\App\Models\Group::class, 'group_user');
    }

    public function groupMessageReads()
    {
        return $this->hasMany(GroupMessageRead::class);
    }
}
