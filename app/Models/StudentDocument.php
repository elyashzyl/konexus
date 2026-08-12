<?php

namespace App\Models;

use App\Enums\StudentDocumentStatus;
use Database\Factories\StudentDocumentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudentDocument extends Model
{
    /** @use HasFactory<StudentDocumentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'document_type',
        'name',
        'file_path',
        'mime_type',
        'file_size',
        'status',
        'remarks',
        'expires_at',
        'uploaded_by',
        'verified_by',
        'verified_at',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'expires_at' => 'date',
            'verified_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * The disk used to store student documents. Files stay private.
     */
    public static function storageDisk(): string
    {
        return 'local';
    }

    /**
     * A human friendly label for the current status.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(
            fn (): string => StudentDocumentStatus::tryFrom($this->status)?->label() ?? ucfirst($this->status)
        );
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->logOnly(['status', 'remarks'])
            ->useLogName('student_documents');
    }
}