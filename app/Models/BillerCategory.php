<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillerCategory extends Model
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
        'icon'
    ];

    /**
     * Get the billers for the category.
     */
    public function billers()
    {
        return $this->hasMany(Biller::class, 'category_id');
    }
}
