<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledPayment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'payment_method_id',
        'bank_account_id',
        'category_id',
        'payment_type',
        'frequency',
        'recipient_name',
        'recipient_account',
        'recipient_bank',
        'amount',
        'currency',
        'description',
        'next_payment_date',
        'end_date',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'next_payment_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * The user who created this scheduled payment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The payment method used for this scheduled payment.
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * The bank account used for this scheduled payment.
     */
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * The category of this scheduled payment.
     */
    public function category()
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    /**
     * The transactions generated from this scheduled payment.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Scope a query to only include active scheduled payments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include scheduled payments due today or earlier.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDue($query)
    {
        return $query->where('next_payment_date', '<=', now())
            ->where('is_active', true);
    }

    /**
     * Scope a query to filter by payment type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    /**
     * Scope a query to filter by frequency.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $frequency
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithFrequency($query, $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Determine if the scheduled payment is recurring.
     *
     * @return bool
     */
    public function isRecurring()
    {
        return $this->payment_type === 'recurring';
    }

    /**
     * Determine if the scheduled payment is a one-time payment.
     *
     * @return bool
     */
    public function isOneTime()
    {
        return $this->payment_type === 'one_time';
    }

    /**
     * Calculate the next payment date based on frequency.
     *
     * @return \Carbon\Carbon
     */
    public function calculateNextPaymentDate()
    {
        if (!$this->isRecurring()) {
            return null;
        }

        $currentDate = $this->next_payment_date ?? now();

        switch ($this->frequency) {
            case 'daily':
                return $currentDate->addDay();
            case 'weekly':
                return $currentDate->addWeek();
            case 'monthly':
                return $currentDate->addMonth();
            case 'yearly':
                return $currentDate->addYear();
            default:
                return null;
        }
    }

    /**
     * Update the next payment date.
     *
     * @return bool
     */
    public function updateNextPaymentDate()
    {
        $nextDate = $this->calculateNextPaymentDate();

        if ($nextDate) {
            // Check if we've reached the end date
            if ($this->end_date && $nextDate->greaterThan($this->end_date)) {
                $this->is_active = false;
                return $this->save();
            }

            $this->next_payment_date = $nextDate;
            return $this->save();
        }

        // For one-time payments, deactivate after processing
        if ($this->isOneTime()) {
            $this->is_active = false;
            return $this->save();
        }

        return false;
    }
}
