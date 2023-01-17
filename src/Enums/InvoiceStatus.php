<?php

namespace dnj\Invoice\Enums;
enum InvoiceStatus: string
{
    case PAID = 'paid';
    case UNPAID = 'unpaid';
}
