# X3 Hosting Themes

مستودع ثيمات **X3 Hosting** مع القوالب، الأصول المبنية، المثبّتات، المعاينات، وتراخيص المصادر المرفقة بكل حزمة.

| الثيم | الإصدار | التطبيق المستهدف | دليل التشغيل |
| --- | --- | --- | --- |
| Paymenter X3 Rackline Studio 3D | 1.4.0 فوق قاعدة Rackline 1.1.0 | Paymenter 1.5.9 مع PHP 8.3+ | [تركيب وفحص وتراجع](paymenter/README.md) |
| Pterodactyl X3 Rackline Glass | 1.1.1 | Pterodactyl Panel 1.15.1 و1.15.2 | [تركيب وفحص وتراجع](pterodactyl/README.md) |

```text
X3-Hosting-Themes/
├── paymenter/
│   ├── base/       # ثيم x3-hosting الكامل وملفات Vite المبنية
│   └── studio/     # تحديث Studio 3D التراكمي
└── pterodactyl/
    └── glass/      # ثيم Glass مع إصلاح الهيدر والبحث
```

## رفع المصدر على GitHub

1. فك ضغط `X3-Hosting-Themes-GitHub.zip` على الكمبيوتر وافتح طرفية داخل مجلد `X3-Hosting-Themes` الذي يحتوي هذا الملف.
2. أنشئ مستودعاً فارغاً باسم `X3-Hosting-Themes` من [GitHub](https://github.com/new). اختَر **Private** للأرشفة الشخصية، واترك خيارات إنشاء README و`.gitignore` والترخيص في نموذج GitHub فارغة حتى يكون أول commit من هذه الحزمة.
3. شغّل الأوامر التالية، واستبدل `YOUR_GITHUB_USERNAME` باسم حسابك.

```bash
git init -b main
git add .
git status --short
git commit -m "Archive X3 Hosting themes"
git remote add origin https://github.com/YOUR_GITHUB_USERNAME/X3-Hosting-Themes.git
git push -u origin main
```

إذا طلب Git الاسم والبريد، اضبطهما داخل هذا المستودع ثم أعد `git commit`:

```bash
git config user.name "YOUR_NAME"
git config user.email "YOUR_GITHUB_EMAIL"
```

تستخدم الحزمة `.gitattributes` لحفظ البايتات الأصلية التي يراجعها المثبّت عبر SHA-256. عند تعديل ملفات الثيم لاحقاً، راجع بصمات الحزمة الخاصة به قبل محاولة تركيب النسخة المعدّلة.

## استنساخ المستودع

على جهاز لديه صلاحية قراءة المستودع، استبدل اسم الحساب ثم شغّل:

```bash
git clone https://github.com/YOUR_GITHUB_USERNAME/X3-Hosting-Themes.git
cd X3-Hosting-Themes
```

ابدأ من دليل Paymenter أو Pterodactyl في الجدول حسب الثيم الذي تريد تركيبه.

## ملفات التثبيت في Releases

من المستودع افتح **Releases → Draft a new release**، واستخدم مثلاً الوسم `themes-2026-11-10`. ارفع الملفين الجاهزين المرفقين مع حزمة المصدر:

- `X3-Paymenter-Complete-v1.4.0.zip`
- `X3-Rackline-Glass-v1.1.1.zip`

تحتوي ملفات التثبيت على ثيمات X3 فقط. جهّز تثبيتاً عاملاً من التطبيق المستهدف قبل استخدامها. تفاصيل رفع المصدر ونشر المرفقات في [دليل GitHub لرفع الكود](https://docs.github.com/en/migrations/importing-source-code/using-the-command-line-to-import-source-code/adding-locally-hosted-code-to-github) و[دليل GitHub للإصدارات](https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository).

المصدر محفوظ مع تراخيص Paymenter وPterodactyl وخط Noto Sans Arabic في الحزم المعنية. الصور المرفقة معاينات ببيانات تجريبية.
