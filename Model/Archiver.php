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

namespace MageBooster\Contract\Model;

use MageBooster\Contract\Model\ResourceModel\Record as RecordResource;
use MageBooster\Contract\Model\ResourceModel\Record\CollectionFactory as RecordCollectionFactory;
use Magento\CheckoutAgreements\Model\Agreement;
use Magento\CheckoutAgreements\Model\ResourceModel\Agreement\CollectionFactory as AgreementCollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Fills the agreements of an order once and stores them; later calls return the stored records.
 *
 * A record is made when the order is placed and never changes afterwards: if the agreement text or the
 * seller details are edited later, the text the customer accepted stays the same (checkable by content_hash).
 */
class Archiver
{
    private const PDF_DIR = 'magebooster_contract';

    public function __construct(
        private Config $config,
        private Variables $variables,
        private TagRenderer $tagRenderer,
        private PdfGenerator $pdfGenerator,
        private AgreementCollectionFactory $agreementCollectionFactory,
        private RecordFactory $recordFactory,
        private RecordResource $recordResource,
        private RecordCollectionFactory $recordCollectionFactory,
        private Filesystem $filesystem,
        private TimezoneInterface $timezone,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Creates the missing agreement records of the order and returns all of its records.
     *
     * @return Record[]
     */
    public function archive(Order $order, bool $isBackfill = false): array
    {
        if (!$order->getId() || !$this->config->isActive($order->getStoreId())) {
            return $this->getRecords((int)$order->getId());
        }

        $existing = [];
        foreach ($this->getRecords((int)$order->getId()) as $record) {
            $existing[(int)$record->getAgreementId()] = true;
        }

        $agreements = $this->agreementCollectionFactory->create()
            ->addStoreFilter((int)$order->getStoreId())
            ->addFieldToFilter('is_active', 1);

        $variables = null;
        foreach ($agreements as $agreement) {
            if (isset($existing[(int)$agreement->getAgreementId()])) {
                continue;
            }
            $variables ??= $this->variables->forOrder($order);
            $this->createRecord($order, $agreement, $variables, $isBackfill);
        }

        return $this->getRecords((int)$order->getId());
    }

    /**
     * @return Record[]
     */
    public function getRecords(int $orderId): array
    {
        if (!$orderId) {
            return [];
        }

        return array_values($this->recordCollectionFactory->create()
            ->addFieldToFilter('order_id', $orderId)
            ->setOrder('agreement_id', 'ASC')
            ->getItems());
    }

    /**
     * The download of a record: PDF when dompdf is installed, otherwise the stand-alone HTML document.
     *
     * @return array{name: string, type: string, body: string}
     */
    public function getDocument(Record $record): array
    {
        if ($this->pdfGenerator->isAvailable()) {
            try {
                return ['name' => $this->getFileName($record, 'pdf'), 'type' => 'application/pdf', 'body' => $this->getPdf($record)];
            } catch (\Throwable $e) {
                $this->logger->error('MageBooster_Contract: PDF could not be created', ['record' => $record->getId(), 'exception' => $e]);
            }
        }

        return $this->getHtmlDocument($record);
    }

    /**
     * @return array{name: string, type: string, body: string}
     */
    public function getHtmlDocument(Record $record): array
    {
        return [
            'name' => $this->getFileName($record, 'html'),
            'type' => 'text/html',
            'body' => $this->pdfGenerator->document((string)$record->getAgreementName(), (string)$record->getContent(), $this->footer($record)),
        ];
    }

    /**
     * PDF of the record; made again from the stored HTML if the file was deleted.
     */
    public function getPdf(Record $record): string
    {
        $var = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $path = (string)$record->getPdfPath();
        if ($path !== '' && $this->isOwnPath($path) && $var->isFile($path)) {
            return $var->readFile($path);
        }

        $pdf = $this->pdfGenerator->render((string)$record->getAgreementName(), (string)$record->getContent(), $this->footer($record));
        $path = $this->pdfPath((string)$record->getIncrementId(), (int)$record->getAgreementId(), (string)$record->getCreatedAt());
        $var->writeFile($path, $pdf);
        $record->setData('pdf_path', $path);
        $this->recordResource->save($record);

        return $pdf;
    }

    public function getFileName(Record $record, string $extension): string
    {
        $name = trim((string)$record->getAgreementName()) ?: 'Contract';
        $name = trim((string)preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', ' ', $name)) ?: 'Contract';

        return sprintf('%s - %s.%s', $name, $record->getIncrementId(), $extension);
    }

    /** ASCII-only file name (Turkish letters transliterated) for headers and mail attachments. */
    public static function asciiName(string $name): string
    {
        $map = ['ç' => 'c', 'Ç' => 'C', 'ğ' => 'g', 'Ğ' => 'G', 'ı' => 'i', 'İ' => 'I',
            'ö' => 'o', 'Ö' => 'O', 'ş' => 's', 'Ş' => 'S', 'ü' => 'u', 'Ü' => 'U'];

        return (string)preg_replace('/[^A-Za-z0-9 ._-]/', '_', strtr($name, $map));
    }

    /**
     * @param array<string, string> $variables
     */
    private function createRecord(Order $order, Agreement $agreement, array $variables, bool $isBackfill): void
    {
        $content = $this->tagRenderer->render($agreement, $variables);

        /** @var Record $record */
        $record = $this->recordFactory->create();
        $record->setData([
            'order_id' => (int)$order->getId(),
            'increment_id' => (string)$order->getIncrementId(),
            'store_id' => (int)$order->getStoreId(),
            'agreement_id' => (int)$agreement->getAgreementId(),
            'agreement_name' => (string)$agreement->getName(),
            'content' => $content,
            'content_hash' => hash('sha256', $content),
            'customer_ip' => (string)$order->getRemoteIp(),
            'is_backfilled' => $isBackfill ? 1 : 0,
        ]);

        try {
            $this->recordResource->save($record);
        } catch (AlreadyExistsException $e) {
            // Another request (for example the order email) created the record at the same time.
            return;
        }

        if (!$this->pdfGenerator->isAvailable()) {
            return;
        }
        try {
            $this->recordResource->load($record, $record->getId());
            $this->getPdf($record);
        } catch (\Throwable $e) {
            // The PDF can be made later by getPdf(); the stored HTML is the record.
            $this->logger->error('MageBooster_Contract: PDF could not be created', [
                'order' => $order->getIncrementId(),
                'agreement' => $agreement->getAgreementId(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function footer(Record $record): array
    {
        $footer = [
            (string)__('Order number') => (string)$record->getIncrementId(),
            (string)__('Recorded at') => $this->timezone->date(new \DateTime((string)$record->getCreatedAt()))->format('d.m.Y H:i:s'),
            (string)__('Customer IP') => (string)$record->getCustomerIp(),
            (string)__('Document hash (SHA-256)') => (string)$record->getContentHash(),
        ];
        if ($record->getIsBackfilled()) {
            $footer[(string)__('Note')] = (string)__('This document was created after the order was placed, from the order data.');
        }

        return $footer;
    }

    private function isOwnPath(string $path): bool
    {
        return str_starts_with($path, self::PDF_DIR . '/') && !str_contains($path, '..');
    }

    private function pdfPath(string $incrementId, int $agreementId, string $createdAt): string
    {
        $date = new \DateTime($createdAt ?: 'now');
        $safeIncrement = preg_replace('/[^A-Za-z0-9_-]/', '_', $incrementId);

        return sprintf('%s/%s/%s-%d.pdf', self::PDF_DIR, $date->format('Y/m'), $safeIncrement, $agreementId);
    }
}
