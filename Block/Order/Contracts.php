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

namespace MageBooster\Contract\Block\Order;

use MageBooster\Contract\Model\Archiver;
use MageBooster\Contract\Model\Config;
use MageBooster\Contract\Model\Record;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;

/**
 * Customer account > My Orders > order view: download links of the order's contracts.
 */
class Contracts extends Template
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        private Registry $registry,
        private Archiver $archiver,
        private Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getOrder(): ?Order
    {
        $order = $this->registry->registry('current_order');
        return $order instanceof Order ? $order : null;
    }

    /**
     * @return Record[]
     */
    public function getRecords(): array
    {
        $order = $this->getOrder();
        if (!$order || !$this->config->isEnabled($order->getStoreId())) {
            return [];
        }
        return $this->archiver->getRecords((int)$order->getId());
    }

    public function getDownloadUrl(Record $record): string
    {
        return $this->getUrl('mbcontract/order/download', ['id' => $record->getId()]);
    }
}
