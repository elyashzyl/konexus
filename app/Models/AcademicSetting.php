<?php

namespace App\Models;

use Database\Factories\AcademicSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicSetting extends Model
{
    /** @use HasFactory<AcademicSettingFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Configurable operating days key.
     */
    public const DAYS_KEY = 'operating_days';

    /**
     * Configurable class-sync toggle key.
     */
    public const AUTO_SYNC_CLASSES_KEY = 'auto_sync_classes';

    /**
     * Teacher load limit (unofficial / configurable).
     */
    public const TEACHER_MAX_LOAD_KEY = 'teacher_max_load';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'group',
        'value',
        'type',
        'sort_order',
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
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}