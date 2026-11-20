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

namespace MageBooster\Contract\Setup\Patch\Data;

use Magento\CheckoutAgreements\Model\AgreementFactory;
use Magento\CheckoutAgreements\Model\ResourceModel\Agreement as AgreementResource;
use Magento\CheckoutAgreements\Model\ResourceModel\Agreement\CollectionFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Adds the two Turkish sample texts (Mesafeli Satış Sözleşmesi, Ön Bilgilendirme Formu) to Stores > Terms and
 * Conditions, INACTIVE: the store owner edits them, has them reviewed and turns them on. Existing texts with the
 * same name are left alone. Removing the module keeps the texts (they belong to Magento_CheckoutAgreements).
 */
class AddSampleAgreements implements DataPatchInterface
{
    private const SAMPLES = [
        'Mesafeli Satış Sözleşmesi' => [
            'file' => 'mesafeli-satis-sozlesmesi.html',
            'checkbox' => 'Mesafeli Satış Sözleşmesi\'ni okudum ve kabul ediyorum.',
        ],
        'Ön Bilgilendirme Formu' => [
            'file' => 'on-bilgilendirme-formu.html',
            'checkbox' => 'Ön Bilgilendirme Formu\'nu okudum ve kabul ediyorum.',
        ],
    ];

    public function __construct(
        private AgreementFactory $agreementFactory,
        private AgreementResource $agreementResource,
        private CollectionFactory $collectionFactory
    ) {
    }

    public function apply(): self
    {
        foreach (self::SAMPLES as $name => $sample) {
            $exists = $this->collectionFactory->create()->addFieldToFilter('name', $name)->getSize();
            if ($exists) {
                continue;
            }
            $content = file_get_contents(dirname(__DIR__, 2) . '/templates/' . $sample['file']);
            if ($content === false) {
                continue;
            }
            $agreement = $this->agreementFactory->create();
            $agreement->setData([
                'name' => $name,
                'content' => $content,
                'checkbox_text' => $sample['checkbox'],
                'is_active' => 0,
                'is_html' => 1,
                'mode' => 1,
                'stores' => [0],
            ]);
            $this->agreementResource->save($agreement);
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
