<?php

namespace App\Models;

use Database\Factories\AttendanceRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRecord extends Model
{
    /** @use HasFactory<AttendanceRecordFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['attendance_session_id', 'student_id', 'status', 'minutes_late', 'remarks', 'recorded_by'];

    protected function casts(): array
    {
        return ['minutes_late' => 'integer'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
