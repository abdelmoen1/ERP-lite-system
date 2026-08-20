<?php

namespace App\Enums;

enum InvoiceSource: string
{
    case SALE = 'sale';
    case OPENING_DEBT = 'opening_debt';
}
