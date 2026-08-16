<?php

namespace App\Models;

use Database\Factories\SubscriptionSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionSetting extends Model
{
    /** @use HasFactory<SubscriptionSettingFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'school_profile_id',
        'key',
        'value',
        'group',
        'type',
        'description',
        'is_active',
    ];

    /**
     * The school this setting belongs to.
     */
    public function schoolProfile(): BelongsTo
    {
        return $this->belongsTo(SchoolProfile::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Decode the setting value according to its stored type.
     */
    public function typedValue(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOL),
            'integer' => (int) $this->value,
            'decimal' => (float) $this->value,
            'json' => json_decode((string) $this->value, true),
            default => $this->value,
        };
    }

    /**
     * Encode a value for storage according to the given type.
     */
    public static function encode(string $type, mixed $value): ?string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            'decimal' => (string) (float) $value,
            'json' => json_encode($value),
            default => (string) $value,
        };
    }
}
