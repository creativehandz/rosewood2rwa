<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Resident;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $amountDue = $this->faker->randomFloat(2, 1000, 5000);
        $amountPaid = $this->faker->randomFloat(2, 0, $amountDue);
        
        // Determine status based on amount paid
        if ($amountPaid >= $amountDue) {
            $status = 'Paid';
        } elseif ($amountPaid > 0) {
            $status = 'Partial';
        } else {
            $status = $this->faker->randomElement(['Pending', 'Overdue']);
        }

        // Generate payment date based on status
        $paymentDate = null;
        if ($amountPaid > 0) {
            $paymentDate = $this->faker->dateTimeBetween('-30 days', 'now');
        }

        return [
            'resident_id' => Resident::factory(),
            'payment_month' => $this->faker->dateTimeBetween('-12 months', '+2 months')->format('Y-m'),
            'amount_due' => $amountDue,
            'amount_paid' => $amountPaid,
            'payment_date' => $paymentDate,
            'payment_method' => $amountPaid > 0 ? $this->faker->randomElement(['Cash', 'UPI', 'Bank Transfer']) : null,
            'transaction_id' => $amountPaid > 0 ? $this->faker->unique()->regexify('[A-Z0-9]{10,15}') : null,
            'status' => $status,
            'remarks' => $this->faker->optional(0.3)->sentence(),
            'sheet_row_id' => $this->faker->optional(0.7)->numberBetween(2, 100),
            'last_synced_at' => $this->faker->optional(0.8)->dateTimeBetween('-7 days', 'now'),
            'google_sheet_data' => $this->faker->optional(0.5)->passthrough([
                'row' => $this->faker->numberBetween(2, 100),
                'last_updated' => $this->faker->dateTimeThisMonth()->format('Y-m-d H:i:s')
            ])
        ];
    }

    /**
     * Indicate that the payment is fully paid.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            $amountDue = $attributes['amount_due'];
            return [
                'amount_paid' => $amountDue,
                'status' => 'Paid',
                'payment_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'payment_method' => $this->faker->randomElement(['Cash', 'UPI', 'Bank Transfer']),
                'transaction_id' => $this->faker->unique()->regexify('[A-Z0-9]{10,15}')
            ];
        });
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'amount_paid' => 0,
                'status' => 'Pending',
                'payment_date' => null,
                'payment_method' => null,
                'transaction_id' => null
            ];
        });
    }

    /**
     * Indicate that the payment is overdue.
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'amount_paid' => 0,
                'status' => 'Overdue',
                'payment_date' => null,
                'payment_method' => null,
                'transaction_id' => null,
                'payment_month' => $this->faker->dateTimeBetween('-6 months', '-1 month')->format('Y-m')
            ];
        });
    }

    /**
     * Indicate that the payment is partial.
     */
    public function partial(): static
    {
        return $this->state(function (array $attributes) {
            $amountDue = $attributes['amount_due'];
            $amountPaid = $this->faker->randomFloat(2, $amountDue * 0.1, $amountDue * 0.9);
            
            return [
                'amount_paid' => $amountPaid,
                'status' => 'Partial',
                'payment_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'payment_method' => $this->faker->randomElement(['Cash', 'UPI', 'Bank Transfer']),
                'transaction_id' => $this->faker->unique()->regexify('[A-Z0-9]{10,15}')
            ];
        });
    }

    /**
     * Indicate that the payment is for a specific month.
     */
    public function forMonth(string $month): static
    {
        return $this->state(function (array $attributes) use ($month) {
            return [
                'payment_month' => $month
            ];
        });
    }

    /**
     * Indicate that the payment is for a specific resident.
     */
    public function forResident(int $residentId): static
    {
        return $this->state(function (array $attributes) use ($residentId) {
            return [
                'resident_id' => $residentId
            ];
        });
    }

    /**
     * Indicate that the payment has a specific amount due.
     */
    public function withAmountDue(float $amount): static
    {
        return $this->state(function (array $attributes) use ($amount) {
            return [
                'amount_due' => $amount
            ];
        });
    }

    /**
     * Indicate that the payment is recent (within last 30 days).
     */
    public function recent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_month' => now()->format('Y-m'),
                'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-30 days', 'now')
            ];
        });
    }

    /**
     * Indicate that the payment is old (more than 6 months).
     */
    public function old(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_month' => $this->faker->dateTimeBetween('-12 months', '-6 months')->format('Y-m'),
                'created_at' => $this->faker->dateTimeBetween('-12 months', '-6 months'),
                'updated_at' => $this->faker->dateTimeBetween('-12 months', '-6 months')
            ];
        });
    }

    /**
     * Indicate that the payment has been synced with Google Sheets.
     */
    public function synced(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'sheet_row_id' => $this->faker->numberBetween(2, 500),
                'last_synced_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
                'google_sheet_data' => [
                    'row' => $this->faker->numberBetween(2, 500),
                    'last_updated' => $this->faker->dateTimeThisMonth()->format('Y-m-d H:i:s'),
                    'sync_source' => 'api'
                ]
            ];
        });
    }
}