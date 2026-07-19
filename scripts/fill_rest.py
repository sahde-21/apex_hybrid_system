#!/usr/bin/env python3
"""Fill ckb_rest.json with complete Sorani translations."""
import json
from pathlib import Path

OUT = Path(__file__).with_name("ckb_rest.json")
UNDO = "ناتوانرێت ئەم کردارە بگەڕێنرێتەوە."

REST: dict[str, str] = {
    "2FA recovery codes": "کۆدەکانی گەڕانەوەی 2FA",
    "A new verification link has been sent to the email address you provided during registration.": "بەستەری پشتڕاستکردنەوەی نوێ نێردرا بۆ ئیمەیڵەکەت کە لە کاتی تۆمارکردندا دابینت کرد.",
    "A new verification link has been sent to your email address.": "بەستەری پشتڕاستکردنەوەی نوێ نێردرا بۆ ناونیشانی ئیمەیڵەکەت.",
    "Action": "کردار",
    "Add a new customer or supplier": "زیادکردنی کڕیار یان دابینکەرێکی نوێ",
    "Add a new employee record": "زیادکردنی تۆmاری کارmەندێki نوێ",
    "Add a new product to your inventory": "زیadkrdni bérhémekî nwwé bo kôgaket",
    "Add a new storage location": "ziadkrdni shwéni kôgay nwwé",
    "Add a new tax rate": "ziadkrdni rêžey bajekî nwwé",
    "Add a passkey to sign in without a password": "klili chûnajûrwwé ziad bke bo chûnajûrwwé bébé wshey nehênî",
    "Add passkey": "ziadkrdni klili chûnajûrwwé",
    "Added :time": "ziad kra :time",
    "Address": "nawishan",
    "Adjustment date": "berwari rêkkhsstn",
    "All actions": "hamoo krdarekan",
    "All report types": "hamoo jôrekani rápôrt",
    "All types": "hamoo jôrekan",
    "Allocated Amount": "bri terxankraw",
    "Appearance": "rûkar",
    "Appearance settings": "rêkkhsstnekanî rûkar",
    "Are you sure you want to delete this CRM interaction? This action cannot be undone.": f"dlnîyayt le sŕinewey em karlêkî CRM? {UNDO}",
    "Are you sure you want to delete this bill? This action cannot be undone.": f"dlnîyayt le sŕinewey em pسوڵey dabinér? {UNDO}",
    "Are you sure you want to delete this contact? This action cannot be undone.": f"dlnîyayt le sŕinewey em peywéndiyé? {UNDO}",
    "Are you sure you want to delete this employee? This action cannot be undone.": f"dlnîyayt le sŕinewey em karméndé? {UNDO}",
    "Are you sure you want to delete this expense? This action cannot be undone.": f"dlnîyayt le sŕinewey em khrjiyé? {UNDO}",
    "Are you sure you want to delete this financial report? This action cannot be undone.": f"dlnîyayt le sŕinewey em rápôrti darayî? {UNDO}",
    "Are you sure you want to delete this inventory adjustment? This action cannot be undone.": f"dlnîyayt le sŕinewey em rêkkhsstni kôga? {UNDO}",
    "Are you sure you want to delete this invoice? This action cannot be undone.": f"dlnîyayt le sŕinewey em pسوڵeyé? {UNDO}",
    "Are you sure you want to delete this journal entry? This action cannot be undone.": f"dlnîyayt le sŕinewey em tômari rôžané? {UNDO}",
    "Are you sure you want to delete this payment? This action cannot be undone.": f"dlnîyayt le sŕinewey em parédané? {UNDO}",
    "Are you sure you want to delete this payroll? This action cannot be undone.": f"dlnîyayt le sŕinewey em mooché? {UNDO}",
    "Are you sure you want to delete this product?": "dlnîyayt le sŕinewey em bérhémé?",
    "Are you sure you want to delete this product? This action cannot be undone.": f"dlnîyayt le sŕinewey em bérhémé? {UNDO}",
    "Are you sure you want to delete this purchase order? This action cannot be undone.": f"dlnîyayt le sŕinewey em férmani krîn? {UNDO}",
    "Are you sure you want to delete this quotation? This action cannot be undone.": f"dlnîyayt le sŕinewey em pêshniaré? {UNDO}",
    "Are you sure you want to delete this sale order? This action cannot be undone.": f"dlnîyayt le sŕinewey em férmani frôshtn? {UNDO}",
    "Are you sure you want to delete this tax rate? This action cannot be undone.": f"dlnîyayt le sŕinewey em rêžey baj? {UNDO}",
    "Are you sure you want to delete this warehouse? This action cannot be undone.": f"dlnîyayt le sŕinewey em kôgayé? {UNDO}",
    "Are you sure you want to delete your account?": "dlnîyayt le sŕinewey héžmaraket?",
    "Are you sure you want to remove the passkey ": "dlnîyayt le labrdni klili chûnajûrwwé ",
    "Are you sure? This action cannot be undone.": f"dlnîyayt? {UNDO}",
}

OUT.write_text(json.dumps(REST, ensure_ascii=False, indent=2), encoding="utf-8")
print(f"Wrote {len(REST)} entries (partial — extend script)")
