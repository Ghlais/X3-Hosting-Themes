# Discord Widget لـPaymenter

يتطلب X3 Rackline Studio 1.4.0 وPython 3 على خادم Paymenter. يظهر الويدجت في الصفحة الرئيسية مع زر دعوة، بالعربي أو الإنجليزي. [معاينة](../../docs/previews/paymenter-discord-demo.jpg)

1. افتح إعدادات سيرفر Discord وفعّل **Server Widget**. انسخ رقم السيرفر من صفحة الـWidget.
2. جهّز رابط دعوة HTTPS صالحاً.
3. من جذر المستودع، استبدل قيمة المثال ورابط الدعوة، ثم نفّذ:

```bash
x3_guild_id='123456789012345678'
x3_invite='https://discord.gg/EXAMPLE'
sudo python3 paymenter/discord-widget/install.py --root /var/www/paymenter --guild-id "$x3_guild_id" --invite "$x3_invite" --check &&
sudo python3 paymenter/discord-widget/install.py --root /var/www/paymenter --guild-id "$x3_guild_id" --invite "$x3_invite" --apply
```

افتح الصفحة الرئيسية وانزل تحت الأسئلة الشائعة. إذا كان الموقع يطبّق سياسة CSP، تحقق من أن `frame-src` يسمح بـ`https://discord.com`. مثبّت Studio قد يستبدل الصفحة الرئيسية عند إعادة تركيبها، لذلك راجع القسم بعدها.

للتراجع، استخدم المسار الذي طبعه المثبّت:

```bash
sudo python3 paymenter/discord-widget/install.py --root /var/www/paymenter --rollback /var/www/paymenter/storage/app/x3-discord-backups/BACKUP.blade.php
```
