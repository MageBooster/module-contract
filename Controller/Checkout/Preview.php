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

namespace MageBooster\Contract\Controller\Checkout;

use MageBooster\Contract\Model\CheckoutPreview;
use MageBooster\Contract\Model\InvoiceInfo;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface;

/**
 * POST mbcontract/checkout/preview
 * When the agreement window opens, returns the text filled with the cart and the (not yet saved) form data.
 * Only the session's own cart is used; nothing is saved.
 */
class Preview implements HttpPostActionInterface
{
    private const SCALAR_FIELDS = [
        'firstname', 'lastname', 'company', 'city', 'region', 'postcode',
        'country_id', 'countryId', 'telephone', 'vat_id', 'vatId', InvoiceInfo::PREVIEW_KEY,
    ];
    private const MAX_JSON_BYTES = 8192;
    private const MAX_STREET_LINES = 4;
    private const MAX_VALUE_LENGTH = 255;

    public function __construct(
        private RequestInterface $request,
        private JsonFactory $jsonFactory,
        private FormKeyValidator $formKeyValidator,
        private CheckoutSession $checkoutSession,
        private CheckoutPreview $preview,
        private RemoteAddress $remoteAddress,
        private JsonSerializer $json,
        private LoggerInterface $logger
    ) {
    }

    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $result->setHeader('Cache-Control', 'no-store', true);
        if (!$this->formKeyValidator->validate($this->request)) {
            return $result->setHttpResponseCode(403)->setData(['success' => false]);
        }

        try {
            $quote = $this->checkoutSession->getQuote();
            if (!$quote->getId() || !$quote->getItemsCount()) {
                return $result->setData(['success' => false]);
            }

            $contents = $this->preview->render(
                $quote,
                (int)$this->request->getParam('agreement_id') ?: null,
                $this->address('billing'),
                $this->address('shipping'),
                $this->email(),
                (string)$this->remoteAddress->getRemoteAddress()
            );

            return $result->setData(['success' => true, 'agreements' => (object)$contents]);
        } catch (\Throwable $e) {
            $this->logger->error('MageBooster_Contract: checkout preview failed', ['exception' => $e]);

            return $result->setData(['success' => false]);
        }
    }

    /**
     * Address from the form: known fields only, scalar values, limited length and number of street lines.
     *
     * @return array<string, mixed>
     */
    private function address(string $type): array
    {
        $raw = $this->request->getParam($type);
        if (!is_string($raw) || $raw === '' || strlen($raw) > self::MAX_JSON_BYTES) {
            return [];
        }
        try {
            $raw = $this->json->unserialize($raw);
        } catch (\InvalidArgumentException $e) {
            return [];
        }
        if (!is_array($raw)) {
            return [];
        }

        $data = [];
        foreach (self::SCALAR_FIELDS as $field) {
            $value = $raw[$field] ?? null;
            if (is_array($value) && $field === 'region') {
                // Knockout region object: {region: 'Izmir', region_id: ...}
                $value = $value['region'] ?? null;
            }
            if (is_scalar($value)) {
                $data[$field] = mb_substr((string)$value, 0, self::MAX_VALUE_LENGTH);
            }
        }

        $street = $raw['street'] ?? null;
        if (is_array($street)) {
            $lines = array_slice(array_values(array_filter($street, 'is_scalar')), 0, self::MAX_STREET_LINES);
            $data['street'] = array_map(static fn($line): string => mb_substr((string)$line, 0, self::MAX_VALUE_LENGTH), $lines);
        } elseif (is_scalar($street)) {
            $data['street'] = [mb_substr((string)$street, 0, self::MAX_VALUE_LENGTH)];
        }

        return $data;
    }

    private function email(): string
    {
        $email = trim((string)$this->request->getParam('email'));

        return strlen($email) <= self::MAX_VALUE_LENGTH && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }
}
