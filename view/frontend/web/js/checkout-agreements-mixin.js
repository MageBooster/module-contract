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
/**
 * Luma checkout: when an agreement window is opened, fills the text again with what the customer has entered so far.
 * The original showContent runs first (themes and one-step checkouts may override it); only the modal content is
 * replaced when the filled text comes back.
 */
define([
    'jquery',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'mage/cookies'
], function ($, urlBuilder, quote, checkoutData) {
    'use strict';

    var FIELDS = ['firstname', 'lastname', 'company', 'street', 'city', 'region', 'postcode',
        'country_id', 'countryId', 'telephone', 'vat_id', 'vatId'];

    /**
     * Optional tax office custom address attribute: form data has custom_attributes[code],
     * the quote address a customAttributes array.
     */
    function taxOffice(source, code) {
        var ca = source.custom_attributes || source.customAttributes,
            v = '';

        if (!code || !ca) {
            return '';
        }
        if (Array.isArray(ca)) {
            ca.forEach(function (item) {
                if (item && item.attribute_code === code) {
                    v = item.value;
                }
            });
        } else if (ca[code] !== undefined) {
            v = ca[code] && ca[code].value !== undefined ? ca[code].value : ca[code];
        }

        return typeof v === 'string' ? v.trim() : '';
    }

    /**
     * Merges the Knockout address model and the raw form data into one flat object (the form value wins).
     */
    function collectAddress(addressModel, formData, taxOfficeCode) {
        var result = {};

        [addressModel || {}, formData || {}].forEach(function (source) {
            var office = taxOffice(source, taxOfficeCode);

            if (office) {
                result['tax_office'] = office;
            }
            FIELDS.forEach(function (field) {
                var value = source[field];

                if (field === 'street' && value && !Array.isArray(value) && typeof value === 'object') {
                    value = Object.keys(value).sort().map(function (key) {
                        return value[key];
                    });
                }

                if (value !== undefined && value !== null && value !== '' &&
                    !(Array.isArray(value) && !value.join('').trim())
                ) {
                    result[field] = value;
                }
            });
        });

        return result;
    }

    /**
     * The form field is the most recent; stored values may be stale or browser autofill leftovers.
     */
    function email() {
        var candidates = [
                $('#customer-email').val(),
                checkoutData.getValidatedEmailValue(),
                checkoutData.getInputFieldEmailValue(),
                quote.guestEmail
            ],
            valid = candidates.filter(function (value) {
                return typeof value === 'string' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());
            });

        return valid.length ? valid[0].trim() : '';
    }

    return function (Component) {
        var config = window.checkoutConfig && window.checkoutConfig.checkoutAgreements;

        if (!config || !config.mbContractPreview) {
            return Component;
        }

        return Component.extend({
            showContent: function (element) {
                var self = this,
                    result = this._super.apply(this, arguments),
                    code = config.mbContractTaxOffice || '',
                    shipping = collectAddress(quote.shippingAddress(), checkoutData.getShippingAddressFromData(), code),
                    billing = collectAddress(quote.billingAddress(), checkoutData.getBillingAddressFromData(), code);

                $.ajax({
                    url: urlBuilder.build('mbcontract/checkout/preview'),
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        'form_key': $.mage.cookies.get('form_key'),
                        'agreement_id': element.agreementId,
                        shipping: JSON.stringify(shipping),
                        // Empty billing form (usually "same as shipping"): the server uses the shipping address
                        billing: JSON.stringify($.isEmptyObject(billing) ? {} : billing),
                        email: email()
                    }
                }).done(function (response) {
                    var content = response && response.success && response.agreements ?
                        response.agreements[element.agreementId] : null;

                    if (content) {
                        element.content = content;
                        self.modalContent(content);
                    }
                });

                return result;
            }
        });
    };
});
