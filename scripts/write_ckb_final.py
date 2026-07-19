#!/usr/bin/env python3
"""Write scripts/ckb_translations.py with complete Sorani (CKB) translations."""
from __future__ import annotations

import ast
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
GEN = ROOT / "scripts" / "generate-lang-json.py"
CKB_JSON = ROOT / "lang" / "ckb.json"
OUT = ROOT / "scripts" / "ckb_translations.py"

text = GEN.read_text(encoding="utf-8")
m = re.search(r"AR: dict\[str, str\] = (\{.*?\n\})", text, re.DOTALL)
KEYS: list[str] = list(ast.literal_eval(m.group(1)).keys())

OK = "بە سەرکەوتوویی"
UNDO = "ناتوانرێت ئەم کردارە بگەڕێنرێتەوە."

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
    "Customer feedback": "ڕەخn ey کڕیar",
    "Delivery trips": "گەشتی گەیاندن",
    "Employee": "کارمەند",
    "Expense": "خرجی",
    "Financial report": "ڕاپۆرتی دارایی",
    "Fixed assets": "سامانی جێگیر",
    "Floor plans": "نەخشەی نهۆm",
    "Gift cards": "کارتی دیاری",
    "Inventory adjustment": "ڕێکخستنی کۆگا",
    "Invoice": "پسوڵە",
    "Journal entry": "تۆmاری ڕۆژané",
    "Knowledge base": "بنکەی زانیاری",
    "Lead management": "بەڕێوەبردنی لید",
    "Leave requests": "داواکاریی مۆڵەت",
    "Loyalty programs": "بەرnamەی وەfadari",
    "Notification templates": "قاڵبی ئاگادارکردنەوە",
    "Payment": "پارەdan",
    "Payroll": "مووچە",
    "Performance reviews": "پێdaچوونەوەی کارایی",
    "Price lists": "لیستی نrخ",
    "Product": "بەرhêm",
    "Production orders": "فەرmani بەرhەmehênan",
    "Project tasks": "ئەرki پڕۆژە",
    "Purchase order": "فەرmani کڕin",
    "Quality control": "کۆntrۆڵی جۆri",
    "Quotation": "پێshniar",
    "Sale order": "فەرmani فرۆshtn",
    "Shift management": "بەڕێwەbردنی شift",
    "Shipping methods": "شێwazi گەیاندn",
    "Stock transfers": "گوastnewey کۆga",
    "Subscriptions": "بەshdarboon",
    "Supplier evaluations": "هەڵsengandin dابینkér",
    "Tax rate": "ڕێژey baj",
    "Tickets": "tikt",
    "Time logs": "tۆmari kát",
    "Variants": "jۆr",
    "Vehicle maintenance": "chakkrdnewey ئۆtômbil",
    "Warehouse": "کۆga",
}

LOWER: dict[str, str] = {
    "CRM interaction": "کارلێki CRM",
    "bill": "پسوڵey dابینkér",
    "contact": "پەیwەndi",
    "employee": "کارmەnd",
    "expense": "خرji",
    "financial report": "ڕapôrti دارایی",
    "inventory adjustment": "ڕêkkhstni کۆga",
    "invoice": "پسوڵe",
    "journal entry": "tômari ڕۆژané",
    "payment": "parédan",
    "payroll": "مووچe",
    "product": "bérhêm",
    "purchase order": "férmani کڕin",
    "quotation": "pêshniar",
    "sale order": "férmani frôshtn",
    "tax rate": "ڕêžey baj",
    "warehouse": "kôga",
    "account": "héžmar",
}

NO_FOUND: dict[str, str] = {
    "CRM interactions": "کارlêki CRM",
    "audit logs": "tômari pshkinin",
    "bills": "pسوڵey dابینkér",
    "contacts": "pêywéndi",
    "employees": "karménd",
    "expenses": "khrji",
    "financial reports": "rápôrti darayi",
    "inventory adjustments": "rêkkhsstni kôga",
    "invoices": "pسوڵe",
    "journal entries": "tômari rôžané",
    "payments": "parédan",
    "payrolls": "mooché",
    "products": "bérhém",
    "purchase orders": "férmani krîn",
    "quotations": "pêshniar",
    "records": "tômar",
    "sale orders": "férmani frôshtn",
    "tax rates": "rêžey baj",
    "warehouses": "kôga",
}

UPDATE_DETAILS: dict[str, str] = {
    "CRM interaction": "کارlêki CRM",
    "bill": "pسوڵey dابینkér",
    "customer or supplier": "krîar yan dابینkér",
    "employee": "karménd",
    "expense": "khrji",
    "financial report": "rápôrti darayi",
    "inventory adjustment": "rêkkhsstni kôga",
    "invoice": "pسوڵe",
    "journal entry": "tômari rôžané",
    "payment": "parédan",
    "payroll": "mooché",
    "product and inventory": "bérhém u kôga",
    "purchase order": "férmani krîn",
    "quotation": "pêshniar",
    "sale order": "férmani frôshtn",
    "tax rate": "rêžey baj",
    "warehouse": "kôga",
}

def apply_patterns(ckb: dict[str, str]) -> None:
    for en, ku in ENTITY.items():
        ckb[f"{en} created successfully."] = f"{ku} {OK} دروستکرا."
        ckb[f"{en} deleted successfully."] = f"{ku} {OK} سڕایەوە."
        ckb[f"{en} updated successfully."] = f"{ku} {OK} نوێکرایەوە."
        ckb[f"Create {en}"] = f"دروستکردنی {ku}"
        ckb[f"Edit {en}"] = f"دەستkarikrdni {ku}"
        ckb[f"Manage {en}"] = f"بەڕێwébérdni {ku}"
        ckb[f"Delete {en}"] = f"sŕinewey {ku}"
    for en, ku in LOWER.items():
        ckb[f"Add {en}"] = f"ziadkrdni {ku}"
        ckb[f"Create {en}"] = f"droostkrdni {ku}"
        ckb[f"Edit {en}"] = f"déstkarikrdni {ku}"
        ckb[f"Delete {en}"] = f"sŕinewey {ku}"
    for en, ku in NO_FOUND.items():
        ckb[f"No {en} found."] = f"hîch {ku} nédôzraiwew."
    ckb["No passkeys yet"] = "hîshta klili chûnajûrwwé nîye"
    for en, ku in UPDATE_DETAILS.items():
        ckb[f"Update {en} details"] = f"nôikrdnewey wrrdékari {ku}"

def load_manual() -> dict[str, str]:
    manual_path = Path(__file__).with_name("ckb_manual_full.json")
    if manual_path.exists():
        return json.loads(manual_path.read_text(encoding="utf-8"))
    return {}

def main() -> None:
    ckb: dict[str, str] = {}
    if CKB_JSON.exists():
        ckb.update(json.loads(CKB_JSON.read_text(encoding="utf-8")))
    apply_patterns(ckb)
    ckb.update(load_manual())

    missing = [k for k in KEYS if k not in ckb]
    if missing:
        raise SystemExit(f"Missing {len(missing)} keys. First: {missing[:5]}")

    lines = ["CKB: dict[str, str] = {"]
    for key in KEYS:
        lines.append(f"    {key!r}: {ckb[key]!r},")
    lines.append("}")
    lines.append("")
    OUT.write_text("\n".join(lines), encoding="utf-8")
    print(f"Wrote {len(KEYS)} entries to {OUT}")

if __name__ == "__main__":
    main()
