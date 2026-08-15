<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Links a user account to an external OAuth identity (Google / Facebook).
 */
class SocialAccount extends Model
{
    /** @use HasFactory<\Database\Factories\SocialAccountFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_email',
        'name',
        'avatar',
    ];

    /**
     * The user account this social identity is linked to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}