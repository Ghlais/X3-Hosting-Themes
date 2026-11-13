# X3 Game Tools 1.1.1

إضافة لـPterodactyl Panel 1.15.1 مع Blueprint beta-2026-08 وPHP 8.3. تظهر في صفحة السيرفر بتبويب **Game Tools**. يمكن تبديل الواجهة بين العربي والإنجليزي.

## الميزات

- **Minecraft Paper** — الصعوبة، نمط اللعب، الحد الأقصى للاعبين، مسافة الرؤية والمحاكاة، والقتال بين اللاعبين عبر `server.properties`.
- **SA-MP** — اسم السيرفر، الحد الأقصى للاعبين والـGamemode الأول عبر `server.cfg`. صيغة الـGamemode مثل `grandlarc 1` وملف `grandlarc.amx` لازم موجود داخل `gamemodes`.
- **MTA** — اسم السيرفر والحد الأقصى للاعبين عبر `mods/deathmatch/mtaserver.conf`.
- **إدارة لاعبي Paper** — عرض المتصلين والقائمة البيضاء والمحظورين، وتفعيل أو تعطيل القائمة البيضاء، وإضافة أو إزالة لاعب، والحظر وفكّه والطرد. تحتاج `X3Players.jar` على كل سيرفر Paper يريد الميزة.
- **فحص تطبيقات Node.js وPython** — يعرض ملف التشغيل المحدد في القالب ووجود ملف الاعتماديات وحالة تحديث Git عند التشغيل. يتطلب عرض إعدادات التشغيل صلاحية Startup Read، وفحص وجود الملفات صلاحية File Read. الفحص يقرأ أسماء الملفات المحددة وحالتها فقط.

## معاينات

[إدارة اللاعبين](../../../docs/previews/players-demo.jpg) · [Node.js](../../../docs/previews/node-demo.jpg) · [Python](../../../docs/previews/python-demo.jpg) · [مراجعة الإعدادات](../../../docs/previews/game-settings-demo.jpg)

## تركيب إضافة البانل

من جذر نسخة GitHub على السيرفر، تحقّق من الحزمة وجهّزها للمثبّت:

```bash
(
  cd pterodactyl/extensions/game-tools &&
  sha256sum -c SHA256SUMS.txt &&
  sudo install -d -m 0700 /root/tmp &&
  sudo install -m 0644 xthreegames.blueprint /root/tmp/xthreegames.blueprint
)
```


فك ضغط الحزمة بمجلد مستقل. ارفع `xthreegames.blueprint` إلى `/root/tmp/xthreegames.blueprint` على خادم البانل. خذ نسخة احتياطية من البانل وقاعدة بياناته قبل التثبيت. Blueprint يعيد بناء الواجهة ويشغّل migration لإضافة جدول صلاحيات المشرفين. نفّذ البناء بوقت تتوفر فيه ذاكرة حرة كافية، وفي فترة صيانة إذا كان الخادم مزدحم.

```bash
cd /var/www/pterodactyl &&
php artisan p:info --no-ansi | grep 'Panel Version' &&
blueprint -version &&
free -h &&
install -m 0644 /root/tmp/xthreegames.blueprint ./xthreegames.blueprint &&
blueprint -install xthreegames &&
php artisan route:list --path=api/client/extensions/xthreegames -v &&
php artisan migrate:status | grep create_xthreegames_moderators
```

راجع ناتج Webpack حتى لو طبع Blueprint رسالة نجاح. إصدار 1.1.1 يضيف فلترة اللاعبين، وقت التحديث، وتحسينات العرض والتحقق. بعد نجاح البناء، افتح أحد سيرفرات Paper أو SA-MP أو MTA واضغط **Game Tools**.

## تحديث نسخة موجودة

احتفظ بالحزمة السابقة قبل الاستبدال. استعمل أوامر التركيب أعلاه بالمعرّف نفسه حتى يتعرف Blueprint على الإضافة ويحدّثها. عند الحاجة للتراجع عن تحديث الواجهة، ارجع للحزمة السابقة وشغّل أمر التركيب نفسه. افحص نتيجة Webpack ثم حدّث الصفحة.

## تفعيل مركز اللاعبين على Paper

1. افتح سيرفر Paper المطلوب من البانل. ارفع `X3Players.jar` إلى مجلد `plugins` من تبويب **Files**.
2. أعد تشغيل سيرفر اللعبة بالوقت المناسب. افتح **Game Tools → Paper players** وتحقق من ظهور القائمة.
3. لتفويض مشرف، أضفه أولاً كمستخدم فرعي من **Users**. بعدها امنحه صلاحية إدارة اللاعبين من **Moderator access**. تگدر تلغي الصلاحية من نفس المكان. الإضافة تتحقق من تفويضه على السيرفر نفسه لكل طلب. بإمكانك ترك صلاحيات الكونسل والملفات خارج حساب المشرف إذا ما يحتاجها.
4. بعد تنفيذ إجراء، انتظر رسالة التأكيد من Paper. إذا انتهت المهلة، حدّث القائمة أولاً. البلوقن يكتب حالة اللعبة داخل مجلد بياناته ويرسل البانل إجراءات محددة عبر Wings. إعداد RCON وفتح منفذ جديد غير مطلوبين.

