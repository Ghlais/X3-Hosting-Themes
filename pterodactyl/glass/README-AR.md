# X3 Rackline Glass 1.1.1

إصلاح لشريط التنقّل والبحث في ثيم X3، متوافق مع Pterodactyl Panel **1.15.1** و**1.15.2**. يفصل تأثير الزجاج عن حاوية أزرار التنقّل، ويمنع أشرطة التمرير داخل الهيدر. يفتح زر البحث نافذة Pterodactyl الأصلية بالنقر أو Enter أو Space. يظهر شريط التمرير داخل نتائج البحث عند الحاجة.

## التركيب

ارفع `X3-Rackline-Glass-v1.1.1.zip` إلى `/root/tmp` ثم نفّذ:

```bash
x3_setup=$(mktemp -d /tmp/x3-glass-nav.XXXXXX) &&
unzip -q /root/tmp/X3-Rackline-Glass-v1.1.1.zip -d "$x3_setup" &&
cd "$x3_setup/X3-Rackline-Glass-v1.1.1" &&
php install.php --root=/var/www/pterodactyl &&
php install.php --root=/var/www/pterodactyl --apply &&
php install.php --root=/var/www/pterodactyl --check
```

حدّث صفحة اللوحة، واضغط أيقونة البحث واكتب ثلاثة أحرف على الأقل. يحتفظ المثبّت بنسخة احتياطية ويطبع أمر `Rollback:`. اختُبرت الترقية والتراجع مع تضمين X3 وتعديلات فهرسة صفحة الدخول الموجودة مسبقاً.
