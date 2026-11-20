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

namespace MageBooster\Contract\Model\Mail;

use MageBooster\Contract\Model\Archiver;
use MageBooster\Contract\Model\Config;
use MageBooster\Contract\Model\PdfGenerator;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Adds the order's contract files to an outgoing mail message.
 *
 * Magento 2.4.8+ builds mails with symfony/mime; 2.4.4 - 2.4.7 with laminas-mime. Both are handled; any
 * failure is logged and the mail is sent without the attachment, never blocked.
 */
class Attacher
{
    public function __construct(
        private Config $config,
        private Archiver $archiver,
        private PdfGenerator $pdfGenerator,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @return array<int, array{name: string, type: string, body: string}>
     */
    public function files(Order $order): array
    {
        $storeId = $order->getStoreId();
        $pdf = $this->config->attachPdf($storeId);
        $html = $this->config->attachHtml($storeId);
        if (!$pdf && !$html) {
            return [];
        }

        $files = [];
        foreach ($this->archiver->archive($order) as $record) {
            if ($pdf) {
                $file = $this->archiver->getDocument($record);
                // Without dompdf getDocument() falls back to HTML; do not attach the same HTML twice.
                if ($file['type'] === 'application/pdf' || !$html) {
                    $files[] = $file;
                }
            }
            if ($html) {
                $files[] = $this->archiver->getHtmlDocument($record);
            }
        }

        return $files;
    }

    /**
     * @param object $message Magento\Framework\Mail\EmailMessageInterface
     */
    public function attach(object $message, Order $order): void
    {
        try {
            $files = $this->files($order);
            if (!$files) {
                return;
            }
            if (method_exists($message, 'getSymfonyMessage')) {
                $this->attachSymfony($message->getSymfonyMessage(), $files);
            } else {
                $this->attachLaminas($message, $files);
            }
        } catch (\Throwable $e) {
            $this->logger->error('MageBooster_Contract: contract could not be attached to the order email', [
                'order' => $order->getIncrementId(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * @param array<int, array{name: string, type: string, body: string}> $files
     */
    private function attachSymfony(object $symfonyMessage, array $files): void
    {
        $parts = [$symfonyMessage->getBody()];
        foreach ($files as $file) {
            $parts[] = new \Symfony\Component\Mime\Part\DataPart($file['body'], Archiver::asciiName($file['name']), $file['type']);
        }
        $symfonyMessage->setBody(new \Symfony\Component\Mime\Part\Multipart\MixedPart(...$parts));
    }

    /**
     * Magento 2.4.4 - 2.4.7 (laminas-mail).
     *
     * @param array<int, array{name: string, type: string, body: string}> $files
     */
    private function attachLaminas(object $message, array $files): void
    {
        $body = $message->getBody();
        if (!$body instanceof \Laminas\Mime\Message) {
            return;
        }
        foreach ($files as $file) {
            $part = new \Laminas\Mime\Part($file['body']);
            $part->setType($file['type']);
            $part->setFileName(Archiver::asciiName($file['name']));
            $part->setDisposition(\Laminas\Mime\Mime::DISPOSITION_ATTACHMENT);
            $part->setEncoding(\Laminas\Mime\Mime::ENCODING_BASE64);
            $body->addPart($part);
        }
        // Re-set the body so laminas-mail rewrites the Content-Type header as multipart/mixed.
        $message->setBody($body);
    }
}
