<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Biller extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'category_id',
        'logo_url',
        'description',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'category_id' => 'integer'
    ];

    /**
     * Get the category that owns the biller.
     */
    public function category()
    {
        return $this->belongsTo(BillerCategory::class, 'category_id');
    }

    /**
     * Get the bills for the biller.
     */
    public function bills()
    {
        return $this->hasMany(Bill::class);
    }
}
