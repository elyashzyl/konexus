<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_number',
        'lrn',
        'school_student_id',
        'rfid_number',
        'qr_code',
        'first_name',
        'middle_name',
        'last_name',
        'extension_name',
        'nickname',
        'gender',
        'birth_date',
        'place_of_birth',
        'civil_status',
        'nationality',
        'citizenship',
        'religion',
        'ethnicity',
        'mother_tongue',
        'interests',
        'is_indigenous',
        'family_monthly_income',
        'blood_type',
        'profile_picture_path',
        'status',
        'mobile_number',
        'telephone_number',
        'email',
        'current_address',
        'current_province',
        'current_city',
        'current_municipality',
        'current_barangay',
        'current_zip_code',
        'permanent_address',
        'permanent_province',
        'permanent_city',
        'permanent_municipality',
        'permanent_barangay',
        'permanent_zip_code',
        'height',
        'weight',
        'medical_conditions',
        'food_allergies',
        'medicine_allergies',
        'preferred_hospital',
        'medical_notes',
        'emergency_medical_notes',
        'disabilities',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_mobile',
        'emergency_contact_telephone',
        'emergency_contact_address',
        'user_id',
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
            'birth_date' => 'date',
            'interests' => 'array',
            'is_indigenous' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The campuses this student is assigned to.
     *
     * @return BelongsToMany<Campus, $this>
     */
    public function campuses(): BelongsToMany
    {
        return $this->belongsToMany(Campus::class);
    }

    /**
     * The school profile this student belongs to.
     *
     * @return BelongsTo<SchoolProfile, $this>
     */
    public function schoolProfile(): BelongsTo
    {
        return $this->belongsTo(SchoolProfile::class);
    }

    /**
     * The parents linked to this student.
     *
     * @return BelongsToMany<parent, $this>
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(ParentGuardian::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * The guardians linked to this student.
     *
     * @return BelongsToMany<Guardian, $this>
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'guardian_student', 'student_id', 'guardian_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * The enrollment records belonging to the student.
     *
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class)->orderByDesc('academic_year_id');
    }

    public function subjectEnrollments(): HasMany
    {
        return $this->hasMany(StudentSubjectEnrollment::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * The most recent enrollment of the student.
     *
     * @return HasOne<Enrollment, $this>
     */
    public function latestEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class)->latestOfMany();
    }

    /**
     * The most recent active (in-progress) enrollment of the student.
     *
     * @return HasOne<Enrollment, $this>
     */
    public function activeEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class)
            ->whereIn('status', EnrollmentStatus::activeStatuses())
            ->latestOfMany();
    }

    /**
     * The documents belonging to the student.
     *
     * @return HasMany<StudentDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    /**
     * The user account linked to this student (portal identity).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The computed age of the student based on the date of birth.
     */
    public function getAgeAttribute(): ?int
    {
        if ($this->birth_date === null) {
            return null;
        }

        return $this->birth_date instanceof Carbon
            ? $this->birth_date->age
            : Carbon::parse($this->birth_date)->age;
    }

    /**
     * The full name of the student.
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->extension_name,
        ], fn (?string $part) => filled($part));

        return implode(' ', $parts);
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('students');
    }
}
