<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'account_number',
        'bank_name',
        'branch_name',
        'routing_number',
        'swift_code',
        'email',
        'phone',
        'address',
        'relationship',
        'beneficiary_type',
        'is_favorite',
        'nickname',
        'notes',
        'is_verified',
        'verified_at',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_favorite' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * The user who created this beneficiary.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The transactions sent to this beneficiary.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'recipient_account', 'account_number')
            ->where('recipient_bank', $this->bank_name);
    }

    /**
     * The scheduled payments to this beneficiary.
     */
    public function scheduledPayments()
    {
        return $this->hasMany(ScheduledPayment::class, 'recipient_account', 'account_number')
            ->where('recipient_bank', $this->bank_name);
    }

    /**
     * Scope a query to only include active beneficiaries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include favorite beneficiaries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFavorite($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Scope a query to only include verified beneficiaries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope a query to filter by beneficiary type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('beneficiary_type', $type);
    }

    /**
     * Mark this beneficiary as verified.
     *
     * @return bool
     */
    public function markAsVerified()
    {
        $this->is_verified = true;
        $this->verified_at = now();

        return $this->save();
    }

    /**
     * Toggle the favorite status.
     *
     * @return bool
     */
    public function toggleFavorite()
    {
        $this->is_favorite = !$this->is_favorite;

        return $this->save();
    }

    /**
     * Get the display name (nickname if available, otherwise name).
     *
     * @return string
     */
    public function getDisplayNameAttribute()
    {
        return $this->nickname ?: $this->name;
    }

    /**
     * Get the masked account number.
     *
     * @return string
     */
    public function getMaskedAccountNumberAttribute()
    {
        $length = strlen($this->account_number);
        $visibleCount = 4;

        if ($length <= $visibleCount) {
            return $this->account_number;
        }

        $maskedPart = str_repeat('*', $length - $visibleCount);
        $visiblePart = substr($this->account_number, -$visibleCount);

        return $maskedPart . $visiblePart;
    }

    /**
     * Get the total amount transferred to this beneficiary.
     *
     * @return float
     */
    public function getTotalTransferredAmount()
    {
        return $this->transactions()
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get a summary of this beneficiary for display.
     *
     * @return array
     */
    public function getSummary()
    {
        return [
            'id' => $this->id,
            'name' => $this->display_name,
            'account' => $this->masked_account_number,
            'bank' => $this->bank_name,
            'is_favorite' => $this->is_favorite,
            'is_verified' => $this->is_verified,
            'type' => ucfirst($this->beneficiary_type),
        ];
    }
}
