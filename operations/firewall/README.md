# X3 Firewall

قواعد nftables لجدار نود ألعاب يستخدم Docker وPterodactyl. تفحص الترافيك الواصل للنود، وتعد الاتصالات الجديدة على المنافذ المنشورة. حماية خط الاتصال من الترافيك الحجمي تعتمد على المزود. [معاينة عدادات تجريبية](../../docs/previews/firewall-demo.jpg)

## تركيب أولي وفحص

نفّذ على نود اختبار أولاً. تحتاج Ubuntu وsystemd وnftables وflock. اعرف الواجهة الخارجية والجسر عبر `ip -brief link`، ثم راجع `x3-guard.nft`. الإعداد النموذجي يستخدم `eth0` و`pterodactyl0`. راجع منافذ الإدارة ضمن `private_tcp_ports` قبل التفعيل. إذا كانت الأداة مثبتة، احفظ ملفاتها الحالية قبل استبدالها.

من جذر المستودع:

```bash
cd operations/firewall &&
sudo install -d -m 0755 /etc/x3-firewall &&
sudo install -m 0644 x3-guard.nft /etc/x3-firewall/x3-guard.nft &&
sudo install -m 0755 x3-firewall /usr/local/sbin/x3-firewall &&
sudo install -m 0644 x3-firewall.service /etc/systemd/system/x3-firewall.service &&
sudo /usr/local/sbin/x3-firewall check &&
sudo systemd-analyze verify /etc/systemd/system/x3-firewall.service &&
sudo systemctl daemon-reload
```

## التفعيل والتراجع

افتح قناة إدارة مستقلة قبل التفعيل. فعّل تراجعاً تلقائياً لتجربة القواعد:

```bash
sudo systemd-run --unit=x3-firewall-rollback --on-active=2min /usr/bin/systemctl stop x3-firewall.service &&
sudo systemctl start x3-firewall.service &&
sudo /usr/local/sbin/x3-firewall status
```

اختبر اتصال SSH جديد واتصال عميل لعبة بالمنافذ المنشورة. بعد نجاح الفحص:

```bash
sudo systemctl stop x3-firewall-rollback.timer &&
sudo systemctl enable x3-firewall.service
```

لإيقافه فوراً وإلغاء تشغيله التلقائي:

```bash
sudo systemctl disable --now x3-firewall.service
sudo nft list tables
```

الخدمة تدير جدول `inet x3_guard`، وتبقى سياسات الجدار الأخرى عند إيقافها.
