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

use Magento\CheckoutAgreements\Api\Data\AgreementInterface;
use Magento\Framework\Escaper;

/**
 * Replaces the {{TAG}} placeholders in an agreement text with the given values.
 * Unknown tags are left as they are so a missing field is easy to spot.
 */
class TagRenderer
{
    public const PATTERN = '/\{\{\s*([A-Z0-9_]+)\s*\}\}/';

    public function __construct(private Escaper $escaper)
    {
    }

    /**
     * @param array<string, string> $values
     */
    public function render(AgreementInterface $agreement, array $values): string
    {
        $content = (string)$agreement->getContent();
        if (!$agreement->getIsHtml()) {
            $content = nl2br((string)$this->escaper->escapeHtml($content));
        }

        return $this->replace($content, $values);
    }

    /**
     * @param array<string, string> $values
     */
    public function replace(string $content, array $values): string
    {
        return (string)preg_replace_callback(
            self::PATTERN,
            static fn(array $m): string => $values[$m[1]] ?? $m[0],
            $content
        );
    }
}
