<?php

namespace App\Models;

use App\Enums\Platform\InvoiceStatus;
use Database\Factories\SubscriptionInvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class SubscriptionInvoice extends Model
{
    /** @use HasFactory<SubscriptionInvoiceFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_number',
        'subscription_id',
        'tenant_id',
        'billing_period_start',
        'billing_period_end',
        'amount',
        'discount',
        'tax',
        'total',
        'currency',
        'status',
        'due_date',
        'paid_at',
        'payment_reference',
        'payment_method',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return HasMany<SubscriptionPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class, 'invoice_id');
    }

    /**
     * The total amount already paid against this invoice.
     */
    public function paidAmount(): float
    {
        return (float) $this->payments()
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * The remaining balance of this invoice.
     */
    public function balance(): float
    {
        return max(0.0, (float) $this->total - $this->paidAmount());
    }

    /**
     * Whether the invoice is fully paid.
     */
    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::PAID->value || $this->balance() <= 0;
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logOnly([
                'subscription_id',
                'tenant_id',
                'amount',
                'discount',
                'tax',
                'total',
                'status',
                'due_date',
                'paid_at',
            ])
            ->useLogName('subscription_invoices');
    }
}