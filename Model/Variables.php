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

use Magento\Directory\Model\CountryFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;

/**
 * Values of the {{TAG}} placeholders in a contract.
 *
 * forOrder() feeds the permanent record, forQuote() the checkout preview. Every value is HTML-escaped,
 * except ORDER_DETAILS, which is a table built here from escaped parts.
 */
class Variables
{
    private const DATE_FORMAT = 'd.m.Y';
    private const TIME_FORMAT = 'H:i:s';

    public function __construct(
        private Config $config,
        private TimezoneInterface $timezone,
        private CountryFactory $countryFactory,
        private PriceCurrencyInterface $priceCurrency,
        private Escaper $escaper,
        private InvoiceInfo $invoice,
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @return array<string, string> TAG => value (without braces)
     */
    public function forOrder(Order $order): array
    {
        $billing = $order->getBillingAddress();

        return $this->build([
            'store_id' => (int)$order->getStoreId(),
            'billing' => $billing,
            'shipping' => $order->getShippingAddress() ?: $billing,
            'email' => (string)$order->getCustomerEmail(),
            'ip' => (string)$order->getRemoteIp(),
            'created_at' => $this->timezone->date(new \DateTime((string)$order->getCreatedAt())),
            'cart_id' => (string)$order->getQuoteId(),
            'increment_id' => (string)$order->getIncrementId(),
            'grand_total' => (float)$order->getGrandTotal(),
            'subtotal' => (float)$order->getSubtotalInclTax(),
            'shipping_total' => (float)$order->getShippingInclTax(),
            'tax' => (float)$order->getTaxAmount(),
            'discount' => abs((float)$order->getDiscountAmount()),
            'cod_fee' => $this->cashOnDeliveryFee($order),
            'items' => array_map(static fn($item): array => [
                'name' => (string)$item->getName(),
                'sku' => (string)$item->getSku(),
                'qty' => (float)$item->getQtyOrdered(),
                'price' => (float)$item->getPriceInclTax(),
                'row_total' => (float)$item->getRowTotalInclTax(),
            ], array_values($order->getAllVisibleItems())),
            'item_qty' => (float)$order->getTotalQtyOrdered(),
            'payment' => $order->getPayment(),
            'base_currency' => (string)$order->getBaseCurrencyCode(),
            'store_currency' => (string)$order->getStoreCurrencyCode(),
            'currency' => (string)$order->getOrderCurrencyCode(),
            'coupon' => (string)$order->getCouponCode(),
            'format_price' => static fn(float $amount): string => (string)$order->getOrderCurrency()->formatTxt($amount),
        ]);
    }

    /**
     * Checkout preview: cart items and totals plus what the customer has typed in the form so far (not saved yet).
     *
     * @param array<string, mixed> $billingData  billing address from the form (snake_case or camelCase keys)
     * @param array<string, mixed> $shippingData shipping address from the form
     * @return array<string, string>
     */
    public function forQuote(
        Quote $quote,
        array $billingData = [],
        array $shippingData = [],
        string $email = '',
        string $ip = '',
        bool $useStoredCustomerData = true
    ): array {
        $totalsSource = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
        $storedBilling = $useStoredCustomerData ? $quote->getBillingAddress() : new DataObject();
        $storedShipping = $useStoredCustomerData ? $quote->getShippingAddress() : new DataObject();
        $billing = $this->mergeAddress($storedBilling, $billingData ?: $shippingData);
        $shipping = $quote->isVirtual() ? $billing : $this->mergeAddress($storedShipping, $shippingData);
        $storedEmail = $useStoredCustomerData
            ? (string)($quote->getCustomerEmail() ?: $quote->getBillingAddress()->getEmail())
            : '';
        $storeId = (int)$quote->getStoreId();
        $currency = (string)$quote->getQuoteCurrencyCode();

        return $this->build([
            'store_id' => $storeId,
            'billing' => $billing,
            'shipping' => $shipping,
            'email' => $email ?: $storedEmail,
            'ip' => $ip ?: (string)$quote->getRemoteIp(),
            'created_at' => $this->timezone->date(),
            'cart_id' => (string)$quote->getId(),
            'increment_id' => (string)$quote->getReservedOrderId(),
            'grand_total' => (float)$quote->getGrandTotal(),
            'subtotal' => (float)$totalsSource->getSubtotalInclTax(),
            'shipping_total' => (float)$totalsSource->getShippingInclTax(),
            'tax' => (float)$totalsSource->getTaxAmount(),
            'discount' => abs((float)$totalsSource->getDiscountAmount()),
            'cod_fee' => $this->cashOnDeliveryFee($totalsSource),
            'items' => array_map(static fn($item): array => [
                'name' => (string)$item->getName(),
                'sku' => (string)$item->getSku(),
                'qty' => (float)$item->getQty(),
                'price' => (float)$item->getPriceInclTax(),
                'row_total' => (float)$item->getRowTotalInclTax(),
            ], array_values($quote->getAllVisibleItems())),
            'item_qty' => (float)$quote->getItemsQty(),
            'payment' => $quote->getPayment()->getMethod() ? $quote->getPayment() : null,
            'base_currency' => (string)$quote->getBaseCurrencyCode(),
            'store_currency' => (string)$quote->getStoreCurrencyCode(),
            'currency' => $currency,
            'coupon' => (string)$quote->getCouponCode(),
            'format_price' => fn(float $amount): string => (string)$this->priceCurrency->format($amount, false, 2, $storeId, $currency),
        ]);
    }

    /**
     * @param array<string, mixed> $d
     * @return array<string, string>
     */
    private function build(array $d): array
    {
        $storeId = $d['store_id'];
        $billing = $d['billing'];
        $shipping = $d['shipping'];
        $price = $d['format_price'];
        $now = $this->timezone->date();
        $created = $d['created_at'];
        $locale = (string)$this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $storeId);
        $seller = fn(string $field): string => $this->config->getSellerValue($field, $storeId);

        $plain = [
            'COMPANY_NAME' => $seller('name'),
            'COMPANY_SHORT_NAME' => $seller('short_name'),
            'COMPANY_ADDRESS' => $seller('address'),
            'COMPANY_CITY' => $seller('city'),
            'COMPANY_STATE' => $seller('state'),
            'COMPANY_COUNTRY' => $seller('country'),
            'COMPANY_PHONE' => $seller('telephone'),
            'COMPANY_FAX' => $seller('fax'),
            'COMPANY_WEBSITE' => $seller('website'),
            'COMPANY_EMAIL' => $seller('email'),
            'COMPANY_TAX_OFFICE' => $seller('tax_office'),
            'COMPANY_TAX_NUMBER' => $seller('tax_number'),
            'COMPANY_MERSIS' => $seller('mersis'),

            'CUSTOMER_NAME' => $this->invoice->buyerName($billing),
            'CUSTOMER_TCK' => (string)$billing?->getData('vat_id'),
            'CUSTOMER_ID_LINE' => $this->invoice->idLine($billing, $storeId),
            'CUSTOMER_INVOICE_TYPE' => $this->invoice->type($billing),
            'CUSTOMER_COMPANY' => trim((string)$billing?->getData('company')),
            'CUSTOMER_TAX_OFFICE' => $this->invoice->taxOffice($billing, $storeId),
            'CUSTOMER_ADDRESS' => $this->address($billing, $locale),
            'CUSTOMER_CITY' => (string)$billing?->getData('city'),
            'CUSTOMER_STATE' => (string)$billing?->getData('region'),
            'CUSTOMER_COUNTRY' => $this->country($billing, $locale),
            'CUSTOMER_ZIP' => (string)$billing?->getData('postcode'),
            'CUSTOMER_PHONE' => (string)$billing?->getData('telephone'),
            'CUSTOMER_EMAIL' => $d['email'],
            'CUSTOMER_IP' => $d['ip'],

            'SHIPPING_NAME' => $this->name($shipping),
            'SHIPPING_ADDRESS' => $this->address($shipping, $locale),
            'SHIPPING_CITY' => (string)$shipping?->getData('city'),
            'SHIPPING_STATE' => (string)$shipping?->getData('region'),
            'SHIPPING_COUNTRY' => $this->country($shipping, $locale),

            'DATE' => $now->format(self::DATE_FORMAT),
            'DATE_TIME' => $now->format(self::DATE_FORMAT . ' ' . self::TIME_FORMAT),
            'ORDER_DATE' => $created->format(self::DATE_FORMAT),
            'ORDER_TIME' => $created->format(self::TIME_FORMAT),
            'ORDER_DATE_TIME' => $created->format(self::DATE_FORMAT . ' ' . self::TIME_FORMAT),
            'CART_ID' => $d['cart_id'],

            'ORDER_GRAND_TOTAL' => $price($d['grand_total']),
            'SHIPPING_TOTAL' => $price($d['shipping_total']),
            'CASH_ON_DELIVERY_FEE' => $price($d['cod_fee']),
            'ORDER_TAX_TOTAL' => $price($d['tax']),
            'DISCOUNT_TOTAL' => $price($d['discount']),
            'ORDER_SUB_TOTAL' => $price($d['subtotal']),
            'ORDER_PRODS_TOTAL' => $price($d['subtotal']),
            'ORDER_SUB_TOTAL_W_DISCOUNT' => $price(max(0.0, $d['subtotal'] - $d['discount'])),
            'PAYMENT_METHOD' => $this->paymentTitle($d['payment']),
            'PAYMENT_DETAILS' => $this->paymentDetails($d['payment']),

            'BASE_CURRENCY' => $d['base_currency'],
            'STORE_CURRENCY' => $d['store_currency'],
            'ORDER_CURRENCY' => $d['currency'],
            'COUPON_CODE' => $d['coupon'],

            'ORDER_INC_ID' => $d['increment_id'],
            'ORDER_LINE_COUNT' => (string)count($d['items']),
            'ORDER_PROD_COUNT' => (string)(int)$d['item_qty'],
        ];

        $values = array_map(fn(string $v): string => (string)$this->escaper->escapeHtml($v), $plain);
        $values['ORDER_DETAILS'] = $this->orderDetailsHtml($d);

        return $values;
    }

