# X3 Paper Plugins 1.0.2

إضافة Blueprint للوحة Pterodactyl باسم X3 Hosting. يفتح العميل تبويب **Paper Plugins** داخل سيرفر Paper، يبحث في Modrinth، يختار نسخة متوافقة، ويثبّت ملف JAR مباشرة في مجلد `plugins`.

إصدار 1.0.2 يوضّح خطوات البحث واختيار الإصدار والتثبيت، ويحافظ على النتائج إذا فشل طلب البحث.

## المتطلبات

- Pterodactyl Panel 1.15.1 أو 1.15.2 مع Blueprint `beta-2026-08` وبيئة بناء Blueprint عاملة. واجهة الملفات المستخدمة مطابقة لمصدر Wings 1.13.4.
- PHP 8.3 أو أحدث مع `zip` واتصال HTTPS من اللوحة إلى `api.modrinth.com` و`cdn.modrinth.com`.
- سيرفر Paper شُغّل مرة على الأقل حتى ينشئ مجلد `plugins`. أسماء Egg المدعومة هي `Paper` و`PaperMC` و`Minecraft Paper` و`Paper Minecraft`، مع السماح باختلاف الحالة والمسافات وعلامات الفصل.
- مالك السيرفر أو مستخدم مفوّض بصلاحيات `file.read` و`file.create` و`file.update`. صلاحية القراءة وحدها تكفي للتصفح.

## التركيب

من جذر نسخة GitHub على السيرفر، تحقّق من الحزمة وجهّزها للمثبّت:

```bash
(
  cd pterodactyl/extensions/paper-plugins &&
  sha256sum -c SHA256SUMS.txt &&
  sudo install -d -m 0700 /root/tmp &&
  sudo install -m 0644 xthreepaper.blueprint /root/tmp/xthreepaper.blueprint
)
```


خذ نسخة احتياطية من اللوحة قبل تركيب إضافة Blueprint، ثم ارفع `xthreepaper.blueprint` إلى `/root/tmp/` في السيرفر. نفّذ الأوامر التالية عبر SSH.

```bash
cd /var/www/pterodactyl &&
php artisan p:info --no-ansi &&
blueprint -version &&
php -m | grep -ix zip
```

إذا توقفت نسخة 1.0.0 برسالة رفض المعرّف، ارفع الملف الجديد واتبع الأمر أدناه مباشرة. في هذه الحالة توقف Blueprint أثناء التحقق قبل تركيب الإضافة.

بعد التأكد من المتطلبات، ركّب الإضافة. يعيد Blueprint بناء واجهة اللوحة أثناء العملية.

```bash
cd /var/www/pterodactyl &&
install -m 0644 /root/tmp/xthreepaper.blueprint ./xthreepaper.blueprint &&
blueprint -install xthreepaper &&
php artisan route:list --path=api/client/extensions/xthreepaper
```

يُفترض أن تظهر أربعة مسارات باسم `status` و`search` و`versions` و`install`. إذا اختفى التبويب بعد التركيب، راجع نتيجة بناء Blueprint وحدّث الصفحة. تقدر تخصّص ظهور التبويب لـEggs محددة من إعدادات مسارات الإضافة في إدارة Blueprint إذا كانت متاحة في لوحتك. الخادم نفسه يتحقّق من Egg وصلاحيات السيرفر عند كل طلب.

## تحديث نسخة موجودة

احتفظ بالحزمة السابقة قبل الاستبدال. استعمل أوامر التركيب أعلاه بالمعرّف نفسه حتى يتعرف Blueprint على الإضافة ويحدّثها. عند الحاجة للتراجع عن تحديث الواجهة، ارجع للحزمة السابقة وشغّل أمر التركيب نفسه. افحص نتيجة Webpack ثم حدّث الصفحة.

## اختبار من حساب العميل

1. افتح سيرفر Paper تجريبي من حساب مالكه، ثم افتح **Paper Plugins**.
2. عند ظهور `latest` في إعدادات تشغيل Paper، اقرأ رقم Minecraft من أمر `version` في الكونسل وأدخله بالضبط. عند وجود رقم إصدار صريح في إعدادات التشغيل، تعرضه الإضافة تلقائياً.
3. ابحث عن `LuckPerms`، اختر نسخة، واضغط **ثبّت البلوقن**. إذا كان البلوقن مثبتاً مسبقاً في السيرفر، استخدم سيرفر اختبار نظيف.
4. راجع الملف في **Files → plugins**. بعدها أعد تشغيل سيرفر الاختبار من **Console** وتأكد من تحميل البلوقن.
5. جرّب بحساب فرعي يملك صلاحية قراءة الملفات فقط. يظل البحث متاحاً ويُعطّل التثبيت. وجرّب على Egg مختلفة لتتأكد من رفض العملية.

## حدود الكتالوج

- البحث يعرض مشاريع Modrinth المنشورة التي تصرّح بدعم Paper وإصدار Minecraft المختار. يشمل الملفات المتاحة علناً على Modrinth بحد أقصى 32 MiB لكل JAR.
- التثبيت يتحقق من الحجم وبصمة SHA‑512 ووصف البلوقن داخل ملف JAR. تظهر الاعتماديات التي يذكرها الناشر في Modrinth، ويتولى العميل تثبيتها عند الحاجة.
- تُحفظ الملفات باسم `x3-modrinth-<project-id>.jar`. إذا وجدت الإضافة ملفاً مُداراً بنفس الاسم، تتوقف العملية. للتحديث، خذ نسخة من الملف القديم وانقله خارج `plugins` أثناء توقف السيرفر، ثم ثبّت النسخة الجديدة. افحص مجلد `plugins` أولاً إذا كانت عندك نسخة مثبتة يدوياً باسم آخر.
- التثبيت يكتب الملف فقط. من الكونسل يختار العميل موعد إعادة التشغيل لتحميل الإضافة.

## التراجع

```bash
cd /var/www/pterodactyl &&
blueprint -remove xthreepaper
```

هذا يزيل الإضافة من اللوحة. ملفات JAR التي ثبّتها أصحاب السيرفرات تبقى داخل سيرفراتهم ويمكنهم إدارتها من **Files**.

[توثيق Blueprint](https://blueprint.zip/guides/admin/extensions) · [توثيق Paper](https://docs.papermc.io/paper/adding-plugins/) · [واجهة Modrinth](https://docs.modrinth.com/api/)
