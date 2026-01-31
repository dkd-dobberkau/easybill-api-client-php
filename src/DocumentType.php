<?php

declare(strict_types=1);

namespace Dkd\EasybillClient;

/**
 * Document types for Easybill API.
 */
enum DocumentType: string
{
    case INVOICE = 'INVOICE';
    case CREDIT = 'CREDIT';
    case OFFER = 'OFFER';
    case ORDER = 'ORDER';
    case RECURRING = 'RECURRING';
}
