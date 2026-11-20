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

use Magento\CheckoutAgreements\Model\ResourceModel\Agreement\CollectionFactory as AgreementCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\Quote;

/**
 * Fills the agreement texts shown at checkout with the cart (and what the customer has typed so far).
 * Saves nothing; the permanent record is made by Archiver when the order is placed.
 */
class CheckoutPreview
{
    public function __construct(
        private Config $config,
        private Variables $variables,
        private TagRenderer $tagRenderer,
        private AgreementCollectionFactory $agreementCollectionFactory,
        private CustomerSession $customerSession
    ) {
    }

    public function isEnabled(Quote $quote): bool
    {
        return $this->config->isActive($quote->getStoreId());
    }

    /**
     * @param array<string, mixed> $billingData
     * @param array<string, mixed> $shippingData
     * @return array<int, string> agreement_id => filled content
     */
    public function render(
        Quote $quote,
        ?int $agreementId = null,
        array $billingData = [],
        array $shippingData = [],
        string $email = '',
        string $ip = ''
    ): array {
        if (!$this->isEnabled($quote)) {
            return [];
        }

        $agreements = $this->agreementCollectionFactory->create()
            ->addStoreFilter((int)$quote->getStoreId())
            ->addFieldToFilter('is_active', 1);
        if ($agreementId) {
            $agreements->addFieldToFilter('agreement_id', $agreementId);
        }

        // Persistent cart (Magento_Persistent): a guest may be looking at another customer's remembered cart.
        // In that case the stored address, ID number and email are not used, only the form data and the items.
        $useStoredCustomerData = $this->customerSession->isLoggedIn() || !$quote->getCustomerId();
        $values = $this->variables->forQuote($quote, $billingData, $shippingData, $email, $ip, $useStoredCustomerData);
        $result = [];
        foreach ($agreements as $agreement) {
            $result[(int)$agreement->getAgreementId()] = $this->tagRenderer->render($agreement, $values);
        }

        return $result;
    }
}
