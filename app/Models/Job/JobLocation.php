<?php
// MAIN APP: app/Models/Job/JobLocation.php

namespace App\Models\Job;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Job\JobPost;
use App\Models\Job\Company;

class JobLocation extends Model
{
    use HasFactory;

    // Country codes mapping to full names and metadata
    const COUNTRY_DATA = [
        'UG' => [
            'name' => 'Uganda',
            'region' => 'East Africa',
            'timezone' => 'Africa/Kampala',
            'currency' => 'UGX',
            'default_lat' => 1.3733,
            'default_lng' => 32.2903,
        ],
        'KE' => [
            'name' => 'Kenya',
            'region' => 'East Africa',
            'timezone' => 'Africa/Nairobi',
            'currency' => 'KES',
            'default_lat' => -1.2921,
            'default_lng' => 36.8219,
        ],
        'TZ' => [
            'name' => 'Tanzania',
            'region' => 'East Africa',
            'timezone' => 'Africa/Dar_es_Salaam',
            'currency' => 'TZS',
            'default_lat' => -6.7924,
            'default_lng' => 39.2083,
        ],
        'RW' => [
            'name' => 'Rwanda',
            'region' => 'East Africa',
            'timezone' => 'Africa/Kigali',
            'currency' => 'RWF',
            'default_lat' => -1.9441,
            'default_lng' => 30.0619,
        ],
        'BI' => [
            'name' => 'Burundi',
            'region' => 'East Africa',
            'timezone' => 'Africa/Bujumbura',
            'currency' => 'BIF',
            'default_lat' => -3.3614,
            'default_lng' => 29.3599,
        ],
        'SS' => [
            'name' => 'South Sudan',
            'region' => 'East Africa',
            'timezone' => 'Africa/Juba',
            'currency' => 'SSP',
            'default_lat' => 4.8594,
            'default_lng' => 31.5713,
        ],
        'CD' => [
            'name' => 'DR Congo',
            'region' => 'Central Africa',
            'timezone' => 'Africa/Kinshasa',
            'currency' => 'CDF',
            'default_lat' => -4.0383,
            'default_lng' => 21.7587,
        ],
        'NG' => [
            'name' => 'Nigeria',
            'region' => 'West Africa',
            'timezone' => 'Africa/Lagos',
            'currency' => 'NGN',
            'default_lat' => 9.0820,
            'default_lng' => 8.6753,
        ],
        'ZA' => [
            'name' => 'South Africa',
            'region' => 'Southern Africa',
            'timezone' => 'Africa/Johannesburg',
            'currency' => 'ZAR',
            'default_lat' => -30.5595,
            'default_lng' => 22.9375,
        ],
        'GH' => [
            'name' => 'Ghana',
            'region' => 'West Africa',
            'timezone' => 'Africa/Accra',
            'currency' => 'GHS',
            'default_lat' => 7.9465,
            'default_lng' => -1.0232,
        ],
        'ET' => [
            'name' => 'Ethiopia',
            'region' => 'East Africa',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'default_lat' => 9.0320,
            'default_lng' => 38.7469,
        ],
        'EG' => [
            'name' => 'Egypt',
            'region' => 'North Africa',
            'timezone' => 'Africa/Cairo',
            'currency' => 'EGP',
            'default_lat' => 26.8206,
            'default_lng' => 30.8025,
        ],
        'MA' => [
            'name' => 'Morocco',
            'region' => 'North Africa',
            'timezone' => 'Africa/Casablanca',
            'currency' => 'MAD',
            'default_lat' => 31.7917,
            'default_lng' => -7.0926,
        ],
        'DZ' => [
            'name' => 'Algeria',
            'region' => 'North Africa',
            'timezone' => 'Africa/Algiers',
            'currency' => 'DZD',
            'default_lat' => 28.0339,
            'default_lng' => 1.6596,
        ],
        'ZM' => [
            'name' => 'Zambia',
            'region' => 'Southern Africa',
            'timezone' => 'Africa/Lusaka',
            'currency' => 'ZMW',
            'default_lat' => -13.1339,
            'default_lng' => 27.8493,
        ],
        'ZW' => [
            'name' => 'Zimbabwe',
            'region' => 'Southern Africa',
            'timezone' => 'Africa/Harare',
            'currency' => 'USD',
            'default_lat' => -19.0154,
            'default_lng' => 29.1549,
        ],
        'MW' => [
            'name' => 'Malawi',
            'region' => 'Southern Africa',
            'timezone' => 'Africa/Blantyre',
            'currency' => 'MWK',
            'default_lat' => -13.2543,
            'default_lng' => 34.3015,
        ],
    ];

