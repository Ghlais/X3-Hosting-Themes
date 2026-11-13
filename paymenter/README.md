# Paymenter · X3 Rackline Studio 3D 1.4.0

التركيب يستهدف **Paymenter 1.5.9 وPHP 8.3+**. حزمة التثبيت الكاملة تضم قاعدة Rackline `1.1.0` وتحديث Studio `1.4.0` في مجلدين. ثيم المظهر داخل Paymenter اسمه `x3-hosting`.

## فك الحزمة على السيرفر

ارفع `X3-Paymenter-Complete-v1.4.0.zip` إلى `~/tmp` بحساب SSH، ثم نفّذ:

```bash
x3_setup=$(mktemp -d /tmp/x3-paymenter.XXXXXX) &&
unzip -q "$HOME/tmp/X3-Paymenter-Complete-v1.4.0.zip" -d "$x3_setup" &&
cd "$x3_setup/X3-Paymenter-Complete-v1.4.0"
```

إذا استنسخت مستودع GitHub على السيرفر، افتح مجلد `paymenter` داخل المستودع وابدأ بالأوامر التالية من هناك. كلا المسارين يوفّر مجلدي `base` و`studio`.

## التركيب لأول مرة

نفّذ هذا البلوك عندما يكون الثيم `x3-hosting` غير مركّب. يفحص كل مثبّت حزمتَه ثم يطبع مسار النسخة الاحتياطية وأمر التراجع بعد التركيب.

```bash
php base/install.php --root=/var/www/paymenter &&
php base/install.php --root=/var/www/paymenter --apply &&
php studio/install.php --root=/var/www/paymenter &&
php studio/install.php --root=/var/www/paymenter --apply
```

من إدارة Paymenter اختَر **x3-hosting** ضمن إعدادات المظهر واحفظ. لتفعيله من SSH، شغّل البلوك التالي بعد نجاح تركيب الملفات:

```bash
(
  cd /var/www/paymenter &&
  php artisan tinker --execute='\App\Models\Setting::updateOrCreate(["key"=>"theme","settingable_type"=>null,"settingable_id"=>null],["value"=>"x3-hosting","type"=>"string"]); \App\Providers\SettingsProvider::flushCache();' &&
  php artisan view:clear
)
```

## التحديث إذا كانت قاعدة X3 مركّبة

إذا عندك Rackline `1.1.0` أو Studio سابق، استخدم `studio` مباشرة. إعادة تشغيل مثبّت `base` تستبدل مجلدي الثيم والأصول بالكامل، لذلك احتفظ به للتركيب الأول أو الاستعادة المقصودة.

```bash
php studio/install.php --root=/var/www/paymenter &&
php studio/install.php --root=/var/www/paymenter --apply
```

## الفحص

```bash
php studio/check.php --root=/var/www/paymenter
```

تأكد من `active_theme: x3-hosting` و`errors: []`، ثم افتح الواجهة والمتجر وسلة الطلبات بحساب تجريبي. راجع بوابة الدفع في وضعها التجريبي على موقعك. إعدادات الروابط والمنتجات مذكورة في [شرح إعدادات الثيم](base/docs/SETTINGS.md)، وتفاصيل الفحص المحلي في [نتائج Studio](studio/VALIDATION.json).

## التراجع

نفّذ أمر `Rollback:` الذي طُبع عند تركيب **Studio**، أو استبدل `BACKUP_ID` في المثال بالاسم الذي ظهر لك:

```bash
x3_backup=/var/www/paymenter/storage/app/x3-studio-backups/BACKUP_ID
php studio/install.php --root=/var/www/paymenter --rollback="$x3_backup" &&
php studio/install.php --root=/var/www/paymenter --rollback="$x3_backup" --apply
```

إذا كان هذا أول تركيب وتريد إلغاءه بالكامل، اختر الثيم السابق من إدارة Paymenter، ثم تراجع عن Studio أولاً، وبعده عن القاعدة بأمر `Rollback:` الخاص بمثبّت `base`. مسار نسخة القاعدة تحت `/var/www/paymenter/storage/app/x3-theme-backups/`.

## إضافات ومعاينات

- [تركيب ويدجت Discord](discord-widget/README.md)
- [معاينة الشراء والفواتير والتذاكر](../docs/PREVIEWS.md#paymenter)
