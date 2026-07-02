<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MediaProvider;
use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    use HasFactory;
    
    // Explicitly NO SoftDeletes here as per design principles

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected static function booted(): void
    {
        static::creating(function (Media $media) {
            $media->uuid = (string) \Illuminate\Support\Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'type'       => MediaType::class,
            'provider'   => MediaProvider::class,
            'is_primary' => 'boolean',
            'order'      => 'integer',
        ];
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
