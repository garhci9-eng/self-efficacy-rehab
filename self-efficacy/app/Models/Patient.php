<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Patient extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'age', 'ward', 'bed_number', 'diagnosis', 'mobility_level', 'notes',
    ];

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function efficacyAssessments()
    {
        return $this->hasMany(EfficacyAssessment::class);
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function wishTreeItems()
    {
        return $this->hasMany(WishTreeItem::class);
    }

    public function diaryEntries()
    {
        return $this->hasMany(DiaryEntry::class);
    }

    public function todayCompletedCount(): int
    {
        return $this->activityLogs()
            ->where('completed_at', today())
            ->where('status', 'completed')
            ->count();
    }

    public function latestEfficacyScore(): ?int
    {
        return $this->efficacyAssessments()->latest('assessed_at')->value('score');
    }

    public function weeklyActivityData(): array
    {
        $logs = $this->activityLogs()
            ->with('activity')
            ->whereBetween('completed_at', [now()->subDays(6)->toDateString(), today()->toDateString()])
            ->get();

        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $days[$date] = $logs->where('completed_at', $date)->where('status', 'completed')->count();
        }

        return $days;
    }
}
