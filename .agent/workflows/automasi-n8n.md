# automasi-n8n.md

## Dokumentasi Workflow Otomasi Bank → n8n → Laravel

### Tujuan
Mengotomatisasi pencatatan transaksi dari email notifikasi bank ke sistem keuangan Laravel.

---

## Arsitektur Workflow

```text
Gmail Trigger
↓
Get Message
↓
Switch (berdasarkan sender)
├── BCA Parser
└── Mandiri Parser
↓
HTTP Request (Webhook Laravel)
↓
Laravel AutomationController
↓
Database Transactions
```

---

## Gmail Trigger

Filter Gmail:

```text
newer_than:30d from:(cba@bca.co.id OR noreply.livin@bankmandiri.co.id)
```

Fungsi:
- hanya menangkap email bank
- menghindari email promosi

---

## Switch Node

Value to check:

```javascript
{{$json.from.text.toLowerCase()}}
```

Rule:

### Output 0 (BCA)

Operator:

```text
contains
```

Value:

```text
cba@bca.co.id
```

---

### Output 1 (Mandiri)

Operator:

```text
contains
```

Value:

```text
noreply.livin@bankmandiri.co.id
```

---

## Parser Mandiri (Code Node)

```javascript
const html = $json.html || "";

const merchantMatch = html.match(
  /<p[^>]*>Penerima<\/p>\s*<h4[^>]*>(.*?)<\/h4>/i
);
const merchant = merchantMatch ? merchantMatch[1].trim() : null;

const dateMatch = html.match(
  /Tanggal<\/td>\s*<td[^>]*>(.*?)<\/td>/i
);
const date = dateMatch ? dateMatch[1].trim() : null;

const timeMatch = html.match(
  /Jam<\/td>\s*<td[^>]*>(.*?)<\/td>/i
);
const time = timeMatch ? timeMatch[1].trim() : null;

const amountMatch = html.match(
  /Jumlah Transfer<\/td>\s*<td[^>]*>(.*?)<\/td>/i
);
const amountRaw = amountMatch ? amountMatch[1].trim() : null;

const refMatch = html.match(
  /No\.\s*Referensi<\/td>\s*<td[^>]*>(.*?)<\/td>/i
);
const reference = refMatch ? refMatch[1].trim() : null;

const sourceMatch = html.match(
  /<p[^>]*>Rekening Sumber<\/p>[\s\S]*?<p[^>]*>(\*+\d+)<\/p>/i
);
const sourceAccount = sourceMatch ? sourceMatch[1].trim() : null;

let amount = null;

if (amountRaw) {
  const clean = amountRaw
    .replace(/Rp/g, "")
    .replace(/\./g, "")
    .replace(/,00/g, "")
    .trim();

  amount = parseInt(clean);
}

const datetime = (date && time)
  ? `${date} ${time}`
  : null;

return [{
  status: "Successful",
  date: datetime,
  type: "expense",
  merchant,
  amount,
  source_account: sourceAccount,
  reference,
  external_id: reference,
  fee: 0,
  raw_amount: amountRaw,
  bank: "MANDIRI"
}];
```

---

## HTTP Request Node

Method:

```text
POST
```

URL:

```text
http://192.168.1.50:8080/api/automation/webhook
```

Body:

```javascript
{{$json}}
```

---

## Laravel Route

File:

```text
routes/api.php
```

Route:

```php
Route::post('/automation/webhook', [AutomationController::class, 'handleWebhook']);
```

---

## AutomationController Logic

Mapping account berdasarkan bank:

- BCA → account name like BCA
- MANDIRI → account name like MANDIRI

Catatan:
Saat ini belum menggunakan account_number.

Rekomendasi upgrade:
Tambahkan account_number untuk mapping lebih presisi.

---

## Anti Duplicate

Gunakan:

```php
Transaction::firstOrCreate()
```

Key:

```text
external_id
```

Source:

```text
reference number email bank
```

---

## Checklist Debugging

### Jika Trigger Error
- cek Gmail OAuth reconnect

### Jika Switch Error
- cek sender email
- cek filter Gmail

### Jika Parser Error
- cek struktur HTML email bank

### Jika HTTP Error
- cek route Laravel
- cek endpoint URL

### Jika Account Not Found
- cek mapping account di database

---

## Improvement Berikutnya

- tambah parser BNI
- tambah parser Jago
- tambah parser OVO
- tambah parser ShopeePay
- account_number mapping
- category auto-detection
- AI categorization
- dashboard audit automation

---

Status: Production Ready (BCA + Mandiri)
