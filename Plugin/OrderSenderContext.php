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

use MageBooster\Contract\Model\Archiver;
use MageBooster\Contract\Model\Config;
use MageBooster\Contract\Model\Mail\AttachmentContext;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Psr\Log\LoggerInterface;

/**
 * While the order confirmation is sent (right away or later by the async cron), remembers the order so
 * the transport plugin can attach the contracts. Also makes sure the records exist before the mail goes out.
 */
class OrderSenderContext
{
    public function __construct(
        private AttachmentContext $context,
        private Archiver $archiver,
        private Config $config,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param bool $forceSyncMode
     * @return bool
     */
    public function aroundSend(OrderSender $subject, callable $proceed, Order $order, $forceSyncMode = false)
    {
        if (!$this->config->isActive($order->getStoreId())) {
            return $proceed($order, $forceSyncMode);
        }
        try {
            $this->archiver->archive($order);
        } catch (\Throwable $e) {
            $this->logger->error('MageBooster_Contract: contracts could not be saved before the order email', [
                'order' => $order->getIncrementId(),
                'exception' => $e,
            ]);
        }

        $this->context->push($order);
        try {
            return $proceed($order, $forceSyncMode);
        } finally {
            $this->context->pop();
        }
    }
}
