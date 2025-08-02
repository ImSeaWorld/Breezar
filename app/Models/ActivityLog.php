<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resource()
    {
        return $this->morphTo();
    }

    public static function log($action, $resource = null, $metadata = [])
    {
        // Handle case where resource_type and resource_id are passed as separate params (legacy)
        if (is_string($resource) && is_numeric($metadata)) {
            // Old format: log($action, $resource_type, $resource_id, $metadata)
            // Convert to new format
            $args = func_get_args();
            return self::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'resource_type' => $resource, // The string type
                'resource_id' => $metadata,    // The numeric ID
                'metadata' => $args[3] ?? [],  // The actual metadata array
            ]);
        }
        
        // New format: log($action, $resource_object, $metadata)
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'resource_type' => $resource ? get_class($resource) : null,
            'resource_id' => $resource ? $resource->id : null,
            'metadata' => $metadata,
        ]);
    }
}
