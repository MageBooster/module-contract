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

namespace MageBooster\Contract\Plugin;

use MageBooster\Contract\Model\CheckoutPreview;
use MageBooster\Contract\Model\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\CheckoutAgreements\Model\AgreementsConfigProvider;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;

/**
 * Luma checkout: fills window.checkoutConfig.checkoutAgreements with the cart when the page loads, so the
 * customer never sees raw {{TAGS}}. The latest form data is filled in by JS when the window is opened.
 */
class FillCheckoutAgreementsConfig
{
    public function __construct(
        private CheckoutSession $checkoutSession,
        private CheckoutPreview $preview,
        private Config $config,
        private RemoteAddress $remoteAddress,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    public function afterGetConfig(AgreementsConfigProvider $subject, array $result): array
    {
        if (empty($result['checkoutAgreements']['agreements'])) {
            return $result;
        }

        try {
            $contents = $this->preview->render(
                $this->checkoutSession->getQuote(),
                null,
                [],
                [],
                '',
                (string)$this->remoteAddress->getRemoteAddress()
            );
        } catch (\Throwable $e) {
            $this->logger->error('MageBooster_Contract: checkout preview could not be created', ['exception' => $e]);

            return $result;
        }

        foreach ($result['checkoutAgreements']['agreements'] as &$agreement) {
            $id = (int)($agreement['agreementId'] ?? 0);
            if (isset($contents[$id])) {
                $agreement['content'] = $contents[$id];
            }
        }
        unset($agreement);
        $result['checkoutAgreements']['mbContractPreview'] = (bool)$contents;
        $result['checkoutAgreements']['mbContractTaxOffice'] = $this->config->getTaxOfficeAttribute();

        return $result;
    }
}
