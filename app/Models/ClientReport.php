<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'type',
        'report_month',
        'data',
        'generated_at',
    ];

    protected $casts = [
        'data' => 'array',
        'report_month' => 'date',
        'generated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeWorkItems($query)
    {
        return $query->where('type', 'work_items');
    }

    public function scopeCustom($query)
    {
        return $query->where('type', 'custom');
    }
}
