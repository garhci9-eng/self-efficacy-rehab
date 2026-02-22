<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ActivityLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id', 'activity_id', 'caregiver_id', 'status',
        'patient_response', 'caregiver_note', 'self_rating', 'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'date',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function activity() { return $this->belongsTo(Activity::class); }
}

// ─────────────────────────────────────────────

namespace App\Models;

class EfficacyAssessment extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id', 'score', 'responses', 'assessed_at', 'assessed_by',
    ];

    protected $casts = [
        'responses'   => 'array',
        'assessed_at' => 'date',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
}

// ─────────────────────────────────────────────

namespace App\Models;

class ChatMessage extends Model
{
    use HasUuids;

    protected $fillable = ['patient_id', 'role', 'content', 'context_type'];

    public function patient() { return $this->belongsTo(Patient::class); }
}

// ─────────────────────────────────────────────

namespace App\Models;

class WishTreeItem extends Model
{
    use HasUuids;

    protected $fillable = ['patient_id', 'wish', 'color'];

    public function patient() { return $this->belongsTo(Patient::class); }
}

// ─────────────────────────────────────────────

namespace App\Models;

class DiaryEntry extends Model
{
    use HasUuids;

    protected $fillable = ['patient_id', 'content', 'recorded_by', 'entry_date'];

    protected $casts = ['entry_date' => 'date'];

    public function patient() { return $this->belongsTo(Patient::class); }
}
