<?php
return [
 'name' => 'X3 Hosting — Rackline',
 'author' => 'X3 Hosting',
 'version' => '1.1.0',
 'settings' => [
 [
 'name' => 'brand_name', 'label' => 'Brand name / اسم الهوية', 'type' => 'text', 'default' => 'X3 Hosting',
 ],
 [
 'name' => 'hero_title_en', 'label' => 'Hero title (English)', 'type' => 'text', 'default' => 'Your next idea.
Its own infrastructure.',
 ],
 [
 'name' => 'hero_title_ar', 'label' => 'عنوان الواجهة (العربية)', 'type' => 'text', 'default' => 'فكرتك القادمة.
بنية تحتية تليق بها.',
 ],
 [
 'name' => 'hero_text_en', 'label' => 'Hero description (English)', 'type' => 'textarea', 'default' => 'Choose your hosting. Shape your configuration. Manage your services, billing and support from one place.',
 ],
 [
 'name' => 'hero_text_ar', 'label' => 'وصف الواجهة (العربية)', 'type' => 'textarea', 'default' => 'اختر استضافتك واضبط إعداداتك وأدر خدماتك وفواتيرك وتواصل مع الدعم، من مكان واحد.',
 ],
 [
 'name' => 'announcement_en', 'label' => 'Announcement (English, empty hides it)', 'type' => 'text', 'default' => '',
 ],
 [
 'name' => 'announcement_ar', 'label' => 'الإعلان (العربية، فارغ للإخفاء)', 'type' => 'text', 'default' => '',
 ],
 [
 'name' => 'footer_en', 'label' => 'Footer description (English)', 'type' => 'text', 'default' => 'Infrastructure for your next idea.',
 ],
 [
 'name' => 'footer_ar', 'label' => 'وصف التذييل (العربية)', 'type' => 'text', 'default' => 'بنية تحتية لفكرتك القادمة.',
 ],
 [
 'name' => 'motion', 'label' => 'Subtle motion / الحركة الهادئة', 'type' => 'checkbox', 'default' => true,
 'database_type' => 'boolean',
 ],
 [
 'name' => 'direct_checkout', 'label' => 'Skip product overview / تخطي صفحة التفاصيل', 'type' => 'checkbox', 'default' => false,
 'database_type' => 'boolean',
 ],
 [
 'name' => 'small_images', 'label' => 'Compact category images / صور صغيرة', 'type' => 'checkbox', 'default' => true,
 'database_type' => 'boolean',
 ],
 [
 'name' => 'show_category_description', 'label' => 'Show descriptions / عرض الوصف', 'type' => 'checkbox', 'default' => true,
 'database_type' => 'boolean',
 ],
 [
 'name' => 'default_mode', 'label' => 'Default appearance / المظهر الافتراضي', 'type' => 'select', 'default' => 'dark',
 'options' => ['dark' => 'Dark / داكن', 'light' => 'Light / فاتح', 'system' => 'System / النظام'],
 ],
 [
 'name' => 'logo_display', 'label' => 'Logo display / عرض الشعار', 'type' => 'select', 'default' => 'logo-and-name',
 'options' => ['logo-only' => 'Logo / الشعار', 'logo-and-name' => 'Logo + name / الشعار والاسم'],
 ],
        ['name' => 'footer_button_url', 'label' => 'Footer button URL / رابط زر التذييل', 'type' => 'text', 'default' => 'https://paymenter.org/'],
        ['name' => 'footer_button_en', 'label' => 'Footer button text (English)', 'type' => 'text', 'default' => 'Powered by Paymenter ↗'],
        ['name' => 'footer_button_ar', 'label' => 'نص زر التذييل (عربي)', 'type' => 'text', 'default' => 'يعمل بواسطة Paymenter ↗'],
        ['name' => 'discord_url', 'label' => 'Discord URL / رابط ديسكورد', 'type' => 'text', 'default' => ''],
        ['name' => 'support_url', 'label' => 'Support URL / رابط الدعم (blank = Paymenter tickets)', 'type' => 'text', 'default' => ''],
        ['name' => 'footer_links', 'label' => 'Extra footer links / روابط إضافية (JSON)', 'type' => 'textarea', 'default' => '[]', 'validation' => ['nullable', 'json']],
        ['name' => 'featured_products', 'label' => 'Homepage product IDs / أرقام منتجات الرئيسية (comma separated; blank = first 3)', 'type' => 'text', 'default' => ''],
        ['name' => 'recommended_products', 'label' => 'Recommended product IDs / أرقام المنتجات الموصى بها (comma separated)', 'type' => 'text', 'default' => ''],
        ['name' => 'product_specs', 'label' => 'Public product specifications / مواصفات المنتجات (JSON keyed by product ID)', 'type' => 'textarea', 'default' => '{}', 'validation' => ['nullable', 'json']],
        ['name' => 'category_art', 'label' => 'Category hardware art / رسومات الأقسام (JSON: category ID → compute/game/apps)', 'type' => 'textarea', 'default' => '{}', 'validation' => ['nullable', 'json']],
        ['name' => 'comparison_enabled', 'label' => 'Enable plan comparison / تفعيل المقارنة', 'type' => 'checkbox', 'default' => true, 'database_type' => 'boolean'],
        ['name' => 'faq_enabled', 'label' => 'Show homepage FAQ / عرض الأسئلة الشائعة', 'type' => 'checkbox', 'default' => true, 'database_type' => 'boolean'],
        ['name' => 'faq_1_q_en', 'label' => 'FAQ 1: Question / السؤال (en)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/en/ui.php')['faq_1_q'] ?? ''],
        ['name' => 'faq_1_a_en', 'label' => 'FAQ 1: Answer / الجواب (en)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/en/ui.php')['faq_1_a'] ?? ''],
        ['name' => 'faq_1_q_ar', 'label' => 'FAQ 1: Question / السؤال (ar)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/ar/ui.php')['faq_1_q'] ?? ''],
        ['name' => 'faq_1_a_ar', 'label' => 'FAQ 1: Answer / الجواب (ar)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/ar/ui.php')['faq_1_a'] ?? ''],
        ['name' => 'faq_2_q_en', 'label' => 'FAQ 2: Question / السؤال (en)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/en/ui.php')['faq_2_q'] ?? ''],
        ['name' => 'faq_2_a_en', 'label' => 'FAQ 2: Answer / الجواب (en)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/en/ui.php')['faq_2_a'] ?? ''],
        ['name' => 'faq_2_q_ar', 'label' => 'FAQ 2: Question / السؤال (ar)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/ar/ui.php')['faq_2_q'] ?? ''],
        ['name' => 'faq_2_a_ar', 'label' => 'FAQ 2: Answer / الجواب (ar)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/ar/ui.php')['faq_2_a'] ?? ''],
        ['name' => 'faq_3_q_en', 'label' => 'FAQ 3: Question / السؤال (en)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/en/ui.php')['faq_3_q'] ?? ''],
        ['name' => 'faq_3_a_en', 'label' => 'FAQ 3: Answer / الجواب (en)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/en/ui.php')['faq_3_a'] ?? ''],
        ['name' => 'faq_3_q_ar', 'label' => 'FAQ 3: Question / السؤال (ar)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/ar/ui.php')['faq_3_q'] ?? ''],
        ['name' => 'faq_3_a_ar', 'label' => 'FAQ 3: Answer / الجواب (ar)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/ar/ui.php')['faq_3_a'] ?? ''],
        ['name' => 'faq_4_q_en', 'label' => 'FAQ 4: Question / السؤال (en)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/en/ui.php')['faq_4_q'] ?? ''],
        ['name' => 'faq_4_a_en', 'label' => 'FAQ 4: Answer / الجواب (en)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/en/ui.php')['faq_4_a'] ?? ''],
        ['name' => 'faq_4_q_ar', 'label' => 'FAQ 4: Question / السؤال (ar)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/ar/ui.php')['faq_4_q'] ?? ''],
        ['name' => 'faq_4_a_ar', 'label' => 'FAQ 4: Answer / الجواب (ar)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/ar/ui.php')['faq_4_a'] ?? ''],
        ['name' => 'faq_5_q_en', 'label' => 'FAQ 5: Question / السؤال (en)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/en/ui.php')['faq_5_q'] ?? ''],
        ['name' => 'faq_5_a_en', 'label' => 'FAQ 5: Answer / الجواب (en)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/en/ui.php')['faq_5_a'] ?? ''],
        ['name' => 'faq_5_q_ar', 'label' => 'FAQ 5: Question / السؤال (ar)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/ar/ui.php')['faq_5_q'] ?? ''],
        ['name' => 'faq_5_a_ar', 'label' => 'FAQ 5: Answer / الجواب (ar)', 'type' => 'textarea', 'default' => (require __DIR__ . '/lang/ar/ui.php')['faq_5_a'] ?? ''],


 ],
];
