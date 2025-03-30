<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'icon',
        'color',
        'is_active',
        'is_system',
        'parent_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Get the parent category.
     */
    public function parent()
    {
        return $this->belongsTo(TransactionCategory::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children()
    {
        return $this->hasMany(TransactionCategory::class, 'parent_id');
    }

    /**
     * Get the transactions in this category.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }

    /**
     * Scope a query to only include active categories.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include parent categories.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to only include child categories.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope a query to only include income categories.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIncome($query)
    {
        return $query->where('code', 'income')
            ->orWhere('parent_id', function($query) {
                $query->select('id')
                    ->from('transaction_categories')
                    ->where('code', 'income');
            });
    }

    /**
     * Scope a query to only include expense categories.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpense($query)
    {
        return $query->where('code', 'expense')
            ->orWhere('parent_id', function($query) {
                $query->select('id')
                    ->from('transaction_categories')
                    ->where('code', 'expense');
            });
    }

    /**
     * Determine if this is an income category.
     *
     * @return bool
     */
    public function isIncome()
    {
        if ($this->code === 'income') {
            return true;
        }

        if ($this->parent_id) {
            return $this->parent->code === 'income';
        }

        return false;
    }

    /**
     * Determine if this is an expense category.
     *
     * @return bool
     */
    public function isExpense()
    {
        if ($this->code === 'expense') {
            return true;
        }

        if ($this->parent_id) {
            return $this->parent->code === 'expense';
        }

        return false;
    }
}
