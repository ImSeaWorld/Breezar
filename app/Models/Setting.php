<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when settings are updated
        static::saved(function ($setting) {
            Cache::forget('settings');
            Cache::forget('settings.' . $setting->key);
        });

        static::deleted(function ($setting) {
            Cache::forget('settings');
            Cache::forget('settings.' . $setting->key);
        });
    }

    /**
     * Get decrypted value based on type
     */
    public function getDecryptedValueAttribute()
    {
        if ($this->type === 'encrypted' && $this->value) {
            try {
                return Crypt::decryptString($this->value);
            } catch (\Exception $e) {
                return $this->value; // Return raw value if decryption fails
            }
        }

        if ($this->type === 'boolean') {
            return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->type === 'json' && $this->value) {
            return json_decode($this->value, true);
        }

        return $this->value;
    }

    /**
     * Set value with encryption if needed
     */
    public function setValueAttribute($value)
    {
        if ($this->type === 'encrypted' && $value) {
            $this->attributes['value'] = Crypt::encryptString($value);
        } elseif ($this->type === 'json' && is_array($value)) {
            $this->attributes['value'] = json_encode($value);
        } elseif ($this->type === 'boolean') {
            $this->attributes['value'] = $value ? 'true' : 'false';
        } else {
            $this->attributes['value'] = $value;
        }
    }

    /**
     * Get setting value by key with caching
     */
    public static function get($key, $default = null)
    {
        return Cache::remember('settings.' . $key, 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->decrypted_value : $default;
        });
    }

    /**
     * Set setting value by key
     */
    public static function set($key, $value, $type = 'string')
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->type = $type;
        $setting->value = $value;
        $setting->save();

        return $setting;
    }

    /**
     * Get all settings grouped by group
     */
    public static function getAllGrouped()
    {
        return Cache::remember('settings', 3600, function () {
            return static::all()->groupBy('group')->map(function ($group) {
                return $group->mapWithKeys(function ($setting) {
                    return [$setting->key => [
                        'value' => $setting->decrypted_value,
                        'type' => $setting->type,
                        'description' => $setting->description,
                    ]];
                });
            });
        });
    }

    /**
     * Get settings by group
     */
    public static function getByGroup($group)
    {
        return static::where('group', $group)->get()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->decrypted_value];
        });
    }
}
