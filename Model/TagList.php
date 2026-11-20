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

/**
 * Tags that can be used in an agreement text, with a description (admin help list).
 * Keys must match the output of Variables::build().
 */
class TagList
{
    /**
     * @return array<string, array<string, string>> group => [TAG => description]
     */
    public function getGroups(): array
    {
        return [
            (string)__('Seller (Stores > Configuration > MageBooster > Contract, falls back to Store Information)') => [
                'COMPANY_NAME' => (string)__('Company name'),
                'COMPANY_SHORT_NAME' => (string)__('Short company name'),
                'COMPANY_ADDRESS' => (string)__('Address'),
                'COMPANY_CITY' => (string)__('District / city'),
                'COMPANY_STATE' => (string)__('Province / region'),
                'COMPANY_COUNTRY' => (string)__('Country'),
                'COMPANY_PHONE' => (string)__('Telephone'),
                'COMPANY_FAX' => (string)__('Fax'),
                'COMPANY_WEBSITE' => (string)__('Website'),
                'COMPANY_EMAIL' => (string)__('Email'),
                'COMPANY_TAX_OFFICE' => (string)__('Tax office'),
                'COMPANY_TAX_NUMBER' => (string)__('Tax number'),
                'COMPANY_MERSIS' => (string)__('MERSIS number'),
            ],
            (string)__('Buyer (billing address)') => [
                'CUSTOMER_NAME' => (string)__('Individual: full name. Corporate: company name (Authorised person: full name)'),
                'CUSTOMER_ID_LINE' => (string)__('Individual: "ID number: ..." Corporate: "Tax office / Tax number: ... / ..."'),
                'CUSTOMER_INVOICE_TYPE' => (string)__('Invoice type: Individual / Corporate'),
                'CUSTOMER_COMPANY' => (string)__('Company name (empty for individuals)'),
                'CUSTOMER_TAX_OFFICE' => (string)__('Tax office (address attribute set in the settings)'),
                'CUSTOMER_TCK' => (string)__('ID / tax number (VAT number field of the address)'),
                'CUSTOMER_ADDRESS' => (string)__('Full address with postcode, city and country'),
                'CUSTOMER_CITY' => (string)__('City'),
                'CUSTOMER_STATE' => (string)__('Region / province'),
                'CUSTOMER_COUNTRY' => (string)__('Country'),
                'CUSTOMER_ZIP' => (string)__('Postcode'),
                'CUSTOMER_PHONE' => (string)__('Telephone'),
                'CUSTOMER_EMAIL' => (string)__('Email'),
                'CUSTOMER_IP' => (string)__('IP address the order was placed from'),
            ],
            (string)__('Shipping address') => [
                'SHIPPING_NAME' => (string)__('Recipient'),
                'SHIPPING_ADDRESS' => (string)__('Full address with postcode, city and country'),
                'SHIPPING_CITY' => (string)__('City'),
                'SHIPPING_STATE' => (string)__('Region / province'),
                'SHIPPING_COUNTRY' => (string)__('Country'),
            ],
            (string)__('Dates') => [
                'DATE' => (string)__('Date the document was created (dd.mm.yyyy)'),
                'DATE_TIME' => (string)__('Date and time the document was created'),
                'ORDER_DATE' => (string)__('Order date (checkout preview: now)'),
                'ORDER_TIME' => (string)__('Order time'),
                'ORDER_DATE_TIME' => (string)__('Order date and time'),
                'CART_ID' => (string)__('Cart number'),
            ],
            (string)__('Amounts') => [
                'ORDER_GRAND_TOTAL' => (string)__('Grand total'),
                'ORDER_SUB_TOTAL' => (string)__('Subtotal (incl. tax)'),
                'ORDER_PRODS_TOTAL' => (string)__('Products total (incl. tax)'),
                'ORDER_SUB_TOTAL_W_DISCOUNT' => (string)__('Subtotal after discount'),
                'DISCOUNT_TOTAL' => (string)__('Discount'),
                'SHIPPING_TOTAL' => (string)__('Shipping'),
                'CASH_ON_DELIVERY_FEE' => (string)__('Cash on delivery fee (if a module adds one)'),
                'ORDER_TAX_TOTAL' => (string)__('Tax'),
            ],
            (string)__('Order') => [
                'ORDER_DETAILS' => (string)__('Product table (product, SKU, qty, unit price, row total, subtotal, shipping, discount, grand total)'),
                'ORDER_INC_ID' => (string)__('Order number (may be empty in the checkout preview)'),
                'ORDER_LINE_COUNT' => (string)__('Number of product lines'),
                'ORDER_PROD_COUNT' => (string)__('Total quantity'),
                'PAYMENT_METHOD' => (string)__('Payment method'),
                'PAYMENT_DETAILS' => (string)__('Payment method and instalments, if any'),
                'COUPON_CODE' => (string)__('Coupon code'),
                'BASE_CURRENCY' => (string)__('Base currency'),
                'STORE_CURRENCY' => (string)__('Store currency'),
                'ORDER_CURRENCY' => (string)__('Order currency'),
            ],
        ];
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return array_merge(...array_map('array_keys', array_values($this->getGroups())));
    }
}
