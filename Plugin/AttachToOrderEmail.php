<?php
/*
 * Copyright (c) 2026 WebWorks 101 OÜ (MageBooster). All rights reserved.
 * Commercial licence, see LICENSE.txt. https://magebooster.com
 * Licensed per serial number for one production installation (one store domain); staging and
 * development copies of that store are allowed. Serial numbers and downloads are tied to the customer.
 * You may modify this code for your own licensed store; keep this notice.
 * Not allowed: copying to other stores, redistributing, reselling, sublicensing, publishing it publicly,
 * removing licence checks or notices, sharing serial numbers. Unauthorised use: the serial number is
 * revoked without refund, updates and support stop, damages and legal action may follow.
 * Estonian law applies; court: Harju County Court (Harju Maakohus).
 */
declare(strict_types=1);

namespace MageBooster\Contract\Plugin;

use MageBooster\Contract\Model\Mail\AttachmentContext;
use MageBooster\Contract\Model\Mail\Attacher;
use Magento\Framework\Mail\TransportInterface;

/**
 * Attaches the contract files to the mails sent while an order confirmation is being sent.
 */
class AttachToOrderEmail
{
    /** @var array<int, true> message object ids already handled (a retry must not attach twice) */
    private array $done = [];

    public function __construct(
        private AttachmentContext $context,
        private Attacher $attacher
    ) {
    }

    public function beforeSendMessage(TransportInterface $subject): void
    {
        $order = $this->context->current();
        if ($order === null) {
            return;
        }
        $message = $subject->getMessage();
        if (!is_object($message)) {
            return;
        }
        $id = spl_object_id($message);
        if (isset($this->done[$id])) {
            return;
        }
        $this->done[$id] = true;
        $this->attacher->attach($message, $order);
    }
}
