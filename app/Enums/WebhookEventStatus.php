<?php

declare(strict_types=1);

namespace App\Enums;

enum WebhookEventStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Delivered = 'delivered';
    case Failed = 'failed';
}
