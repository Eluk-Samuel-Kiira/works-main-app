<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SocialMediaPlatform extends Model
{
    use HasFactory;

    protected $table = 'social_media_platforms';

    protected $fillable = [
        'name',
        'slug',
        'platform',
        'url',
        'icon',
        'description',
        'followers_count',
        'handle',
        'location_id',
        'is_active',
        'is_verified',
        'is_featured',
        'sort_order',
        'meta_title',
        'meta_description',
        'created_by',
    ];

    protected $casts = [
        'followers_count' => 'integer',
        'sort_order'      => 'integer',
        'is_active'       => 'boolean',
        'is_verified'     => 'boolean',
        'is_featured'     => 'boolean',
    ];

    // Supported platform types
    const PLATFORMS = [
        'facebook'   => ['label' => 'Facebook',        'color' => '#1877F2', 'icon' => 'bi bi-facebook'],
        'instagram'  => ['label' => 'Instagram',        'color' => '#E1306C', 'icon' => 'bi bi-instagram'],
        'twitter'    => ['label' => 'X (Twitter)',       'color' => '#000000', 'icon' => 'bi bi-twitter-x'],
        'youtube'    => ['label' => 'YouTube',           'color' => '#FF0000', 'icon' => 'bi bi-youtube'],
        'tiktok'     => ['label' => 'TikTok',            'color' => '#010101', 'icon' => 'bi bi-tiktok'],
        'whatsapp'   => ['label' => 'WhatsApp',          'color' => '#25D366', 'icon' => 'bi bi-whatsapp'],
        'telegram'   => ['label' => 'Telegram',          'color' => '#229ED9', 'icon' => 'bi bi-telegram'],
        'linkedin'   => ['label' => 'LinkedIn',          'color' => '#0A66C2', 'icon' => 'bi bi-linkedin'],
        'snapchat'   => ['label' => 'Snapchat',          'color' => '#FFFC00', 'icon' => 'bi bi-snapchat'],
        'pinterest'  => ['label' => 'Pinterest',         'color' => '#E60023', 'icon' => 'bi bi-pinterest'],
        'reddit'     => ['label' => 'Reddit',            'color' => '#FF4500', 'icon' => 'bi bi-reddit'],
        'threads'    => ['label' => 'Threads',           'color' => '#000000', 'icon' => 'bi bi-threads'],
        'discord'    => ['label' => 'Discord',           'color' => '#5865F2', 'icon' => 'bi bi-discord'],
        'twitch'     => ['label' => 'Twitch',            'color' => '#9146FF', 'icon' => 'bi bi-twitch'],
        'spotify'    => ['label' => 'Spotify',           'color' => '#1DB954', 'icon' => 'bi bi-spotify'],
        'github'     => ['label' => 'GitHub',            'color' => '#181717', 'icon' => 'bi bi-github'],
        'medium'     => ['label' => 'Medium',            'color' => '#000000', 'icon' => 'bi bi-medium'],
        'vimeo'      => ['label' => 'Vimeo',             'color' => '#1AB7EA', 'icon' => 'bi bi-vimeo'],
        'skype'      => ['label' => 'Skype',             'color' => '#00AFF0', 'icon' => 'bi bi-skype'],
        'slack'      => ['label' => 'Slack',             'color' => '#4A154B', 'icon' => 'bi bi-slack'],
        'mastodon'   => ['label' => 'Mastodon',          'color' => '#6364FF', 'icon' => 'bi bi-mastodon'],
        'behance'    => ['label' => 'Behance',           'color' => '#1769FF', 'icon' => 'bi bi-behance'],
        'dribbble'   => ['label' => 'Dribbble',          'color' => '#EA4C89', 'icon' => 'bi bi-dribbble'],
        'substack'   => ['label' => 'Substack',          'color' => '#FF6719', 'icon' => 'bi bi-substack'],
        'tumblr'     => ['label' => 'Tumblr',            'color' => '#35465C', 'icon' => 'bi bi-tumblr'],
        'soundcloud' => ['label' => 'SoundCloud',        'color' => '#FF5500', 'icon' => 'bi bi-soundwave'],
        'signal'     => ['label' => 'Signal',            'color' => '#3A76F0', 'icon' => 'bi bi-signal'],
        'line'       => ['label' => 'LINE',              'color' => '#00C300', 'icon' => 'bi bi-chat-fill'],
        'viber'      => ['label' => 'Viber',             'color' => '#7360F2', 'icon' => 'bi bi-phone-fill'],
        'wechat'     => ['label' => 'WeChat',            'color' => '#07C160', 'icon' => 'bi bi-wechat'],
        'qq'         => ['label' => 'QQ',                'color' => '#12B7F5', 'icon' => 'bi bi-tencent-qq'],
        'weibo'      => ['label' => 'Weibo',             'color' => '#DF2029', 'icon' => 'bi bi-sina-weibo'],
        'quora'      => ['label' => 'Quora',             'color' => '#B92B27', 'icon' => 'bi bi-quora'],
        'patreon'    => ['label' => 'Patreon',           'color' => '#FF424D', 'icon' => 'bi bi-patch-check-fill'],
        'ko_fi'      => ['label' => 'Ko-fi',             'color' => '#FF5E5B', 'icon' => 'bi bi-cup-hot-fill'],
        'buymeacoffee' => ['label' => 'Buy Me a Coffee', 'color' => '#FFDD00', 'icon' => 'bi bi-cup-fill'],
        'onlyfans'   => ['label' => 'OnlyFans',          'color' => '#00AFF0', 'icon' => 'bi bi-person-heart'],
        'clubhouse'  => ['label' => 'Clubhouse',         'color' => '#F3E9D7', 'icon' => 'bi bi-mic-fill'],
        'bluesky'    => ['label' => 'Bluesky',           'color' => '#0085FF', 'icon' => 'bi bi-cloud-fill'],
        'rumble'     => ['label' => 'Rumble',            'color' => '#85C742', 'icon' => 'bi bi-play-circle-fill'],
        'odysee'     => ['label' => 'Odysee',            'color' => '#EF1970', 'icon' => 'bi bi-play-btn-fill'],
        'xing'       => ['label' => 'Xing',              'color' => '#006567', 'icon' => 'bi bi-x-square-fill'],
        'vk'         => ['label' => 'VKontakte (VK)',    'color' => '#0077FF', 'icon' => 'bi bi-vk'],
        'ok'         => ['label' => 'Odnoklassniki',     'color' => '#EE8208', 'icon' => 'bi bi-person-circle'],
        'website'    => ['label' => 'Website / Blog',    'color' => '#343a40', 'icon' => 'bi bi-globe2'],
        'other'      => ['label' => 'Other',             'color' => '#6c757d', 'icon' => 'bi bi-globe'],
    ];

    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model->name);
            }
            if (empty($model->meta_title)) {
                $model->meta_title = Str::limit("Follow us on {$model->name}", 60);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->getOriginal('slug'))) {
                $model->slug = static::generateUniqueSlug($model->name);
            }
        });
    }

    private static function generateUniqueSlug(string $name): string
    {
        $base    = Str::slug($name);
        $slug    = $base;
        $counter = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter++;
        }
        return $slug;
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------
    public function location()
    {
        return $this->belongsTo(\App\Models\Job\JobLocation::class, 'location_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\Auth\User::class, 'created_by');
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------
    public function getPlatformLabelAttribute(): string
    {
        return self::PLATFORMS[$this->platform]['label'] ?? ucfirst($this->platform);
    }

    public function getPlatformColorAttribute(): string
    {
        return self::PLATFORMS[$this->platform]['color'] ?? '#6c757d';
    }

    public function getPlatformIconAttribute(): string
    {
        return $this->icon ?? (self::PLATFORMS[$this->platform]['icon'] ?? 'bi bi-globe');
    }

    public function getFollowersFormattedAttribute(): string
    {
        $n = $this->followers_count;
        if ($n >= 1_000_000) return round($n / 1_000_000, 1) . 'M';
        if ($n >= 1_000)     return round($n / 1_000, 1) . 'K';
        return (string) $n;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeForLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}