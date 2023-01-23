<?php

namespace dnj\Invoice\Enums;

enum PaymentStatus: string
{
    case APPROVED = 'approved';
    case PENDING = 'pending';
    case REJECTED = 'rejected';
}