    /**
     * Applies the form fields to a copy of the address; the cart address itself is not changed.
     *
     * @param array<string, mixed> $data
     */
    private function mergeAddress(DataObject $address, array $data): DataObject
    {
        $copy = new DataObject($address->getData());
        $map = [
            'firstname' => 'firstname', 'lastname' => 'lastname', 'company' => 'company',
            'street' => 'street', 'city' => 'city', 'region' => 'region', 'postcode' => 'postcode',
            'country_id' => 'country_id', 'countryId' => 'country_id', 'telephone' => 'telephone',
            'vat_id' => 'vat_id', 'vatId' => 'vat_id', InvoiceInfo::PREVIEW_KEY => InvoiceInfo::PREVIEW_KEY,
        ];
        foreach ($map as $from => $to) {
            if (!array_key_exists($from, $data) || $data[$from] === null || $data[$from] === '') {
                continue;
            }
            $value = $data[$from];
            if ($to === 'street') {
                $value = array_values(array_filter(array_map('strval', (array)$value), 'strlen'));
            } elseif (is_array($value)) {
                $value = (string)($value['region'] ?? reset($value));
            }
            $copy->setData($to, is_array($value) ? $value : trim((string)$value));
        }
        if (is_string($copy->getData('street'))) {
            $copy->setData('street', explode("\n", $copy->getData('street')));
        }

        return $copy;
    }

