# Transaction Import Quality

## Supported Templates

- `BCA_QRIS`
- `BCA_TRANSFER`
- `BCA_VIRTUAL_ACCOUNT`
- `BCA_CASH_DEPOSIT`
- `BCA_CASH_WITHDRAW`
- `MANDIRI_TRANSFER`
- `MANDIRI_BIFAST`
- `MANDIRI_QRIS`
- `MANDIRI_TOPUP`

## Description Generated

- `BCA_QRIS` -> `QRIS {Merchant}`
- `BCA_TRANSFER` -> remarks first, then `Transfer to/from {Counterparty}`, then `Transfer Internal`
- `BCA_VIRTUAL_ACCOUNT` -> company/product labels from structured fields only
- `BCA_CASH_DEPOSIT` -> `Cash Deposit`
- `BCA_CASH_WITHDRAW` -> `Cash Withdrawal`
- `MANDIRI_TRANSFER` -> remarks first, then `Transfer to/from {Counterparty}`, then `Transfer Internal`
- `MANDIRI_BIFAST` -> remarks first, then `Transfer to/from {Counterparty}`, then `Transfer Internal`
- `MANDIRI_QRIS` -> `QRIS {Merchant}`
- `MANDIRI_TOPUP` -> `Top Up {Merchant}`

All descriptions are capped at 60 characters and do not use raw email body text.

## Missing Fields

- `BCA_CASH_DEPOSIT`: no merchant/counterparty by design
- `BCA_CASH_WITHDRAW`: no merchant/counterparty by design
- Some `BCA_TRANSFER` and `MANDIRI_TRANSFER` messages may not include a distinct `merchant` value beyond the counterparty
- Some `BCA_VIRTUAL_ACCOUNT` messages may only expose a company field without a separate product field

## Templates Still Needing Improvement

- `BCA_VIRTUAL_ACCOUNT`
- `MANDIRI_TOPUP`

These templates can still benefit from richer structured fields in future message formats, but current output remains concise and structured.

## Known Limitations

- Descriptions depend on the structured fields extracted by the parsers.
- If a bank email omits `keterangan`, the builder falls back to merchant, counterparty, or template defaults.
- The importer records `internal_transfer_candidate` as read-only metadata only; it does not alter transaction type or categorization.
- No database schema changes were made.

