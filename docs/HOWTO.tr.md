# MageBooster Contract: kullanım kılavuzu

- Paket: `magebooster/module-contract` (MageBooster Checkout / Hyvä için ayrıca `magebooster/module-contract-hyva`)
- Lisans: ücretli (seri numarası gerekir)
- Destek: info@magebooster.com

## Ne yapar

Türkiye'deki ödeme kuruluşları (iyzico, PayTR, Moka, Rubikpara, Ödeal ...) ödeme öncesinde müşteriye doldurulmuş Mesafeli
Satış Sözleşmesi ve Ön Bilgilendirme Formu gösterilmesini ister. Bu modül Magento'nun **Şartlar ve Koşullar** metinlerini
sipariş bilgileriyle doldurur ve her siparişle birlikte bir kopyasını saklar.

- Ödeme sayfasında metinler sepet ve müşterinin o ana kadar girdiği bilgilerle dolu açılır.
- Sipariş verilince doldurulan metinler siparişle birlikte saklanır: HTML, SHA-256 özeti, müşteri IP'si ve tarih.
- Yönetim paneli: **Satış > Siparişler > (sipariş) > Sözleşmeler** sekmesi: görüntüleme, PDF / HTML indirme.
- Müşteri: Hesabım'daki sipariş sayfasında sözleşmeler indirme bağlantısıyla listelenir.
- Sipariş onay e-postasına PDF eklenir (isterseniz HTML de).
- Her ödeme yöntemiyle çalışır: kayıt ödeme adımında değil, sipariş verildiğinde yapılır.
- Geçerli seri numarası yoksa hiçbir şey doldurulmaz, saklanmaz, eklenmez; daha önce saklanan kayıtlar görüntülenebilir.
- Hatalar siparişi ya da e-postayı hiçbir zaman durdurmaz; `var/log/magebooster_contract.log` dosyasına yazılır.

Modül hukuki danışmanlık vermez. Örnek metinler taslaktır; metinlerinizi avukatınıza kontrol ettirin.

## Gereksinimler

- PHP 8.0 veya üstü, Magento 2.4.4 - 2.4.9 / Mage-OS
- `magebooster/module-license` (otomatik kurulur)
- PDF için `dompdf/dompdf` 2.x veya 3.x (otomatik kurulur; yoksa HTML dosyası kullanılır)
- Luma ödeme sayfası: `magebooster/module-contract`
- MageBooster Checkout (Luma ya da Hyvä): ayrıca `magebooster/module-contract-hyva`

Hyvä'nın kendi ödeme sayfası ve Magento Şartlar ve Koşullar bloğunu kullanmayan diğer tek adım ödeme sayfalarında metin
ödeme sırasında doldurulmaz; sipariş kaydı ve e-posta eki yine çalışır.

## Kurulum

1. MageBooster Composer deposunu ekleyin (kullanıcı adı sipariş e-postanızda, şifre seri numaranızdır):
   ```bash
   composer config repositories.magebooster composer https://repo.magebooster.com
   composer config http-basic.repo.magebooster.com <siparis-e-postasindaki-kullanici-adi> <seri-numaraniz>
   ```
2. Kurun:
   ```bash
   composer require magebooster/module-contract
   # MageBooster Checkout / Hyvä için:
   composer require magebooster/module-contract-hyva
   bin/magento setup:upgrade
   bin/magento setup:di:compile
   bin/magento setup:static-content:deploy
   bin/magento cache:flush
   ```

## Ayarlar

1. **Mağazalar > Yapılandırma > MageBooster > Lisanslar**: seri numarasını girin.
2. **Mağazalar > Yapılandırma > MageBooster > Sözleşme**:
   - *Etkin*: Evet.
   - *Sipariş onay e-postasına PDF / HTML ekle*: tercihinize göre.
   - *Vergi dairesi adres özelliği kodu*: yalnızca vergi dairesini tutan bir müşteri adresi özelliği varsa (örneğin `tax_office`).
   - *Satıcı bilgileri*: unvan, adres, telefon, e-posta, vergi dairesi, vergi numarası, MERSİS numarası. Boş alanlarda
     **Genel > Mağaza Bilgileri** kullanılır.
3. **Mağazalar > Yapılandırma > Satış > Ödeme > Ödeme Seçenekleri**: *Şartlar ve Koşulları Etkinleştir* = Evet.
4. **Mağazalar > Şartlar ve Koşullar**: iki örnek metin pasif olarak yüklenir (*Mesafeli Satış Sözleşmesi*,
   *Ön Bilgilendirme Formu*). Köşeli parantez içindeki `[...]` yerleri doldurun, metni avukatınıza kontrol ettirin, sonra
   *Durum* = Etkin, *Uygulama* = Elle yapın ve mağaza görünümlerini seçin.

## Etiketler

Etiketleri `{{AD}}` biçiminde yazın. Şartlar ve Koşullar düzenleme formunun altındaki tabloda her etiket açıklamasıyla listelenir.
Etiketlerin tam listesi için İngilizce kılavuzdaki tabloya bakın; gruplar: satıcı (`COMPANY_*`), alıcı (`CUSTOMER_*`),
teslimat (`SHIPPING_*`), tarihler (`DATE`, `ORDER_DATE` ...), sipariş (`ORDER_INC_ID`, `ORDER_DETAILS`, `PAYMENT_METHOD` ...)
ve tutarlar (`ORDER_GRAND_TOTAL`, `SHIPPING_TOTAL` ...).

Bireysel ya da kurumsal alıcı: fatura adresinde firma adı varsa alıcı *Kurumsal*, yoksa *Bireysel* sayılır. `CUSTOMER_TCK`
ve `CUSTOMER_ID_LINE` adresteki vergi numarası alanını kullanır.

## Modül açılmadan önce verilen siparişler

Siparişi açın, **Sözleşmeler** sekmesi, **Sözleşmeleri şimdi oluştur** (*Eksik sipariş sözleşmelerini oluştur* yetkisi).
Metin siparişin şu anki bilgileri ve etkin metinlerle oluşturulur ve "sonradan oluşturuldu" olarak işaretlenir.

## Yetkiler (Sistem > Yetkiler > Kullanıcı Rolleri)

- *Sipariş sözleşmelerini görüntüle (MageBooster)*: Sözleşmeler sekmesi ve indirme.
- *Eksik sipariş sözleşmelerini oluştur (MageBooster)*: "Sözleşmeleri şimdi oluştur" düğmesi.
- *Sözleşme ayarları (MageBooster)*: ayarlar sayfası.

## Sorun giderme

- Ödeme sayfasında `{{ETİKET}}` görünüyor: modülün açık, seri numarasının geçerli olduğunu ve önbelleğin temizlendiğini kontrol edin.
- E-posta eki yok: *PDF ekle* ayarına ve `var/log/magebooster_contract.log` dosyasına bakın.
- PDF yok: `composer show dompdf/dompdf`; yoksa onun yerine HTML eklenir.