    private function name(?DataObject $address): string
    {
        if (!$address) {
            return '';
        }
        $name = trim($address->getData('firstname') . ' ' . $address->getData('lastname'));
        $company = trim((string)$address->getData('company'));

        return $company !== '' ? trim($name . ' / ' . $company, ' /') : $name;
    }

    private function address(?DataObject $address, string $locale): string
    {
        if (!$address) {
            return '';
        }
        $street = $address->getData('street');
        $street = is_array($street) ? $street : explode("\n", (string)$street);
        $street = implode(' ', array_filter(array_map('trim', array_map('strval', $street))));
        $cityLine = implode(' / ', array_filter([(string)$address->getData('city'), (string)$address->getData('region')]));
        $parts = array_filter([$street, trim($address->getData('postcode') . ' ' . $cityLine), $this->country($address, $locale)]);

        return implode(', ', $parts);
    }

    private function country(?DataObject $address, string $locale): string
    {
        $code = (string)$address?->getData('country_id');
        if ($code === '') {
            return '';
        }

        return (string)$this->countryFactory->create()->loadByCode($code)->getName($locale ?: null);
    }

    private function paymentTitle(?DataObject $payment): string
    {
        if (!$payment) {
            return '';
        }
        $info = (array)$payment->getAdditionalInformation();
        if (!empty($info['method_title']) && is_scalar($info['method_title'])) {
            return (string)$info['method_title'];
        }
        try {
            return (string)$payment->getMethodInstance()->getTitle();
        } catch (\Throwable $e) {
            return (string)$payment->getMethod();
        }
    }

