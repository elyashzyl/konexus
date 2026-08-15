<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionInvoice>
 */
class SubscriptionInvoiceFactory extends Factory
{
    protected $model = SubscriptionInvoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 50, 500);
        $tax = round($amount * 0.12, 2);

        return [
            'invoice_number' => fn () => 'INV-'.strtoupper(fake()->unique()->bothify('######')),
            'subscription_id' => Subscription::factory(),
            'tenant_id' => Tenant::factory(),
            'billing_period_start' => fake()->dateTimeBetween('-2 months', '-1 month')->format('Y-m-d'),
            'billing_period_end' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'amount' => $amount,
            'discount' => 0,
            'tax' => $tax,
            'total' => round($amount + $tax, 2),
            'currency' => 'PHP',
            'status' => 'pending',
            'due_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'paid_at' => null,
            'payment_reference' => null,
            'payment_method' => null,
            'notes' => null,
        ];
    }
}