# تثبيت ثيمات X3 Hosting

فك ضغط ملفات الثيمين خارج مجلدات التطبيقات. اتصل بالسيرفر عبر SSH وافتح الطرفية داخل المجلد المستخرج الذي يحتوي `paymenter/` و`pterodactyl/`. شغّل البلوك الخاص بالتطبيق الذي تريد تركيب الثيم عليه.

## Paymenter · X3 Rackline Studio 3D v1.4.0

يتطلب **Paymenter 1.5.9** و**PHP 8.3 أو أحدث**. تفترض الأوامر أن Paymenter مركّب في `/var/www/paymenter`.

### أول تركيب

هذا البلوك يركّب قاعدة Rackline ‏1.1.0، ثم تحديث Studio ‏1.4.0. نفّذه من جذر المجلد المستخرج.

```bash
php paymenter/base/install.php --root=/var/www/paymenter &&
php paymenter/base/install.php --root=/var/www/paymenter --apply &&
php paymenter/studio/install.php --root=/var/www/paymenter &&
php paymenter/studio/install.php --root=/var/www/paymenter --apply
```

### تحديث ثيم X3 موجود

إذا عندك `x3-hosting` بقاعدة Rackline ‏1.1.0 أو إصدار Studio سابق، شغّل مثبّت `studio` مباشرة من جذر المجلد المستخرج.

```bash
php paymenter/studio/install.php --root=/var/www/paymenter &&
php paymenter/studio/install.php --root=/var/www/paymenter --apply
```

### التفعيل والفحص

اختَر **`x3-hosting`** من إعدادات المظهر في إدارة Paymenter واحفظ. بعدها نفّذ الفحص من جذر المجلد المستخرج وتأكد أن `active_theme` يساوي `x3-hosting` وأن `errors` فارغة.

```bash
php paymenter/studio/check.php --root=/var/www/paymenter
```

## Pterodactyl · X3 Rackline Glass v1.1.1

يتطلب **Pterodactyl Panel 1.15.1 أو 1.15.2**. تفترض الأوامر أن اللوحة مركّبة في `/var/www/pterodactyl`. استخدم البلوك نفسه للتركيب الأول والتحديث.

```bash
php pterodactyl/glass/install.php --root=/var/www/pterodactyl &&
php pterodactyl/glass/install.php --root=/var/www/pterodactyl --apply &&
php pterodactyl/glass/install.php --root=/var/www/pterodactyl --check
```

بعد نجاح الفحص، حدّث صفحة اللوحة وجرّب البحث والكونسول. يظهر أمر `Rollback:` ضمن ناتج المثبّت إذا احتجت التراجع.
