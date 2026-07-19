#!/usr/bin/env python3
"""Generate scripts/ckb_translations.py with complete proper Sorani translations."""
import ast
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
GEN = ROOT / "scripts" / "generate-lang-json.py"
OUT = ROOT / "scripts" / "ckb_translations.py"

text = GEN.read_text(encoding="utf-8")
m = re.search(r"AR: dict\[str, str\] = (\{.*?\n\})", text, re.DOTALL)
KEYS = list(ast.literal_eval(m.group(1)).keys())
BASE = json.loads((ROOT / "lang" / "ckb.json").read_text(encoding="utf-8"))

OK = "بە سەرکەوتوویی"
UNDO = "ناتوانرێت ئەم کردارە بگەڕێنرێتەوە."

ENTITY = {
    "Attendance": "ئامادەبوون",
    "Bank reconciliation": "ڕێکخستنی بانکی",
    "Bill": "پسوڵەی دابینکەر",
    "Bill of materials": "لیستی ماددەکان",
    "Branches": "لق",
    "Budgeting": "بودجە",
    "CRM interaction": "کارلێکی CRM",
    "Campaigns": "کەمپین",
    "Contact": "پەیوەندی",
    "Contracts": "گرێبەست",
    "Coupons": "کووپۆن",
    "Customer feedback": "ڕەخنەی کڕیار",
    "Delivery trips": "گەشتی گەیاندن",
    "Employee": "کارمەند",
    "Expense": "خرجی",
    "Financial report": "ڕاپۆرتی دارایی",
    "Fixed assets": "سامانی جێگیر",
    "Floor plans": "نەخشەی نهۆم",
    "Gift cards": "کارتی دیاری",
    "Inventory adjustment": "ڕێکخستنی کۆگا",
    "Invoice": "پسوڵە",
    "Journal entry": "تۆماری ڕۆژانە",
    "Knowledge base": "بنکەی زانیاری",
    "Lead management": "بەرێوەبردنی لید",
    "Leave requests": "داواکاری مۆلەت",
    "Loyalty programs": "بەرنامەی وفاداری",
    "Notification templates": "قالبەکانی ئاگاداری",
    "Payment": "پارەدان",
    "Payroll": "مووچە",
    "Performance reviews": "پێداچوونەوەی کارایی",
    "Price lists": "لیستی نرخ",
    "Product": "بەرهەم",
    "Production orders": "فەرمانی بەرهەمھێنان",
    "Project tasks": "ئەرکی پرۆژە",
    "Purchase order": "فەرمانی کڕین",
    "Quality control": "کۆنترۆلی جۆری",
    "Quotation": "پێشنیار",
    "Sale order": "فەرمانی فرۆشتن",
    "Shift management": "بەرێوەبردنی شیفت",
    "Shipping methods": "شێوازی گەیاندن",
    "Stock transfers": "گواستنەوەی کۆگا",
    "Subscriptions": "بەشداربوون",
    "Supplier evaluations": "هەڵسەنگاندنی دابینکەر",
    "Tax rate": "ڕێژەی باج",
    "Tickets": "تیکت",
    "Time logs": "تۆماری کات",
    "Variants": "جۆر",
    "Vehicle maintenance": "چاککردنەوەی ئۆتۆمبیل",
    "Warehouse": "کۆگا",
}

LOWER = {
    "CRM interaction": "کارلێکی CRM",
    "bill": "پسوڵەی دابینکەر",
    "contact": "پەیوەندی",
    "employee": "کارمەند",
    "expense": "خرجی",
    "financial report": "ڕاپۆرتی دارایی",
    "inventory adjustment": "ڕێکخستنی کۆگا",
    "invoice": "پسوڵە",
    "journal entry": "تۆماری ڕۆژانە",
    "payment": "پارەدان",
    "payroll": "مووچە",
    "product": "بەرهەم",
    "purchase order": "فەرمانی کڕین",
    "quotation": "پێشنیار",
    "sale order": "فەرمانی فرۆشتن",
    "tax rate": "ڕێژەی باج",
    "warehouse": "کۆگا",
    "account": "هەژمار",
}

