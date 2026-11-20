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

use MageBooster\Contract\Model\RecordFactory;
use MageBooster\Contract\Model\ResourceModel\Record as RecordResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Escaper;

class View extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MageBooster_Contract::view';

    public function __construct(
        Context $context,
        private RecordFactory $recordFactory,
        private RecordResource $recordResource,
        private Escaper $escaper
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $record = $this->recordFactory->create();
        $this->recordResource->load($record, (int)$this->getRequest()->getParam('id'));

        /** @var Raw $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        if (!$record->getId()) {
            return $result->setHttpResponseCode(404)->setContents((string)$this->escaper->escapeHtml(__('The contract record was not found.')));
        }

        $result->setHeader('Content-Type', 'text/html; charset=utf-8', true);
        $result->setHeader('X-Robots-Tag', 'noindex', true);
        $result->setHeader('Cache-Control', 'private, no-store', true);
        // The text is written by an admin and frozen in the record. The page is served without scripts and as a
        // separate origin (sandbox), so even a script in the text cannot run in the admin session.
        $result->setHeader(
            'Content-Security-Policy',
            "sandbox; default-src 'none'; style-src 'unsafe-inline'; img-src data:",
            true
        );
        $result->setHeader('X-Content-Type-Options', 'nosniff', true);

        return $result->setContents(sprintf(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><title>%s - %s</title>'
            . '<style>body{font-family:Arial,sans-serif;max-width:900px;margin:24px auto;padding:0 16px;font-size:14px;line-height:1.5}</style>'
            . '</head><body>%s<hr><p style="font-size:12px;color:#666">SHA-256: %s</p></body></html>',
            $this->escaper->escapeHtml((string)$record->getAgreementName()),
            $this->escaper->escapeHtml((string)$record->getIncrementId()),
            $record->getContent(),
            $this->escaper->escapeHtml((string)$record->getContentHash())
        ));
    }
}