    private function paymentDetails(?DataObject $payment): string
    {
        $details = [$this->paymentTitle($payment)];
        $info = $payment ? (array)$payment->getAdditionalInformation() : [];
        foreach (['installment', 'installment_count', 'installments', 'taksit'] as $key) {
            if (!empty($info[$key]) && is_scalar($info[$key]) && (int)$info[$key] > 1) {
                $details[] = (string)__('%1 instalments', (int)$info[$key]);
                break;
            }
        }

        return implode(', ', array_filter($details));
    }

    /** Fee added by a cash-on-delivery module, if one is installed; otherwise 0. */
    private function cashOnDeliveryFee(DataObject $source): float
    {
        foreach (['cash_on_delivery_fee', 'cod_fee', 'msp_cod_amount'] as $key) {
            if ($source->hasData($key)) {
                return (float)$source->getData($key);
            }
        }

        return 0.0;
    }

    /**
     * @param array<string, mixed> $d
     */
    private function orderDetailsHtml(array $d): string
    {
        $price = $d['format_price'];
        $esc = fn(string $v): string => (string)$this->escaper->escapeHtml($v);
        $cell = 'border:1px solid #ccc;padding:4px 6px;';
        $rows = '';
        foreach ($d['items'] as $item) {
            $rows .= sprintf(
                '<tr><td style="%1$s">%2$s</td><td style="%1$s">%3$s</td><td style="%1$stext-align:center">%4$s</td>'
                . '<td style="%1$stext-align:right">%5$s</td><td style="%1$stext-align:right">%6$s</td></tr>',
                $cell,
                $esc($item['name']),
                $esc($item['sku']),
                $esc((string)(float)$item['qty']),
                $esc($price($item['price'])),
                $esc($price($item['row_total']))
            );
        }

        $summary = [
            [(string)__('Subtotal'), $d['subtotal']],
            [(string)__('Shipping'), $d['shipping_total']],
        ];
        if ($d['discount'] > 0) {
            $summary[] = [(string)__('Discount'), -$d['discount']];
        }
        if ($d['cod_fee'] > 0) {
            $summary[] = [(string)__('Cash on delivery fee'), $d['cod_fee']];
        }
        $summary[] = [(string)__('Grand Total'), $d['grand_total']];

        foreach ($summary as [$label, $amount]) {
            $rows .= sprintf(
                '<tr><td colspan="4" style="%1$stext-align:right"><strong>%2$s</strong></td>'
                . '<td style="%1$stext-align:right"><strong>%3$s</strong></td></tr>',
                $cell,
                $esc($label),
                $esc($price((float)$amount))
            );
        }

        return sprintf(
            '<table style="border-collapse:collapse;width:100%%;font-size:12px"><thead><tr>'
            . '<th style="%1$stext-align:left">%3$s</th><th style="%1$stext-align:left">%4$s</th>'
            . '<th style="%1$s">%5$s</th><th style="%1$stext-align:right">%6$s</th>'
            . '<th style="%1$stext-align:right">%7$s</th></tr></thead><tbody>%2$s</tbody></table>',
            $cell,
            $rows,
            $esc((string)__('Product')),
            $esc((string)__('SKU')),
            $esc((string)__('Qty')),
            $esc((string)__('Unit price')),
            $esc((string)__('Row total'))
        );
    }
}
