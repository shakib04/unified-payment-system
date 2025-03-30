<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_id',
        'user_id',
        'payment_method_id',
        'bank_account_id',
        'category_id',
        'scheduled_payment_id',
        'transaction_type',
        'payment_for',
        'recipient_name',
        'recipient_account',
        'recipient_bank',
        'amount',
        'fee',
        'currency',
        'status',
        'description',
        'reference_id',
        'gateway_reference',
        'response_data',
        'receipt_url',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'response_data' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment method used for the transaction.
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the bank account used for the transaction.
     */
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Get the category of the transaction.
     */
    public function category()
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    /**
     * Get the scheduled payment related to this transaction.
     */
    public function scheduledPayment()
    {
        return $this->belongsTo(ScheduledPayment::class);
    }

    /**
     * Scope a query to only include transactions of a specific type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope a query to only include transactions with a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include completed transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include pending transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include failed transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to filter transactions by date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $startDate
     * @param  string  $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get the formatted amount with currency symbol.
     *
     * @return string
     */
    public function getFormattedAmountAttribute()
    {
        $symbol = $this->currency === 'BDT' ? '৳' : '$';
        return $symbol . ' ' . number_format($this->amount, 2);
    }

    /**
     * Get the formatted fee with currency symbol.
     *
     * @return string
     */
    public function getFormattedFeeAttribute()
    {
        $symbol = $this->currency === 'BDT' ? '৳' : '$';
        return $symbol . ' ' . number_format($this->fee, 2);
    }

    /**
     * Get the total amount including fees.
     *
     * @return float
     */
    public function getTotalAmountAttribute()
    {
        return $this->amount + $this->fee;
    }

    /**
     * Get the formatted total amount with currency symbol.
     *
     * @return string
     */
    public function getFormattedTotalAmountAttribute()
    {
        $symbol = $this->currency === 'BDT' ? '৳' : '$';
        return $symbol . ' ' . number_format($this->getTotalAmountAttribute(), 2);
    }
}