NF = {
    "CRM interactions": "کارلێکی CRM",
    "audit logs": "تۆماری پشکنین",
    "bills": "پسوڵەی دابینکەر",
    "contacts": "پەیوەندی",
    "employees": "کارمەند",
    "expenses": "خرجی",
    "financial reports": "ڕاپۆرتی دارایی",
    "inventory adjustments": "ڕێکخستنی کۆگا",
    "invoices": "پسوڵە",
    "journal entries": "تۆماری ڕۆژانە",
    "payments": "پارەدان",
    "payrolls": "مووچە",
    "products": "بەرهەم",
    "purchase orders": "فەرمانی کڕین",
    "quotations": "پێشنیار",
    "records": "تۆمار",
    "sale orders": "فەرمانی فرۆشتن",
    "tax rates": "ڕێژەی باج",
    "warehouses": "کۆگا",
}

UD = {
    "CRM interaction": "کارلێکی CRM",
    "bill": "پسوڵەی دابینکەر",
    "customer or supplier": "کڕیار یان دابینکەر",
    "employee": "کارمەند",
    "expense": "خرجی",
    "financial report": "ڕاپۆرتی دارایی",
    "inventory adjustment": "ڕێکخستنی کۆگا",
    "invoice": "پسوڵە",
    "journal entry": "تۆماری ڕۆژانە",
    "payment": "پارەدان",
    "payroll": "مووچە",
    "product and inventory": "بەرهەم و کۆگا",
    "purchase order": "فەرمانی کڕین",
    "quotation": "پێشنیار",
    "sale order": "فەرمانی فرۆشتن",
    "tax rate": "ڕێژەی باج",
    "warehouse": "کۆگا",
}

exec(Path(__file__).with_name("ckb_manual.py").read_text(encoding="utf-8"))


def build():
    ckb = dict(BASE)
    ckb.update(MANUAL)
    for en, ku in ENTITY.items():
        ckb[f"{en} created successfully."] = f"{ku} {OK} دروستکرا."
        ckb[f"{en} deleted successfully."] = f"{ku} {OK} سڕایەوە."
        ckb[f"{en} updated successfully."] = f"{ku} {OK} نوێکرایەوە."
        ckb[f"Create {en}"] = f"دروستکردنی {ku}"
        ckb[f"Edit {en}"] = f"دەستکاری {ku}"
        ckb[f"Manage {en}"] = f"بەڕێوەبردنی {ku}"
        ckb[f"Delete {en}"] = f"سڕینەوەی {ku}"
        ckb[f"Update {en} details."] = f"نوێکردنەوەی وردەکاری {ku}."
        ckb[f"No {en.lower()} found."] = f"هیچ {ku} نەدۆزرایەوە."
    for en, ku in LOWER.items():
        ckb[f"Add {en}"] = f"زیادکردنی {ku}"
        ckb[f"Create {en}"] = f"دروستکردنی {ku}"
        ckb[f"Edit {en}"] = f"دەستکاری {ku}"
        ckb[f"Delete {en}"] = f"سڕینەوەی {ku}"
    for en, ku in NF.items():
        ckb[f"No {en} found."] = f"هیچ {ku} نەدۆزرایەوە."
    ckb["No passkeys yet"] = "هێشتا کلیلی چوونەژوورەوە نییە"
    for en, ku in UD.items():
        ckb[f"Update {en} details"] = f"نوێکردنەوەی وردەکاری {ku}"
    return ckb


if __name__ == "__main__":
    ckb = build()
    missing = [k for k in KEYS if k not in ckb]
    if missing:
        raise SystemExit(f"Missing {len(missing)}: {missing[:8]}")
    lines = ["CKB: dict[str, str] = {"]
    for k in KEYS:
        lines.append(f"    {k!r}: {ckb[k]!r},")
    lines.append("}")
    lines.append("")
    OUT.write_text("\n".join(lines), encoding="utf-8")
    print(f"Wrote {len(KEYS)} entries")
