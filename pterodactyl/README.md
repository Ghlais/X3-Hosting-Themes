# Pterodactyl · X3 Rackline Glass 1.1.1

ثيم واجهة العميل للوحة **Pterodactyl Panel 1.15.1 و1.15.2**. تضم الحزمة واجهة Glass كاملة مع تصحيح الهيدر والبحث وتمرير الكونسول وإرسال الأوامر بصلاحيات اللوحة الأصلية.

## التثبيت أو التحديث من ZIP

ارفع `X3-Rackline-Glass-v1.1.1.zip` إلى `~/tmp` بحساب SSH، ثم نفّذ:

```bash
x3_setup=$(mktemp -d /tmp/x3-pterodactyl.XXXXXX) &&
unzip -q "$HOME/tmp/X3-Rackline-Glass-v1.1.1.zip" -d "$x3_setup" &&
cd "$x3_setup/X3-Rackline-Glass-v1.1.1" &&
php install.php --root=/var/www/pterodactyl &&
php install.php --root=/var/www/pterodactyl --apply &&
php install.php --root=/var/www/pterodactyl --check
```

## التثبيت من المستودع

من جذر نسخة GitHub على السيرفر، نفّذ:

```bash
php pterodactyl/glass/install.php --root=/var/www/pterodactyl &&
php pterodactyl/glass/install.php --root=/var/www/pterodactyl --apply &&
php pterodactyl/glass/install.php --root=/var/www/pterodactyl --check
```

يعمل التحميل عن طريق تضمين Blade في قالب اللوحة، ويفحص المثبّت توافق القالب قبل الكتابة. بعد التركيب حدّث صفحة اللوحة، واختبر البحث والكونسول على سيرفر تجريبي. الملفات المنشورة تتضمن [معاينة الهيدر](glass/previews/header.png) و[نافذة البحث](glass/previews/search.png).

## التراجع

انسخ أمر `Rollback:` الذي طبعه المثبّت. مثال عند التشغيل من مجلد الحزمة:

```bash
x3_backup=/var/www/pterodactyl/storage/app/x3-rackline-backups/BACKUP_ID
php install.php --root=/var/www/pterodactyl --rollback="$x3_backup" &&
php install.php --root=/var/www/pterodactyl --rollback="$x3_backup" --apply
```

استبدل `BACKUP_ID` بالاسم المطبوع. عند التشغيل من جذر المستودع استخدم `pterodactyl/glass/install.php` مكان `install.php`.
