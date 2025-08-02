<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'fly_app_id',
        'type',
        'region',
        'size',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeSql($query)
    {
        return $query->where('type', 'sql');
    }

    public function scopeSas($query)
    {
        return $query->where('type', 'sas');
    }

    public function isRunning()
    {
        return $this->status === 'running';
    }
}
