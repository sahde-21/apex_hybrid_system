# Smart Assistant

**Rule-based assistant** — not generative AI. Answers a controlled set of business questions using local ERP queries.

## Supported intents

| Intent | Example phrases |
|--------|-----------------|
| `sales_month` | "show sales this month", "مبيعات الشهر", "فرۆشی ئەم مانگە" |
| `overdue_invoices` | "overdue invoice", "فواتير متأخرة" |
| `low_stock` | "low stock", "مخزون منخفض" |
| `health_score` | "business health", "صحة الأعمال" |
| `alerts` | "show alerts", "تنبيهات" |
| `forecast` | "sales forecast", "توقع المبيعات" |
| `top_products` | "top product", "أفضل منتج" |

## Security

- Maximum input length: 500 characters
- No arbitrary SQL
- No code execution
- No internet access
- Permission-aware per intent domain
- Unsupported questions return suggested prompts

## Localization

EN, AR, and CKB phrase aliases in `SmartAssistantService::resolveIntent()`.

## Route

`/intelligence/assistant` — requires `intelligence.assistant.use`.
