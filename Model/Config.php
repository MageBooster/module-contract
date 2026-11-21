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

use MageBooster\License\Api\LicenseInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Settings of Stores > Configuration > MageBooster > Contract.
 *
 * Seller values fall back to Stores > Configuration > General > Store Information (and the general store email / base URL)
 * when the contract-specific field is empty.
 */
class Config
{
    public const SECTION = 'magebooster_contract/';
    public const DEFAULT_LICENCE_CODE = 'contract';

    /** @var string[] */
    private array $licenceCodes;

    private ?bool $licensed = null;

    /**
     * @param string[] $licenceCodes deprecated since 1.1.0: accepted serial codes are registered on MageBooster\\License\\Model\\Products
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private StoreManagerInterface $storeManager,
        private CountryFactory $countryFactory,
        private RegionFactory $regionFactory,
        private LicenseInterface $license,
        array $licenceCodes = []
    ) {
        $this->licenceCodes = array_values(array_unique(array_filter(array_map('strval', $licenceCodes)))) ?: [self::DEFAULT_LICENCE_CODE];
    }

    /** "Enabled" switch only (existing records stay viewable even when the serial lapses). */
    public function isEnabled(int|string|null $storeId = null): bool
    {
        return $this->flag('general/enabled', $storeId);
    }

    /**
     * Valid serial (contract or contract-hyva) or still within the 30-day grace period (MageBooster_License).
     */
    public function isLicensed(): bool
    {
        if ($this->licensed === null) {
            try {
                $this->licensed = $this->license->isActive(self::DEFAULT_LICENCE_CODE);
            } catch (\Throwable $e) {
                $this->licensed = false;
            }
        }
        return $this->licensed;
    }

    /** Enabled and licensed: contracts are filled, saved and attached. */
    public function isActive(int|string|null $storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->isLicensed();
    }

    public function attachPdf(int|string|null $storeId = null): bool
    {
        return $this->isActive($storeId) && $this->flag('general/attach_pdf', $storeId);
    }

    public function attachHtml(int|string|null $storeId = null): bool
    {
        return $this->isActive($storeId) && $this->flag('general/attach_html', $storeId);
    }

    /** Customer address attribute that holds the tax office (Turkish "vergi dairesi"), empty = none. */
    public function getTaxOfficeAttribute(int|string|null $storeId = null): string
    {
        $code = trim((string)$this->value('general/tax_office_attribute', $storeId));
        return preg_match('/^[a-z][a-z0-9_]{0,59}$/', $code) ? $code : '';
    }

    /**
     * Seller value for the {{COMPANY_*}} tags.
     */
    public function getSellerValue(string $field, int|string|null $storeId = null): string
    {
        $own = trim((string)$this->value('seller/' . $field, $storeId));
        if ($own !== '') {
            return $own;
        }
        return $this->fallback($field, $storeId);
    }

    private function fallback(string $field, int|string|null $storeId): string
    {
        $info = static fn(ScopeConfigInterface $c, string $key) => trim((string)$c->getValue(
            'general/store_information/' . $key,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
        switch ($field) {
            case 'name':
            case 'short_name':
                return $info($this->scopeConfig, 'name');
            case 'address':
                return trim($info($this->scopeConfig, 'street_line1') . ' ' . $info($this->scopeConfig, 'street_line2'));
            case 'city':
                return $info($this->scopeConfig, 'city');
            case 'state':
                $regionId = $info($this->scopeConfig, 'region_id');
                if ($regionId !== '' && ctype_digit($regionId)) {
                    $name = (string)$this->regionFactory->create()->load((int)$regionId)->getName();
                    if ($name !== '') {
                        return $name;
                    }
                }
                return $regionId;
            case 'country':
                $code = $info($this->scopeConfig, 'country_id');
                return $code === '' ? '' : (string)$this->countryFactory->create()->loadByCode($code)->getName();
            case 'telephone':
                return $info($this->scopeConfig, 'phone');
            case 'tax_number':
                return $info($this->scopeConfig, 'merchant_vat_number');
            case 'email':
                return trim((string)$this->scopeConfig->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE, $storeId));
            case 'website':
                try {
                    return rtrim((string)$this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB), '/');
                } catch (\Throwable $e) {
                    return '';
                }
            default:
                return '';
        }
    }

    private function flag(string $path, int|string|null $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(self::SECTION . $path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    private function value(string $path, int|string|null $storeId): mixed
    {
        return $this->scopeConfig->getValue(self::SECTION . $path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
