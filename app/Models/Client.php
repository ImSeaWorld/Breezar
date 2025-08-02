<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_info',
        'fly_org_id',
        'fly_api_token',
        'fly_token_type',
        'fly_token_expires_at',
        'status',
        'billing_start_date',
    ];

    protected $casts = [
        'contact_info' => 'array',
        'billing_start_date' => 'date',
        'fly_token_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'fly_api_token', // Hide token from JSON responses
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function instances()
    {
        return $this->hasMany(ClientInstance::class);
    }

    public function reports()
    {
        return $this->hasMany(ClientReport::class);
    }

    public function scriptExecutions()
    {
        return $this->hasMany(ScriptExecution::class);
    }

    public function getSqlInstanceAttribute()
    {
        return $this->instances()->where('type', 'sql')->first();
    }

    public function getSasInstanceAttribute()
    {
        return $this->instances()->where('type', 'sas')->first();
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Get decrypted Fly API token
     */
    public function getDecryptedFlyApiTokenAttribute()
    {
        if (!$this->fly_api_token) {
            return null;
        }

        try {
            return Crypt::decryptString($this->fly_api_token);
        } catch (\Exception $e) {
            return $this->fly_api_token; // Return raw if decryption fails
        }
    }

    /**
     * Set encrypted Fly API token
     */
    public function setFlyApiTokenAttribute($value)
    {
        if ($value) {
            $this->attributes['fly_api_token'] = Crypt::encryptString($value);
        } else {
            $this->attributes['fly_api_token'] = null;
        }
    }

    /**
     * Check if token is expired
     */
    public function isFlyTokenExpired()
    {
        if (!$this->fly_token_expires_at) {
            return false; // No expiration set
        }

        return $this->fly_token_expires_at->isPast();
    }
}
