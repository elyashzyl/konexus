<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPayment>
 */
class SubscriptionPaymentFactory extends Factory
{
    protected $model = SubscriptionPayment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => SubscriptionInvoice::factory(),
            'subscription_id' => Subscription::factory(),
            'tenant_id' => Tenant::factory(),
            'amount' => fake()->randomFloat(2, 50, 500),
            'payment_date' => fake()->date(),
            'payment_method' => 'bank_transfer',
            'reference_number' => fn () => strtoupper(fake()->unique()->bothify('REF-####')),
            'status' => 'completed',
            'recorded_by' => null,
            'notes' => null,
        ];
    }
}