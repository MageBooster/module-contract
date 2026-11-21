# MageBooster Contract: how-to

- Package: `magebooster/module-contract` (MageBooster Checkout / Hyvä: also `magebooster/module-contract-hyva`)
- Licence: paid (serial required)
- Support: info@magebooster.com

## What it does

Turkish payment providers (iyzico, PayTR, Moka, Rubikpara, Ödeal …) ask the shop to show the customer a filled-in distance
sales contract (*Mesafeli Satış Sözleşmesi*) and preliminary information form (*Ön Bilgilendirme Formu*) before payment.
This module fills the standard Magento **Terms and Conditions** texts with the order data and keeps a copy with every order.

- At checkout the texts are filled with the cart and what the customer has typed so far.
- When the order is placed, the filled texts are saved with the order: HTML, SHA-256 hash, customer IP and date.
- Admin: **Sales > Orders > (order) > Contracts** tab: view, download PDF / HTML.
- Customer: the order page in My Account lists the contracts with a download link.
- The order confirmation email gets the PDF attached (optionally also the HTML).
- Every payment method works: the record is made when the order is placed, not in the payment step.
- Without a valid serial the module keeps working for 30 days (grace period, see Licence check below). After that nothing is filled, saved or attached; checkout still works and records already saved stay viewable.
- Errors never block an order or an email; they are logged to `var/log/magebooster_contract.log`.

The module does not give legal advice. The sample texts are drafts; have your texts reviewed by your lawyer.

## Requirements

- PHP 8.0 or later, Magento 2.4.4 to 2.4.9 / Mage-OS
- `magebooster/module-license` (installed automatically)
- `dompdf/dompdf` 2.x or 3.x for PDFs (installed automatically; without it the HTML file is used)
- Luma checkout: `magebooster/module-contract`
- MageBooster Checkout (Luma or Hyvä): also `magebooster/module-contract-hyva`

Hyvä's own checkout and other one-step checkouts that do not use the Magento Terms and Conditions block are not filled
at checkout; the record and the email attachment still work for them.

## Install

1. Add the MageBooster Composer repository (username from your order email, your serial is the password):
   ```bash
   composer config repositories.magebooster composer https://repo.magebooster.com
   composer config http-basic.repo.magebooster.com <username from your order email> <your-serial>
   ```
2. Install:
   ```bash
   composer require magebooster/module-contract
   # MageBooster Checkout / Hyvä:
   composer require magebooster/module-contract-hyva
   bin/magento setup:upgrade
   bin/magento setup:di:compile
   bin/magento setup:static-content:deploy
   bin/magento cache:flush
   ```

## Set up

1. **Stores > Configuration > MageBooster > Licenses**: enter the serial.
2. **Stores > Configuration > MageBooster > Contract**:
   - *Enabled*: Yes.
   - *Attach PDF / HTML to the order confirmation email*: as you like.
   - *Tax office address attribute code*: only if a customer address attribute holds the tax office (for example `tax_office`).
   - *Seller details*: company name, address, phone, email, tax office, tax number, MERSIS number. Empty fields use
     **General > Store Information**.
3. **Stores > Configuration > Sales > Checkout > Checkout Options**: *Enable Terms and Conditions* = Yes.
4. **Stores > Terms and Conditions**: two sample texts are installed inactive
   (*Mesafeli Satış Sözleşmesi*, *Ön Bilgilendirme Formu*). Replace every `[bracketed]` part, have the text reviewed by
   your lawyer, then set *Status* = Enabled, *Applied* = Manually, and pick the store views.

## Tags

Write tags as `{{NAME}}`. The table under the Terms and Conditions edit form lists every tag with a description.

