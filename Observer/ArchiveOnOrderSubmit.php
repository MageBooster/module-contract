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

namespace MageBooster\Contract\Observer;

use MageBooster\Contract\Model\Archiver;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Saves the contracts as soon as the order is placed, whatever the payment method.
 * With redirect payments the mail may go out later; the record still holds the data of the moment of ordering.
 */
class ArchiveOnOrderSubmit implements ObserverInterface
{
    public function __construct(
        private Archiver $archiver,
        private LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        $orders = [];
        $order = $observer->getEvent()->getData('order');
        if ($order) {
            $orders[] = $order;
        }
        foreach ((array)$observer->getEvent()->getData('orders') as $multiOrder) {
            $orders[] = $multiOrder;
        }

        foreach ($orders as $order) {
            if (!$order instanceof Order) {
                continue;
            }
            try {
                $this->archiver->archive($order);
            } catch (\Throwable $e) {
                // The contract archive must never block an order.
                $this->logger->critical('MageBooster_Contract: contracts could not be saved', [
                    'order' => $order->getIncrementId(),
                    'exception' => $e,
                ]);
            }
        }
    }
}
