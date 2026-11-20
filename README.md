<!-- mb-top -->
# MageBooster Contract

Fills in the Turkish distance sales contract and preliminary information form automatically at checkout — required by Turkish payment providers (iyzico, PayTR, Moka, Rubikpara, Ödeal …). The filled contract is saved with every order (HTML and PDF) and attached to the order email. Needs a MageBooster serial number.

![Magento 2.4.4–2.4.9](https://img.shields.io/badge/Magento-2.4.4%E2%80%932.4.9-orange) ![PHP 8.0+](https://img.shields.io/badge/PHP-8.0%2B-777bb4) ![Luma | Hyvä](https://img.shields.io/badge/themes-Luma%20%7C%20Hyv%C3%A4-0a7) ![Licence: Commercial](https://img.shields.io/badge/licence-Commercial-red)

```bash
composer require magebooster/module-contract
bin/magento setup:upgrade
```

Paid packages are served from the MageBooster Composer repository (`https://repo.magebooster.com`); your serial number is the password. See the guide for the full steps.

- Guide: [English](docs/HOWTO.en.md) · [Türkçe](docs/HOWTO.tr.md)
- Changes: [CHANGELOG.md](CHANGELOG.md)
- Buy a licence: https://magebooster.com
- Support: info@magebooster.com

---

<!-- /mb-top -->
# MageBooster_Contract – filled distance sales contracts for Magento 2

Turkish payment providers ask online shops to show the customer a filled-in *Mesafeli Satış Sözleşmesi* (distance sales
contract) and *Ön Bilgilendirme Formu* (preliminary information form) before payment, and to keep a copy of what the
customer accepted. This module does that with the standard Magento **Terms and Conditions**, for Magento 2.4.4–2.4.9 /
Mage-OS (PHP 8.0+). Commercial licence (see LICENSE.txt): using it needs a **MageBooster serial number**. Türkçe açıklama aşağıda.

## What it does

- You write the texts in **Stores > Terms and Conditions** with tags such as `{{CUSTOMER_NAME}}`, `{{ORDER_DETAILS}}`,
  `{{COMPANY_NAME}}`. The full tag list is shown under the edit form.
- At checkout the texts open filled with the cart and what the customer has typed so far (Luma checkout; MageBooster
  Checkout on Luma and Hyvä with `magebooster/module-contract-hyva`).
- When the order is placed, the filled texts are saved with the order (HTML, SHA-256 hash, customer IP). Later changes to
  the texts do not change the saved records.
- The saved contracts are listed in the admin order view (**Contracts** tab) and on the customer's order page, and can be
  downloaded as PDF (dompdf) or HTML.
- The PDF (optionally the HTML) is attached to the order confirmation email.
- Works with every payment method: nothing depends on the payment step.
- Seller details come from **Stores > Configuration > MageBooster > Contract**, with Store Information as the fallback.
- Two sample Turkish texts are installed **inactive**. They are drafts: have them reviewed by your lawyer before use.

This module does not give legal advice and does not guarantee that your texts meet any law or payment provider rule.

## Install

```bash
composer require magebooster/module-contract
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

Then enter the serial in **Stores > Configuration > MageBooster > Licenses**, turn the module on and follow the guide.

## Türkçe

Türkiye'deki ödeme kuruluşları (iyzico, PayTR, Moka, Rubikpara, Ödeal ...) ödeme öncesinde müşteriye doldurulmuş
Mesafeli Satış Sözleşmesi ve Ön Bilgilendirme Formu gösterilmesini ve onaylanan metnin saklanmasını ister. Bu modül
Magento'nun Şartlar ve Koşullar metinlerini ödeme sayfasında sipariş bilgileriyle doldurur, siparişle birlikte HTML ve
PDF olarak saklar, yönetim panelindeki sipariş ekranında gösterir ve sipariş e-postasına ekler. Her ödeme yöntemiyle
çalışır. Örnek Türkçe metinler pasif olarak yüklenir ve taslaktır; kullanmadan önce avukatınıza kontrol ettirin. Modül
hukuki danışmanlık yerine geçmez. Kurulum ve ayarlar için [Türkçe kılavuz](docs/HOWTO.tr.md).
