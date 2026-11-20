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

namespace MageBooster\Contract\Block\Adminhtml\Order\View\Tab;

use MageBooster\Contract\Model\Archiver;
use MageBooster\Contract\Model\Record;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;

class Contracts extends Template implements TabInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        private Registry $registry,
        private Archiver $archiver,
        private TimezoneInterface $timezone,
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
        return $this->archiver->getRecords((int)$this->getOrder()?->getId());
    }

    public function formatCreatedAt(Record $record): string
    {
        return $this->timezone->date(new \DateTime((string)$record->getCreatedAt()))->format('d.m.Y H:i:s');
    }

    public function getViewUrl(Record $record): string
    {
        return $this->getUrl('mbcontract/record/view', ['id' => $record->getId()]);
    }

    public function getDownloadUrl(Record $record): string
    {
        return $this->getUrl('mbcontract/record/download', ['id' => $record->getId()]);
    }

    public function getGenerateUrl(): string
    {
        return $this->getUrl('mbcontract/record/generate', ['order_id' => $this->getOrder()?->getId()]);
    }

    public function canGenerate(): bool
    {
        return $this->_authorization->isAllowed('MageBooster_Contract::generate');
    }

    public function getTabLabel()
    {
        return __('Contracts');
    }

    public function getTabTitle()
    {
        return __('Contracts');
    }

    public function canShowTab()
    {
        return $this->_authorization->isAllowed('MageBooster_Contract::view');
    }

    public function isHidden()
    {
        return false;
    }
}
