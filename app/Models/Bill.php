<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Bill extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'biller_id',
        'category_id',
        'payment_method_id',
        'bank_account_id',
        'name',
        'bill_type',
        'provider',
        'account_number',
        'reference_number',
        'amount',
        'currency',
        'is_variable_amount',
        'frequency',
        'due_day',
        'description',
        'website_url',
        'customer_service_phone',
        'next_due_date',
        'last_paid_date',
        'auto_pay',
        'reminder_days',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'is_variable_amount' => 'boolean',
        'next_due_date' => 'date',
        'last_paid_date' => 'date',
        'auto_pay' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * The user who owns this bill.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The category of this bill.
     */
    public function category()
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    /**
     * The payment method for this bill.
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * The bank account for this bill.
     */
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * The transactions associated with this bill.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'reference_id', 'id')
            ->where('payment_for', 'bill');
    }

    /**
     * Scope a query to only include active bills.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include bills that are due soon.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDueSoon($query, $days = null)
    {
        $endDate = now()->addDays($days ?? 5);
        return $query->where('next_due_date', '<=', $endDate)
            ->where('next_due_date', '>=', now())
            ->where('is_active', true);
    }

    /**
     * Scope a query to only include overdue bills.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverdue($query)
    {
        return $query->where('next_due_date', '<', now())
            ->where('is_active', true);
    }

    /**
     * Scope a query to filter by bill type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('bill_type', $type);
    }

    /**
     * Scope a query to filter by autopay status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  bool  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAutoPay($query, $status = true)
    {
        return $query->where('auto_pay', $status);
    }

    /**
     * Determine if the bill is overdue.
     *
     * @return bool
     */
    public function isOverdue()
    {
        return $this->next_due_date->isPast();
    }

    /**
     * Determine if the bill is due soon.
     *
     * @param  int  $days
     * @return bool
     */
    public function isDueSoon($days = null)
    {
        $checkDays = $days ?? $this->reminder_days;
        $dueDate = $this->next_due_date;

        return $dueDate->between(now(), now()->addDays($checkDays));
    }

    /**
     * Get the number of days until the bill is due.
     *
     * @return int
     */
    public function daysUntilDue()
    {
        return now()->diffInDays($this->next_due_date, false);
    }

    /**
     * Mark this bill as paid and update next due date.
     *
     * @param  \Carbon\Carbon|string|null  $paidDate
     * @return bool
     */
    public function markAsPaid($paidDate = null)
    {
        $this->last_paid_date = $paidDate ?? now();
        $this->next_due_date = $this->calculateNextDueDate();

        return $this->save();
    }

    /**
     * Calculate the next due date based on frequency.
     *
     * @return \Carbon\Carbon
     */
    public function calculateNextDueDate()
    {
        $baseDate = $this->next_due_date;

        switch ($this->frequency) {
            case 'weekly':
                return $baseDate->copy()->addWeek();
            case 'monthly':
                $nextDate = $baseDate->copy()->addMonth();
                // Adjust for months with fewer days if needed
                if ($this->due_day) {
                    $maxDay = $nextDate->daysInMonth;
                    $day = min($this->due_day, $maxDay);
                    return $nextDate->day($day);
                }
                return $nextDate;
            case 'quarterly':
                return $baseDate->copy()->addMonths(3);
            case 'yearly':
                return $baseDate->copy()->addYear();
            default:
                return $baseDate->copy()->addMonth();
        }
    }

    /**
     * Get a summary of this bill for display.
     *
     * @return array
     */
    public function getSummary()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => $this->formatAmount(),
            'next_due_date' => $this->next_due_date->format('M d, Y'),
            'days_until_due' => $this->daysUntilDue(),
            'status' => $this->getStatusLabel(),
            'auto_pay' => $this->auto_pay ? 'Enabled' : 'Disabled',
        ];
    }

    /**
     * Get the formatted amount.
     *
     * @return string
     */
    public function formatAmount()
    {
        if ($this->is_variable_amount) {
            return 'Variable';
        }

        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get the status label.
     *
     * @return string
     */
    public function getStatusLabel()
    {
        if ($this->isOverdue()) {
            return 'Overdue';
        }

        if ($this->isDueSoon()) {
            return 'Due Soon';
        }

        return 'Upcoming';
    }
}
