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

use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;

/**
 * Returns the agreement PDF (or the HTML when no PDF library is installed) as a download, without a temp file.
 *
 * FileFactory rejects non-ASCII file names, so an ASCII fallback name and an RFC 5987 UTF-8 name are both sent.
 */
class PdfResponse
{
    public function __construct(
        private ResultFactory $resultFactory,
        private Archiver $archiver
    ) {
    }

    public function create(Record $record): Raw
    {
        $file = $this->archiver->getDocument($record);

        /** @var Raw $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHeader('Content-Type', $file['type'], true);
        $result->setHeader(
            'Content-Disposition',
            sprintf(
                'attachment; filename="%s"; filename*=UTF-8\'\'%s',
                Archiver::asciiName($file['name']),
                rawurlencode($file['name'])
            ),
            true
        );
        $result->setHeader('Content-Length', (string)strlen($file['body']), true);
        $result->setHeader('Cache-Control', 'private, no-store', true);
        $result->setHeader('X-Content-Type-Options', 'nosniff', true);
        $result->setHeader('X-Robots-Tag', 'noindex', true);

        return $result->setContents($file['body']);
    }
}
