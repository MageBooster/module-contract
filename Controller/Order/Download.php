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

namespace MageBooster\Contract\Controller\Order;

use MageBooster\Contract\Model\Config;
use MageBooster\Contract\Model\PdfResponse;
use MageBooster\Contract\Model\RecordFactory;
use MageBooster\Contract\Model\ResourceModel\Record as RecordResource;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * GET mbcontract/order/download/id/{record}
 * Only the logged-in customer who owns the order can download it.
 */
class Download implements HttpGetActionInterface
{
    public function __construct(
        private RequestInterface $request,
        private CustomerSession $customerSession,
        private RecordFactory $recordFactory,
        private RecordResource $recordResource,
        private OrderRepositoryInterface $orderRepository,
        private PdfResponse $pdfResponse,
        private RedirectFactory $redirectFactory,
        private ResultFactory $resultFactory,
        private Config $config,
        private LoggerInterface $logger
    ) {
    }

    public function execute(): ResultInterface
    {
        if (!$this->customerSession->isLoggedIn()) {
            return $this->redirectFactory->create()->setPath('customer/account/login');
        }

        $record = $this->recordFactory->create();
        $this->recordResource->load($record, (int)$this->request->getParam('id'));

        $order = null;
        if ($record->getId()) {
            try {
                $order = $this->orderRepository->get((int)$record->getOrderId());
            } catch (\Throwable $e) {
                $order = null;
            }
        }

        $customerId = (int)$this->customerSession->getCustomerId();
        if (!$order
            || !$customerId
            || (int)$order->getCustomerId() !== $customerId
            || !$this->config->isEnabled($order->getStoreId())
        ) {
            // 404 for unauthorised access too, so the existence of a record is not revealed.
            return $this->notFound();
        }

        try {
            return $this->pdfResponse->create($record);
        } catch (\Throwable $e) {
            $this->logger->error('MageBooster_Contract: customer download failed', [
                'record' => $record->getId(),
                'exception' => $e,
            ]);

            return $this->notFound();
        }
    }

    private function notFound(): ResultInterface
    {
        return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('noroute');
    }
}