### الرفع التلقائي على خادم البانل

يحتوي مجلد `automation/` على خدمة ومؤقّت لـsystemd. يدعم قوالب Paper داخل مجموعة Minecraft، وبمجلد الإضافات القياسي. يرفع البلوقن بعد توفر ملف اللعبة، ويفحص البصمة، ويترك أي نسخة موجودة باسم مختلف أو ببصمة مختلفة. المؤقّت لا يعيد تشغيل اللعبة. إذا أنشأت سيرفراً وبدأ تشغيله قبل دورة المزامنة، يظهر البلوقن عند إعادة التشغيل التالية.

لتثبيت المزامنة على خادم بنية مجلداته مثل X3، نفّذ من المجلد المفكوك وبصلاحية root:

```bash
install -d -m 0755 /opt/x3-paper-players &&
install -m 0644 automation/sync-paper.php X3Players.jar /opt/x3-paper-players/ &&
runuser -u www-data -- /usr/bin/php /opt/x3-paper-players/sync-paper.php --check &&
install -m 0644 automation/x3-paper-players.service automation/x3-paper-players.timer /etc/systemd/system/ &&
systemctl daemon-reload &&
systemctl start x3-paper-players.service &&
systemctl enable --now x3-paper-players.timer
```

لمراجعة نتيجة الرفع، نفّذ `journalctl -u x3-paper-players.service -n 20 --no-pager`. لإيقاف الرفع التلقائي، نفّذ `systemctl disable --now x3-paper-players.timer` قبل نقل البلوقن خارج مجلد `plugins`.

## استخدام محرّر الإعدادات

للحفظ، يحتاج المستخدم صلاحيات قراءة محتوى الملفات وإنشائها وتعديلها. الإضافة تعرض الحقول المحددة لكل لعبة، وتعلّم الحقول التي يعيد القالب ضبطها كحقول للعرض. عدّل القيم واضغط **راجع التغييرات** ثم **أكد واحفظ**. إذا تغيّر الملف منذ فتحه، حدّث الصفحة وراجع القيم من جديد. الحفظ يحتفظ بنسخة الملف السابق بالاسم نفسه مع اللاحقة `.x3-previous`. النسخة تتحدّث مع كل عملية حفظ جديدة. الإعدادات تحتاج إعادة تشغيل سيرفر اللعبة حتى تتطبق.

ملفات MTA لازم تكون XML سليمة بمسارها القياسي. إضافة SA-MP تتعامل مع `server.cfg` بصيغته المعتادة. ملفات خصائص Java ذات المفاتيح المهربة أو الأسطر الممتدة تحتاج تعديل من مدير الملفات. إعدادات Paper المكتوبة كوسائط في أمر التشغيل قد تتقدّم على قيمة الملف.

## التراجع

- إذا عدّلت إعداداً وتريد ترجع، انسخ محتوى ملف `.x3-previous` إلى ملف الإعداد الأصلي من **Files**، ثم أعد تشغيل سيرفر اللعبة. احفظ النسخة الحالية أولاً إذا بيها تغييرات إضافية.
- لإيقاف الربط في Paper، أوقف اللعبة في موعد الصيانة، وانقل `X3Players.jar` إلى خارج مجلد `plugins`، ثم شغّل السيرفر.
- لإزالة واجهة البانل، استخدم `blueprint -remove xthreegames` من `/var/www/pterodactyl` في وقت يتوفر فيه RAM للبناء. إذا أردت تصفير التفويضات قبل إزالة الإضافة، ألغها من تبويب **Moderator access**. حذف الإضافة لا يتطلب حذف إعدادات اللعبة أو نسخها الاحتياطية.

## الفحص المنفّذ

- فحص PHP 8.3 واختبارات قراءة وكتابة عيّنات الإعدادات الثلاثة.
- فحص TypeScript وبناء Webpack على نسخة محلية من البانل مع Blueprint. البناء رجّع تحذيراً من ملف أنواع تابع للبانل.
- جرد وقراءة فعلية عبر Wings لملفات Paper وSA-MP وMTA في X3 Hosting. حالة MTA بملف مفقود رجّعت رسالة مفهومة. فحص رفض الضيف والحساب غير المرتبط. اختبار النسخ الاحتياطي والحفظ وتعارض النسخ باستخدام Wings وهمي.
- اختبار البلوقن على Paper 26.3 build 249 ببيئة محلية. فحص القائمة البيضاء والحظر وفكّه، والقائمة الحية وهي فارغة. اختبار الطرد غطّى حالة اللاعب غير المتصل. اختبار صلاحيات التفويض من واجهة الإنتاج يبقى بعد التركيب.

## المراجع

- [Paper server.properties](https://docs.papermc.io/paper/reference/server-properties/)
- [Paper player data files](https://docs.papermc.io/paper/reference/vanilla-data-files/)
- [SA-MP server.cfg](https://open.mp/docs/server/server.cfg)
- [MTA mtaserver.conf](https://wiki.multitheftauto.com/wiki/Mtaserver.conf)