| Group | Tags |
| --- | --- |
| Seller | `COMPANY_NAME`, `COMPANY_SHORT_NAME`, `COMPANY_ADDRESS`, `COMPANY_CITY`, `COMPANY_STATE`, `COMPANY_COUNTRY`, `COMPANY_PHONE`, `COMPANY_FAX`, `COMPANY_WEBSITE`, `COMPANY_EMAIL`, `COMPANY_TAX_OFFICE`, `COMPANY_TAX_NUMBER`, `COMPANY_MERSIS` |
| Buyer (billing) | `CUSTOMER_NAME`, `CUSTOMER_ID_LINE`, `CUSTOMER_INVOICE_TYPE`, `CUSTOMER_COMPANY`, `CUSTOMER_TAX_OFFICE`, `CUSTOMER_TCK`, `CUSTOMER_ADDRESS`, `CUSTOMER_CITY`, `CUSTOMER_STATE`, `CUSTOMER_COUNTRY`, `CUSTOMER_ZIP`, `CUSTOMER_PHONE`, `CUSTOMER_EMAIL`, `CUSTOMER_IP` |
| Shipping | `SHIPPING_NAME`, `SHIPPING_ADDRESS`, `SHIPPING_CITY`, `SHIPPING_STATE`, `SHIPPING_COUNTRY` |
| Dates | `DATE`, `DATE_TIME`, `ORDER_DATE`, `ORDER_TIME`, `ORDER_DATE_TIME` |
| Order | `ORDER_INC_ID`, `CART_ID`, `ORDER_DETAILS`, `ORDER_LINE_COUNT`, `ORDER_PROD_COUNT`, `PAYMENT_METHOD`, `PAYMENT_DETAILS`, `COUPON_CODE` |
| Amounts | `ORDER_GRAND_TOTAL`, `ORDER_SUB_TOTAL`, `ORDER_PRODS_TOTAL`, `ORDER_SUB_TOTAL_W_DISCOUNT`, `DISCOUNT_TOTAL`, `SHIPPING_TOTAL`, `CASH_ON_DELIVERY_FEE`, `ORDER_TAX_TOTAL`, `BASE_CURRENCY`, `STORE_CURRENCY`, `ORDER_CURRENCY` |

Individual or corporate buyer: when the billing address has a company name the buyer is *Kurumsal* (corporate),
otherwise *Bireysel* (individual). `CUSTOMER_TCK` and `CUSTOMER_ID_LINE` use the address VAT number field.

## Orders placed before the module was enabled

Open the order, **Contracts** tab, **Create contracts now** (permission *Create missing order contracts*). The text is
made from the current order data and the active texts, and is marked "created later".

## Permissions (System > Permissions > User Roles)

- *View order contracts (MageBooster)*: Contracts tab and downloads.
- *Create missing order contracts (MageBooster)*: the "Create contracts now" button.
- *Contract settings (MageBooster)*: the settings page.

## Licence check (serial number)

- Enter the serial in **Stores > Configuration > MageBooster > Licenses**, field **Contract (Turkish distance sales contract)** (Hyvä edition: **Contract (Turkish distance sales contract) (Hyvä edition)**). The serial starts with `MB1`.
- The **License status** table on that page shows each module as **Active**, **Grace period** or **Switched off**. `bin/magento magebooster:license:status` shows the same.
- Without a valid serial (none entered, not valid, expired, another domain or another product) the module keeps working for **30 days**.
  - The 30 days start the first time the store sees the module without a valid serial.
  - A valid serial ends the grace period. If that serial later stops being valid (for example it expires), a new 30-day period starts.
- During and after the grace period, every admin page shows a notice that cannot be closed. It names the module, the reason, and the days left or the date it was switched off, with links to the Licenses page and to your serial numbers at https://store.magebooster.com/licences/account/.
- Shoppers never see a licence message, and the check runs inside your store: nothing is sent to MageBooster.
- Development hosts (`localhost`, `127.0.0.1`, `*.localhost`, `*.test`, `*.local`) always pass the domain check.
- **After the 30 days:** contracts are no longer filled, saved with orders or attached to emails. Checkout keeps working: the Terms and Conditions checkbox stays required and shows the text exactly as written in Stores > Terms and Conditions (tags are not filled). Records already saved stay viewable.

## Troubleshooting

- Checkout shows raw `{{TAGS}}`: check that the module is enabled, the serial is valid and caches are flushed.
- No attachment: check *Attach PDF* and `var/log/magebooster_contract.log`.
- No PDF: `composer show dompdf/dompdf`; without it the HTML is attached instead.
