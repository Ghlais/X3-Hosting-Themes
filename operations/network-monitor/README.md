# X3 Network Monitor

لوحة تعرض حركة الشبكة والحزم والإسقاطات والمعالج والذاكرة وconntrack عند توفره. تحفظ آخر 24 ساعة محلياً. [معاينة تجريبية](../../docs/previews/network-monitor-demo.jpg)

## التركيب على Ubuntu

من جذر المستودع، اعرف اسم واجهة الشبكة من `ip -brief link`. راجع `operations/network-monitor/config.json` وعدّل قيمة `interface` واسم النود. بعدها نفّذ:

```bash
cd operations/network-monitor &&
sudo bash install.sh &&
sudo systemctl is-active x3-network-monitor &&
sudo ss -lnt 'sport = :8787'
```

المثبّت يخصص كلمة مرور لكل تثبيت. اعرضها من جلستك الإدارية عبر `sudo cat /etc/x3-network-monitor/dashboard-password`. من جهازك، استبدل بيانات SSH بالمثال أدناه واترك الأمر يعمل:

```bash
ssh -N -o ExitOnForwardFailure=yes -L 127.0.0.1:8787:127.0.0.1:8787 USER@SSH_HOST
```

افتح `http://127.0.0.1:8787/` بحساب `operator`. واجهة الخدمة تستمع على loopback.

## المعاينة والإيقاف

```bash
python3 monitor.py --config config.json --state ./demo-state --demo
sudo journalctl -u x3-network-monitor -n 30 --no-pager
sudo systemctl disable --now x3-network-monitor
```

شغّل أمر المعاينة من مجلد الأداة على جهاز تجريبي. الإيقاف يبقي السجل في `/var/lib/x3-network-monitor`. للتشغيل لاحقاً استخدم `sudo systemctl enable --now x3-network-monitor`.
