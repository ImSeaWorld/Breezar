<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScriptExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'script_id',
        'executed_by',
        'client_id',
        'output',
        'executed_at',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
    ];

    public function script()
    {
        return $this->belongsTo(Script::class);
    }

    public function executor()
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
