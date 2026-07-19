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
    "Coupons": "کوپۆn",
    "Customer feedback": "ڕەخn ey کڕیar",
    "Delivery trips": "گەشتی گەیاندn",
    "Employee": "کارmەnd",
    "Expense": "خرji",
    "Financial report": "ڕapôrti دارایی",
    "Fixed assets": "سامانی جێگیر",
    "Floor plans": "نەخsh ey nehôm",
    "Gift cards": "kárti diyari",
    "Inventory adjustment": "rêkkhsstni kôga",
    "Invoice": "pسوڵe",
    "Journal entry": "tômari rôžané",
    "Knowledge base": "bénké zanyari",
    "Lead management": "bérêwébérdni lid",
    "Leave requests": "dawakariy môllet",
    "Loyalty programs": "bérnaméy wéfadari",
    "Notification templates": "qálbi agahdarkrdnwwé",
    "Payment": "parédan",
    "Payroll": "mooché",
    "Performance reviews": "pêdachûnewey karayi",
    "Price lists": "listi nrx",
    "Product": "bérhém",
    "Production orders": "férmani bérhémehênan",
    "Project tasks": "érki prôjé",
    "Purchase order": "férmani krîn",
    "Quality control": "kôntrrôli jôri",
    "Quotation": "pêshniar",
    "Sale order": "férmani frôshtn",
    "Shift management": "bérêwébérdni shift",
    "Shipping methods": "shêwazi geyandn",
    "Stock transfers": "guastnewey kôga",
    "Subscriptions": "béshdarboown",
    "Supplier evaluations": "héllsengandin dabinér",
    "Tax rate": "rêžey baj",
    "Tickets": "tikt",
    "Time logs": "tômari kát",
    "Variants": "jôr",
    "Vehicle maintenance": "chakkrdnewey otômbil",
    "Warehouse": "kôga",
}

LOWER: dict[str, str] = {
    "CRM interaction": "کارlêki CRM",
    "bill": "pسوڵey dabinér",
    "contact": "pêywéndi",
    "employee": "karménd",
    "expense": "khrji",
    "financial report": "rápôrti darayi",
    "inventory adjustment": "rêkkhsstni kôga",
    "invoice": "pسوڵe",
    "journal entry": "tômari rôžané",
    "payment": "parédan",
    "payroll": "mooché",
    "product": "bérhém",
    "purchase order": "férmani krîn",
    "quotation": "pêshniar",
    "sale order": "férmani frôshtn",
    "tax rate": "rêžey baj",
    "warehouse": "kôga",
    "account": "héžmar",
}

NO_FOUND: dict[str, str] = {
    "CRM interactions": "کارlêki CRM",
    "audit logs": "tômari pshkinin",
    "bills": "pسوڵey dabinér",
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
    "bill": "pسوڵey dabinér",
    "customer or supplier": "krîar yan dabinér",
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

# Import full manual translations
from ckb_manual_full import MANUAL  # noqa: E402

def build() -> dict[str, str]:
    ckb: dict[str, str] = dict(MANUAL)
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
    return ckb

def main() -> None:
    ckb = build()
    missing = [k for k in KEYS if k not in ckb]
    if missing:
        raise SystemExit(f"Missing {len(missing)}: {missing[:10]}")
    lines = ["CKB: dict[str, str] = {"]
    for key in KEYS:
        lines.append(f"    {key!r}: {ckb[key]!r},")
    lines.append("}")
    lines.append("")
    OUT.write_text("\n".join(lines), encoding="utf-8")
    print(f"Wrote {len(KEYS)} entries")

if __name__ == "__main__":
    main()
