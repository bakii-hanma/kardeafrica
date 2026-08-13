<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un message WhatsApp journalisé (sortant ou entrant) — voir migration
 * create_whatsapp_messages_table. Le journal est la source de vérité pour
 * l'idempotence (dedup_key), la programmation (scheduled_at) et les statuts.
 */
class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    public const DIR_OUT = 'out';
    public const DIR_IN  = 'in';

    public const STATUS_QUEUED    = 'queued';
    public const STATUS_SENT      = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ      = 'read';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_RECEIVED  = 'received';   // entrant

    public const CAT_TRANSACTIONAL = 'transactional';
    public const CAT_SUPPORT       = 'support';
    public const CAT_MARKETING     = 'marketing';
    public const CAT_OTP           = 'otp';
    public const CAT_OPS           = 'ops';

    protected $fillable = [
        'direction', 'phone', 'type', 'category', 'body', 'payload',
        'status', 'provider_message_id', 'error',
        'context_type', 'context_id', 'dedup_key',
        'scheduled_at', 'sent_at', 'delivered_at', 'read_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'read_at'      => 'datetime',
    ];
}
