<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentGateway extends Model
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
        'logo_path',
        'description',
        'api_credentials',
        'webhook_urls',
        'base_url',
        'supports_recurring',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'supports_recurring' => 'boolean',
        'is_active' => 'boolean',
        'api_credentials' => 'array',
        'webhook_urls' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'api_credentials',
    ];

    /**
     * Scope a query to only include active payment gateways.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the transactions processed through this gateway.
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'gateway_reference', 'code');
    }

    /**
     * Get the full URL to the logo.
     *
     * @return string|null
     */
    public function getLogoUrlAttribute()
    {
        if (!$this->logo_path) {
            return null;
        }

        return asset('storage/' . $this->logo_path);
    }

    /**
     * Get a specific API credential.
     *
     * @param string $key
     * @return mixed|null
     */
    public function getApiCredential($key)
    {
        $credentials = $this->api_credentials;

        return $credentials[$key] ?? null;
    }

    /**
     * Set a specific API credential.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setApiCredential($key, $value)
    {
        $credentials = $this->api_credentials ?: [];
        $credentials[$key] = $value;

        $this->api_credentials = $credentials;

        return $this;
    }

    /**
     * Get a webhook URL by its purpose.
     *
     * @param string $purpose
     * @return string|null
     */
    public function getWebhookUrl($purpose)
    {
        $webhooks = $this->webhook_urls;

        return $webhooks[$purpose] ?? null;
    }

    /**
     * Determine if this gateway has the necessary credentials.
     *
     * @return bool
     */
    public function hasValidCredentials()
    {
        return !empty($this->api_credentials);
    }
}
