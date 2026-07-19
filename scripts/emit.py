#!/usr/bin/env python3
"""Emit scripts/ckb_translations.py."""
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
UNDO = "ناتوانرێت ئەم کردارە بگەڕێنرێتەوە."

E = {
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

L = {
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

NF = {
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

UD = {
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

# Load remaining manual strings
MANUAL_PATH = Path(__file__).with_name("ckb_rest.json")
REST = json.loads(MANUAL_PATH.read_text(encoding="utf-8")) if MANUAL_PATH.exists() else {}

def build():
    ckb = dict(json.loads((ROOT / "lang/ckb.json").read_text(encoding="utf-8")))
    ckb.update(REST)
    for en, ku in E.items():
        ckb[f"{en} created successfully."] = f"{ku} {OK} دروستکرا."
        ckb[f"{en} deleted successfully."] = f"{ku} {OK} سڕایەوە."
        ckb[f"{en} updated successfully."] = f"{ku} {OK} نوێکرایەوە."
        ckb[f"Create {en}"] = f"دروستکردنی {ku}"
        ckb[f"Edit {en}"] = f"دەستkarikrdni {ku}"
        ckb[f"Manage {en}"] = f"بەڕێwébérdni {ku}"
        ckb[f"Delete {en}"] = f"sŕinewey {ku}"
    for en, ku in L.items():
        ckb[f"Add {en}"] = f"ziadkrdni {ku}"
        ckb[f"Create {en}"] = f"droostkrdni {ku}"
        ckb[f"Edit {en}"] = f"déstkarikrdni {ku}"
        ckb[f"Delete {en}"] = f"sŕinewey {ku}"
    for en, ku in NF.items():
        ckb[f"No {en} found."] = f"hîch {ku} nédôzraiwew."
    ckb["No passkeys yet"] = "hîshta klili chûnajûrwwé nîye"
    for en, ku in UD.items():
        ckb[f"Update {en} details"] = f"nôikrdnewey wrrdékari {ku}"
    return ckb

ckb = build()
missing = [k for k in KEYS if k not in ckb]
if missing:
    # write placeholder json for missing keys
    Path(__file__).with_name("ckb_rest.json").write_text(
        json.dumps({k: REST.get(k, f"__TODO__{k}") for k in missing}, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )
    raise SystemExit(f"{len(missing)} missing — see ckb_rest.json")

lines = ["CKB: dict[str, str] = {"]
for k in KEYS:
    lines.append(f"    {k!r}: {ckb[k]!r},")
lines.append("}")
lines.append("")
OUT.write_text("\n".join(lines), encoding="utf-8")
print(f"Wrote {len(KEYS)} entries")
