<?php

namespace App\Models;

use Database\Factories\SubscriptionUsageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionUsage extends Model
{
    /** @use HasFactory<SubscriptionUsageFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'subscription_usage';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'period_year',
        'period_month',
        'students_count',
        'users_count',
        'staff_count',
        'branches_count',
        'storage_mb',
        'documents_count',
        'database_size_mb',
        'captured_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'students_count' => 'integer',
            'users_count' => 'integer',
            'staff_count' => 'integer',
            'branches_count' => 'integer',
            'storage_mb' => 'integer',
            'documents_count' => 'integer',
            'database_size_mb' => 'integer',
            'captured_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}