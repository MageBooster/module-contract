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

use MageBooster\Contract\Model\Archiver;
use MageBooster\Contract\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Creates the contracts afterwards, for orders placed before the module was installed or enabled.
 */
class Generate extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageBooster_Contract::generate';

    public function __construct(
        Context $context,
        private OrderRepositoryInterface $orderRepository,
        private Archiver $archiver,
        private Config $config
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $orderId = (int)$this->getRequest()->getParam('order_id');

        try {
            if (!$this->config->isLicensed()) {
                $this->messageManager->addErrorMessage(
                    __('MageBooster Contract needs a valid serial number (Stores > Configuration > MageBooster > Licenses).')
                );
                return $this->_redirect('sales/order/view', ['order_id' => $orderId]);
            }
            $order = $this->orderRepository->get($orderId);
            $records = $this->archiver->archive($order, true);
            if ($records) {
                $this->messageManager->addSuccessMessage(__('%1 contract(s) created.', count($records)));
            } else {
                $this->messageManager->addWarningMessage(
                    __('There is no active agreement for the store of this order, or the module is disabled there.')
                );
            }
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('The contract could not be created: %1', $e->getMessage()));
        }

        return $this->_redirect('sales/order/view', ['order_id' => $orderId]);
    }
}
