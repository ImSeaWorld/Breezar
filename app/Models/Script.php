<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Script extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'tinker_code',
        'created_by',
        'last_run_at',
    ];

    protected $casts = [
        'last_run_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function executions()
    {
        return $this->hasMany(ScriptExecution::class);
    }

    public function getLastExecutionAttribute()
    {
        return $this->executions()->latest('executed_at')->first();
    }
}
