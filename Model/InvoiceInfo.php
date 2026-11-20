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

use Magento\Framework\Api\CustomAttributesDataInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Invoice type of the buyer: corporate when the billing address has a company name, otherwise individual.
 */
class InvoiceInfo
{
    /** Key used by the checkout preview form data. */
    public const PREVIEW_KEY = 'tax_office';

    public function __construct(private Config $config)
    {
    }

    public function isCorporate(?DataObject $billing): bool
    {
        return $billing !== null && trim((string)$billing->getData('company')) !== '';
    }

    public function type(?DataObject $billing): string
    {
        return $this->isCorporate($billing) ? (string)__('Corporate') : (string)__('Individual');
    }

    public function personName(?DataObject $address): string
    {
        return $address ? trim($address->getData('firstname') . ' ' . $address->getData('lastname')) : '';
    }

    /** "ACME Ltd. (Authorised person: Jane Doe)" or "Jane Doe". */
    public function buyerName(?DataObject $billing): string
    {
        if (!$billing) {
            return '';
        }
        $name = $this->personName($billing);
        if (!$this->isCorporate($billing)) {
            return $name;
        }
        $company = trim((string)$billing->getData('company'));
        return $name !== '' ? (string)__('%1 (Authorised person: %2)', $company, $name) : $company;
    }

    /** "Tax office / Tax number: X / N" (corporate) or "ID number: N" (individual). */
    public function idLine(?DataObject $billing, int|string|null $storeId = null): string
    {
        if (!$billing) {
            return '';
        }
        $number = trim((string)$billing->getData('vat_id'));
        if ($this->isCorporate($billing)) {
            return (string)__('Tax office / Tax number: %1', trim($this->taxOffice($billing, $storeId) . ' / ' . $number, ' /'));
        }
        return (string)__('ID number: %1', $number);
    }

    /**
     * Tax office from the preview form, the address data or the configured custom address attribute.
     */
    public function taxOffice(?DataObject $address, int|string|null $storeId = null): string
    {
        if (!$address) {
            return '';
        }
        $value = $address->getData(self::PREVIEW_KEY);
        $attribute = $this->config->getTaxOfficeAttribute($storeId);
        if (($value === null || $value === '') && $attribute !== '') {
            $value = $address->getData($attribute);
            if ($value === null || $value === '') {
                if ($address instanceof CustomAttributesDataInterface) {
                    $value = $address->getCustomAttribute($attribute)?->getValue();
                } elseif (!$address instanceof AbstractExtensibleModel && is_array($address->getData('custom_attributes'))) {
                    $value = $address->getData('custom_attributes')[$attribute] ?? null;
                }
            }
        }
        if (is_array($value)) {
            $value = $value['value'] ?? '';
        }
        return is_scalar($value) ? trim((string)$value) : '';
    }
}
