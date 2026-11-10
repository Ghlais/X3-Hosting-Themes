<?php
// Theme-local translation repairs. Paymenter's translation files stay untouched.
return static function (string $key, array $replace = []): string {
    $arabic = [
        'account.expires' => 'تنتهي في :date',
        'account.current_device' => 'الجهاز الحالي',
        'common.select.placeholder' => 'يرجى اختيار :name',
        'invoices.amount_due' => 'المبلغ المستحق: :amount',
        'invoices.account_credits' => 'رصيد الحساب',
        'invoices.available_credits' => 'الرصيد المتاح: :amount',
        'invoices.apply_credits_and_pay' => 'استخدم الرصيد وادفع',
        'invoices.download_pdf' => 'تنزيل PDF',
        'services.renews_in' => 'يتجدد خلال',
        'services.label_modal_title' => 'تعديل اسم الخدمة',
        'services.label' => 'اسم الخدمة',
        'services.label_placeholder' => 'أدخل اسماً مخصصاً لهذه الخدمة',
        'services.update_label' => 'تحديث الاسم',
        'services.renews_on' => 'موعد التجديد',
        'ticket.uploading_files' => 'جارٍ رفع الملفات',
        'ticket.close_ticket' => 'إغلاق التذكرة',
        'ticket.close_ticket_confirmation' => 'هل تريد إغلاق هذه التذكرة؟ لا يمكن التراجع عن هذا الإجراء.',
    ];
    if (!str_starts_with(app()->getLocale(), 'ar') || !isset($arabic[$key])) {
        return __($key, $replace);
    }
    $tokens = [];
    foreach ($replace as $name => $value) { $tokens[':'.$name] = (string) $value; }
    return strtr($arabic[$key], $tokens);
};
