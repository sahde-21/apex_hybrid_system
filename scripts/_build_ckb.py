#!/usr/bin/env python3
"""Generate scripts/ckb_translations.py with complete Sorani translations."""
from __future__ import annotations

import ast
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
GEN = ROOT / "scripts" / "generate-lang-json.py"
OUT = ROOT / "scripts" / "ckb_translations.py"

text = GEN.read_text(encoding="utf-8")
m = re.search(r"AR: dict\[str, str\] = (\{.*?\n\})", text, re.DOTALL)
KEYS: list[str] = list(ast.literal_eval(m.group(1)).keys())

OK = "بە سەرکەوتوویی"

ENTITY: dict[str, str] = {
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
    "Coupons": "کوپۆن",
    "Customer feedback": "ڕەخnەی کڕیar",
    "Delivery trips": "گەشتی گەیاندن",
    "Employee": "کارمەند",
    "Expense": "خرجی",
    "Financial report": "ڕاپۆرتی دارایی",
    "Fixed assets": "سامانی جێگیر",
    "Floor plans": "نەخشەی نهۆm",
    "Gift cards": "کارتی دیاری",
    "Inventory adjustment": "ڕێکخستنی کۆگا",
    "Invoice": "پسوڵە",
    "Journal entry": "تۆmاری ڕۆژanە",
    "Knowledge base": "بنکەی زانیاری",
    "Lead management": "بەڕێوەبردنی لید",
    "Leave requests": "داواکاریی مۆڵەت",
    "Loyalty programs": "بەرنامەی وەfadari",
    "Notification templates": "قاڵبی ئاگادارکردنەوە",
    "Payment": "پارەdan",
    "Payroll": "مووچە",
    "Performance reviews": "پێdaچوونەوەی کارایی",
    "Price lists": "لیستی نrخ",
    "Product": "بەرhەم",
    "Production orders": "فەرmani بەرhەmehێnan",
    "Project tasks": "ئەرki پڕۆژە",
    "Purchase order": "فەرmani کڕin",
    "Quality control": "کۆntrۆڵی جۆri",
    "Quotation": "پێshniar",
    "Sale order": "فەرmani فرۆshtn",
    "Shift management": "بەڕێwەbردنی شift",
    "Shipping methods": "شێwazi گەیاندn",
    "Stock transfers": "گوastnewey کۆga",
    "Subscriptions": "بەshdarboon",
    "Supplier evaluations": "هەڵsengandin dابینkەر",
    "Tax rate": "ڕێژey baj",
    "Tickets": "tikt",
    "Time logs": "tۆmari kات",
    "Variants": "jۆr",
    "Vehicle maintenance": "chakkrdnewey ئۆtۆmbil",
    "Warehouse": "کۆga",
}

LOWER: dict[str, str] = {
    "CRM interaction": "کارلێki CRM",
    "bill": "پسوڵey dابینkەر",
    "contact": "پەیwەndi",
    "employee": "کارmەnd",
    "expense": "خرji",
    "financial report": "ڕapۆrti دارایی",
    "inventory adjustment": "ڕێkخstni کۆga",
    "invoice": "پسوڵe",
    "journal entry": "tۆmari ڕۆژanە",
    "payment": "parەdan",
    "payroll": "مووچe",
    "product": "bەرhەم",
    "purchase order": "فەرmani کڕin",
    "quotation": "پێshniar",
    "sale order": "فەرmani فرۆshtn",
    "tax rate": "ڕێژey baj",
    "warehouse": "کۆga",
    "account": "هەژmar",
}

NO_FOUND: dict[str, str] = {
    "CRM interactions": "کارلێki CRM",
    "audit logs": "tۆmari پشkinin",
    "bills": "پسوڵey dابینkەر",
    "contacts": "پەیwەndi",
    "employees": "کارmەnd",
    "expenses": "خرji",
    "financial reports": "ڕapۆrti دارایی",
    "inventory adjustments": "ڕێkخstni کۆga",
    "invoices": "پسوڵe",
    "journal entries": "tۆmari ڕۆژanە",
    "payments": "parەdan",
    "payrolls": "مووچe",
    "products": "bەرhەم",
    "purchase orders": "فەرmani کڕin",
    "quotations": "پێshniar",
    "records": "tۆmar",
    "sale orders": "فەرmani فرۆshtn",
    "tax rates": "ڕێژey baj",
    "warehouses": "کۆga",
}

UPDATE_DETAILS: dict[str, str] = {
    "CRM interaction": "کارلێki CRM",
    "bill": "پسوڵey dابینkەر",
    "customer or supplier": "کڕiar yan dابینkەر",
    "employee": "کارmەnd",
    "expense": "خرji",
    "financial report": "ڕapۆrti دارایی",
    "inventory adjustment": "ڕێkخstni کۆga",
    "invoice": "پسوڵe",
    "journal entry": "tۆmari ڕۆژanە",
    "payment": "parەdan",
    "payroll": "مووچe",
    "product and inventory": "bەرhەم w کۆga",
    "purchase order": "فەرmani کڕin",
    "quotation": "پێshniar",
    "sale order": "فەرmani فرۆshtn",
    "tax rate": "ڕێژey baj",
    "warehouse": "کۆga",
}
