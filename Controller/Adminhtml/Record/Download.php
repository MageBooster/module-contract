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

namespace MageBooster\Contract\Controller\Adminhtml\Record;

use MageBooster\Contract\Model\PdfResponse;
use MageBooster\Contract\Model\RecordFactory;
use MageBooster\Contract\Model\ResourceModel\Record as RecordResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Download extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MageBooster_Contract::view';

    public function __construct(
        Context $context,
        private RecordFactory $recordFactory,
        private RecordResource $recordResource,
        private PdfResponse $pdfResponse
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $record = $this->recordFactory->create();
        $this->recordResource->load($record, (int)$this->getRequest()->getParam('id'));
        if (!$record->getId()) {
            $this->messageManager->addErrorMessage(__('The contract record was not found.'));

            return $this->_redirect('sales/order/index');
        }

        try {
            return $this->pdfResponse->create($record);
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('The document could not be created: %1', $e->getMessage()));

            return $this->_redirect('sales/order/view', ['order_id' => $record->getOrderId()]);
        }
    }
}
