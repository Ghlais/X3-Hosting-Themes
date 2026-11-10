# Theme configuration / إعدادات الثيم

Native `theme.php` uses `settings`, as required by Paymenter v1.5.9. Paymenter stores fields under `theme_x3-hosting_<name>`. Use administration to edit them; no database migration or manual `.env` setting is needed.

## Product IDs / أرقام المنتجات

Open a product in the Paymenter admin editor; its numeric ID appears in the edit URL. Use the real ID of each product. These examples use **12** and **13 only as placeholders**. Replace them with your IDs and actual specifications. The values are intentionally not installed by default.

`featured_products`: `12,13` (first six valid IDs; blank falls back to the first three visible products).

`recommended_products`: `12`

`product_specs`:

```json
{
  "12": {
    "cpu": "4 vCPU",
    "ram": "8 GB",
    "storage": "80 GB NVMe",
    "network": {"en": "Your actual allocation", "ar": "التخصيص الفعلي لباقتك"},
    "location": {"en": "Your actual location", "ar": "الموقع الفعلي"}
  }
}
```

Supported public keys are `cpu`, `ram`, `storage`, `network`, `location`. A value can be a string or `{ "en": "…", "ar": "…" }`. Other fields are ignored. Missing specifications are omitted from cards and shown as “Not specified” in comparison. Invalid JSON is rejected by admin validation; malformed shapes also degrade safely at render time.

These fields are presentation metadata. Configuring “8 GB” here does not allocate RAM, change extension settings or change the price. Keep them consistent with the native product and provisioning configuration. Sensitive server extension settings are never used as public specs.

## Category art / رسومات الأقسام

Use real numeric **category** IDs, not product IDs:

```json
{"1":"compute","2":"game","3":"apps"}
```

Compute is a grille rack, game uses dual cooling units, apps uses modular bays. The original X3 Rackline identity is preserved. Illustrations do not promise hardware performance.

## Footer / التذييل

`footer_button_url`, `footer_button_en`, `footer_button_ar` control the button previously hardcoded to Paymenter. `discord_url` and `support_url` control optional destinations. A blank support URL uses native tickets unless tickets are disabled.

Additional `footer_links` example (at most eight):

```json
[
  {"label_en":"Terms","label_ar":"الشروط","url":"/your-existing-terms-page"},
  {"label_en":"Status","label_ar":"حالة الخدمات","url":"https://your-existing-status-site.example"}
]
```

Replace sample URLs with existing pages; the theme does not create these external/internal pages. Accepted URL schemes are http/https, or internal paths starting with a single slash. Invalid, protocol-relative and script URLs are not rendered. Text is escaped.

FAQ fields are `faq_1_q_en`, `faq_1_a_en`, `faq_1_q_ar`, `faq_1_a_ar` through index 5. Empty question/answer hides that language's entry. `faq_enabled` hides the section, `comparison_enabled` hides comparison controls.

## Pricing and client actions

Cards and comparison display one available native plan and its actual billing cycle; a free plan is preferred to match the native product price behavior. Other cycles are listed on product detail. Checkout links carry that selected plan ID. The core calculates final totals, options, taxes, coupons and orders.

The cart renewal display uses native original price for a first-period-only coupon, and the current discounted price for recurring coupons. It multiplies by the item quantity and excludes one-time setup from renewal. Prices are estimates under current configuration, not a guarantee that catalog prices will never change.

Dashboard invoice amounts use native `formattedRemaining` and invoice currency individually; different currencies are not summed. Services use the existing `expires_at`, status active and recurring plan, ordered before limit, without calling renewal methods or changing dates. Payment links open actual invoices; service links do not trigger renewals.