    // Major cities coordinates for precise location data
    const CITIES_DATA = [
        // Uganda
        'Kampala' => ['lat' => 0.3136, 'lng' => 32.5811, 'is_capital' => true],
        'Entebbe' => ['lat' => 0.0512, 'lng' => 32.4637, 'is_capital' => false],
        'Jinja' => ['lat' => 0.4246, 'lng' => 33.2042, 'is_capital' => false],
        'Gulu' => ['lat' => 2.7724, 'lng' => 32.2907, 'is_capital' => false],
        'Mbarara' => ['lat' => -0.6072, 'lng' => 30.6545, 'is_capital' => false],
        'Fort Portal' => ['lat' => 0.6712, 'lng' => 30.2750, 'is_capital' => false],
        'Mbale' => ['lat' => 1.0784, 'lng' => 34.1810, 'is_capital' => false],
        'Lira' => ['lat' => 2.2499, 'lng' => 32.8999, 'is_capital' => false],
        'Soroti' => ['lat' => 1.7144, 'lng' => 33.6111, 'is_capital' => false],
        
        // Kenya
        'Nairobi' => ['lat' => -1.2921, 'lng' => 36.8219, 'is_capital' => true],
        'Mombasa' => ['lat' => -4.0435, 'lng' => 39.6682, 'is_capital' => false],
        'Kisumu' => ['lat' => -0.1022, 'lng' => 34.7617, 'is_capital' => false],
        'Nakuru' => ['lat' => -0.3031, 'lng' => 36.0800, 'is_capital' => false],
        'Eldoret' => ['lat' => 0.5143, 'lng' => 35.2698, 'is_capital' => false],
        'Thika' => ['lat' => -1.0388, 'lng' => 37.0833, 'is_capital' => false],
        'Malindi' => ['lat' => -3.2187, 'lng' => 40.1169, 'is_capital' => false],
        
        // Tanzania
        'Dar es Salaam' => ['lat' => -6.7924, 'lng' => 39.2083, 'is_capital' => false],
        'Dodoma' => ['lat' => -6.1629, 'lng' => 35.7516, 'is_capital' => true],
        'Arusha' => ['lat' => -3.3869, 'lng' => 36.6820, 'is_capital' => false],
        'Mwanza' => ['lat' => -2.5164, 'lng' => 32.8987, 'is_capital' => false],
        'Zanzibar' => ['lat' => -6.1659, 'lng' => 39.2026, 'is_capital' => false],
        'Mbeya' => ['lat' => -8.9000, 'lng' => 33.4500, 'is_capital' => false],
        'Tanga' => ['lat' => -5.0724, 'lng' => 39.0995, 'is_capital' => false],
        
        // Rwanda
        'Kigali' => ['lat' => -1.9441, 'lng' => 30.0619, 'is_capital' => true],
        'Musanze' => ['lat' => -1.5000, 'lng' => 29.6346, 'is_capital' => false],
        'Rubavu' => ['lat' => -1.6833, 'lng' => 29.2500, 'is_capital' => false],
        'Huye' => ['lat' => -2.6000, 'lng' => 29.7333, 'is_capital' => false],
        
        // Burundi
        'Bujumbura' => ['lat' => -3.3614, 'lng' => 29.3599, 'is_capital' => true],
        'Gitega' => ['lat' => -3.4264, 'lng' => 29.9306, 'is_capital' => false],
        
        // South Sudan
        'Juba' => ['lat' => 4.8594, 'lng' => 31.5713, 'is_capital' => true],
        
        // Nigeria
        'Lagos' => ['lat' => 6.5244, 'lng' => 3.3792, 'is_capital' => false],
        'Abuja' => ['lat' => 9.0765, 'lng' => 7.3986, 'is_capital' => true],
        
        // South Africa
        'Johannesburg' => ['lat' => -26.2041, 'lng' => 28.0473, 'is_capital' => false],
        'Cape Town' => ['lat' => -33.9249, 'lng' => 18.4241, 'is_capital' => false],
        'Pretoria' => ['lat' => -25.7479, 'lng' => 28.2293, 'is_capital' => true],
        
        // Ghana
        'Accra' => ['lat' => 5.6037, 'lng' => -0.1870, 'is_capital' => true],
    ];

