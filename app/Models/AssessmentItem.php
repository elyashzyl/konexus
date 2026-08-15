<?php

namespace App\Models;

use Database\Factories\AssessmentItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentItem extends Model
{
    /** @use HasFactory<AssessmentItemFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['subject_offering_id', 'academic_period_id', 'component', 'title', 'max_score', 'display_order', 'status'];

    protected function casts(): array
    {
        return ['max_score' => 'decimal:2', 'display_order' => 'integer'];
    }

    public function subjectOffering(): BelongsTo
    {
        return $this->belongsTo(SubjectOffering::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }
}
