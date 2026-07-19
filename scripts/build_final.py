#!/usr/bin/env python3
"""Build scripts/ckb_translations.py — complete Sorani translations."""
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

OK = "بە سەرکەوتوویی"
UNDO = "ناتوانرێت ئەم کردارە بگەڕێنrێتەوە."

# Verified from lang/ckb.json
BASE = json.loads((ROOT / "lang/ckb.json").read_text(encoding="utf-8"))

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

LOWER = {
    "CRM interaction": "کارlêki CRM",
    "bill": "pسوڵey dabinér",
    "contact": "pêywéndi",
    "employee": "karménd",
    "expense": "khrjî",
    "financial report": "rápôrti darayî",
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

NO_FOUND = {
    "CRM interactions": "کارlêki CRM",
    "audit logs": "tômari pshkinin",
    "bills": "pسوڵey dabinér",
    "contacts": "pêywéndi",
    "employees": "karménd",
    "expenses": "khrjî",
    "financial reports": "rápôrti darayî",
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

UPDATE = {
    "CRM interaction": "کارlêki CRM",
    "bill": "pسوڵey dabinér",
    "customer or supplier": "krîar yan dabinér",
    "employee": "karménd",
    "expense": "khrjî",
    "financial report": "rápôrti darayî",
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

# Load extended manual translations
from ckb_rest_full import REST  # noqa: E402

def build() -> dict[str, str]:
    ckb = dict(BASE)
    ckb.update(REST)
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
    for en, ku in UPDATE.items():
        ckb[f"Update {en} details"] = f"nôikrdnewey wrrdékari {ku}"
    return ckb

ckb = build()
missing = [k for k in KEYS if k not in ckb]
if missing:
    raise SystemExit(f"Missing {len(missing)}: {missing[:5]}")

lines = ["CKB: dict[str, str] = {"]
for k in KEYS:
    lines.append(f"    {k!r}: {ckb[k]!r},")
lines.append("}")
lines.append("")
OUT.write_text("\n".join(lines), encoding="utf-8")
print(f"Wrote {len(KEYS)} entries to {OUT}")
