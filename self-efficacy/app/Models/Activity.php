<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Activity extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'name_en', 'phase', 'category', 'description', 'icon', 'difficulty', 'voice_enabled', 'active',
    ];

    protected $casts = [
        'voice_enabled' => 'boolean',
        'active' => 'boolean',
    ];

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function scopeForPhase($query, int $phase)
    {
        return $query->where('phase', $phase)->where('active', true);
    }
}