    protected $fillable = [
        'country',
        'country_code',
        'district',
        'city',
        'region',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'is_active',
        'sort_order',
        'created_by',
        'latitude',
        'longitude',
        'timezone',
        'is_capital',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_capital' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // Get full country name from country code
    public function getCountryNameAttribute(): string
    {
        return self::COUNTRY_DATA[$this->country]['name'] ?? $this->country;
    }

    // Get country code for hreflang (lowercase)
    public function getCountryCodeLowerAttribute(): string
    {
        return strtolower($this->country);
    }

    // Get region
    public function getRegionNameAttribute(): string
    {
        return self::COUNTRY_DATA[$this->country]['region'] ?? 'Africa';
    }

    // Get timezone
    public function getTimezoneNameAttribute(): string
    {
        return self::COUNTRY_DATA[$this->country]['timezone'] ?? 'Africa/Nairobi';
    }

    // Get currency
    public function getCurrencyAttribute(): string
    {
        return self::COUNTRY_DATA[$this->country]['currency'] ?? 'USD';
    }

    // Generate SEO-optimized URL with country prefix
    public function getUrlAttribute(): string
    {
        $countryCode = strtolower($this->country);
        return url("/{$countryCode}/jobs/location/{$this->slug}");
    }

    // Get hreflang tags for international SEO
    public function getHreflangTags(): array
    {
        $tags = [];
        $baseUrl = config('app.url');
        $countryCode = strtolower($this->country);
        
        $tags[] = [
            'rel' => 'alternate',
            'hreflang' => "en-{$countryCode}",
            'href' => "{$baseUrl}/{$countryCode}/jobs/location/{$this->slug}",
        ];
        
        $tags[] = [
            'rel' => 'alternate',
            'hreflang' => 'en',
            'href' => "{$baseUrl}/jobs/location/{$this->slug}",
        ];
        
        $tags[] = [
            'rel' => 'alternate',
            'hreflang' => 'x-default',
            'href' => "{$baseUrl}/jobs/location/{$this->slug}",
        ];
        
        return $tags;
    }

    // Get country-specific meta tags for better local SEO
    public function getCountryMetaTags(): array
    {
        $countryName = $this->country_name;
        $district = $this->district;
        
        return [
            'title' => "Jobs in {$district}, {$countryName} - Latest Career Opportunities",
            'description' => "Find the latest jobs in {$district}, {$countryName}. Browse thousands of career opportunities, vacancies, and employment in {$district}, {$countryName}. Apply today!",
            'keywords' => "jobs in {$district}, {$district} {$countryName} jobs, employment {$district}, careers {$countryName}, vacancies {$countryName}, work in {$countryName}",
            'og_title' => "{$district}, {$countryName} Jobs - Stardena Works",
            'og_description' => "Discover career opportunities in {$district}, {$countryName}. Find your dream job today!",
            'twitter_title' => "Jobs in {$district}, {$countryName}",
            'twitter_description' => "Browse the latest job openings in {$district}, {$countryName}",
        ];
    }

    // Auto-set coordinates based on district/city
    public function setCoordinatesFromCity(): void
    {
        // Try to find coordinates from city/district first
        if ($this->district && isset(self::CITIES_DATA[$this->district])) {
            $cityData = self::CITIES_DATA[$this->district];
            $this->latitude = $cityData['lat'];
            $this->longitude = $cityData['lng'];
            $this->is_capital = $cityData['is_capital'];
        } elseif ($this->city && isset(self::CITIES_DATA[$this->city])) {
            $cityData = self::CITIES_DATA[$this->city];
            $this->latitude = $cityData['lat'];
            $this->longitude = $cityData['lng'];
            $this->is_capital = $cityData['is_capital'];
        } elseif ($this->district) {
            // Try to match partial names
            foreach (self::CITIES_DATA as $city => $data) {
                if (stripos($this->district, $city) !== false || stripos($city, $this->district) !== false) {
                    $this->latitude = $data['lat'];
                    $this->longitude = $data['lng'];
                    $this->is_capital = $data['is_capital'];
                    break;
                }
            }
        }
        
        // If still no coordinates, use country default
        if (!$this->latitude && isset(self::COUNTRY_DATA[$this->country])) {
            $this->latitude = self::COUNTRY_DATA[$this->country]['default_lat'];
            $this->longitude = self::COUNTRY_DATA[$this->country]['default_lng'];
        }
    }

    // Scopes
    public function scopeByCountry($query, $countryCode)
    {
        return $query->where('country', $countryCode);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNearby($query, $latitude, $longitude, $radiusKm = 50)
    {
        // Haversine formula to find nearby locations
        return $query->whereRaw(
            "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?",
            [$latitude, $longitude, $latitude, $radiusKm]
        );
    }

    // Relationships
    public function jobPosts()
    {
        return $this->hasMany(JobPost::class, 'job_location_id');
    }

    public function companies()
    {
        return $this->hasMany(Company::class, 'location_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($location) {
            // ⭐ FORCE country_code from country if not set
            if (empty($location->country_code) && !empty($location->country)) {
                $location->country_code = $location->country;
            }
            
            // Generate slug
            if (empty($location->slug)) {
                $countryCode = strtolower($location->country);
                $districtSlug = Str::slug($location->district ?? $location->city ?? 'jobs');
                $location->slug = "{$districtSlug}-jobs-in-{$countryCode}";
            }
            
            // Set region from country data
            if (empty($location->region) && isset(self::COUNTRY_DATA[$location->country])) {
                $location->region = self::COUNTRY_DATA[$location->country]['region'];
            }
            
            // Set timezone from country data
            if (empty($location->timezone) && isset(self::COUNTRY_DATA[$location->country])) {
                $location->timezone = self::COUNTRY_DATA[$location->country]['timezone'];
            }
            
            // Auto-set coordinates
            if (empty($location->latitude) || empty($location->longitude)) {
                $location->setCoordinatesFromCity();
            }
            
            // Generate meta title if empty
            if (empty($location->meta_title)) {
                $countryName = $location->country_name;
                $locationName = $location->district ?? $location->city ?? 'Jobs';
                $location->meta_title = "Jobs in {$locationName}, {$countryName} - Latest Career Opportunities";
            }
            
            // Generate meta description if empty
            if (empty($location->meta_description)) {
                $countryName = $location->country_name;
                $locationName = $location->district ?? $location->city ?? 'Jobs';
                $location->meta_description = "Find latest jobs in {$locationName}, {$countryName}. Browse career opportunities, vacancies, and employment in {$locationName}, {$countryName}. Apply today!";
            }
            
            // Set created_by if not set and user is authenticated
            if (empty($location->created_by) && auth()->check()) {
                $location->created_by = auth()->id();
            }
        });
        
        static::updating(function ($location) {
            // ⭐ FORCE country_code update if country changes
            if ($location->isDirty('country') && !empty($location->country)) {
                $location->country_code = $location->country;
            }
            
            // Update coordinates if district/city changed
            if ($location->isDirty('district') || $location->isDirty('city') || $location->isDirty('country')) {
                $location->setCoordinatesFromCity();
            }
            
            // Update region if country changed
            if ($location->isDirty('country') && isset(self::COUNTRY_DATA[$location->country])) {
                $location->region = self::COUNTRY_DATA[$location->country]['region'];
                $location->timezone = self::COUNTRY_DATA[$location->country]['timezone'];
            }
            
            // Update slug if district changed
            if ($location->isDirty('district') || $location->isDirty('country')) {
                $countryCode = strtolower($location->country);
                $districtSlug = Str::slug($location->district ?? $location->city ?? 'jobs');
                $location->slug = "{$districtSlug}-jobs-in-{$countryCode}";
            }
        });
    }
}