# Finance Review Inbox

## Displayed Fields

- `Template`
- `Description Preview`
- `Counterparty`
- `Amount`
- `Transaction Date`
- `Status`
- `Confidence`
- `Warnings`
- `Internal Transfer Candidate` badge when present in metadata

All review fields are derived from parsed transaction data or read-only metadata.
No raw email body content is used for the review preview.

## Preview Generation

- The inbox preview uses `TransactionDescriptionBuilder`.
- The same builder is used later during import.
- This keeps the inbox preview identical to the eventual transaction description.
- If parsing does not produce a transaction, the preview falls back to a review warning state.

## Confidence Rules

- `HIGH`
  - Amount exists
  - Transaction date exists
  - Merchant and counterparty are both present
- `MEDIUM`
  - Amount and transaction date exist
  - Either merchant or counterparty is missing
- `LOW`
  - Amount is missing or transaction date is missing

## Warning Rules

- Show `Description is empty` when the generated preview is blank.
- Show `Merchant missing` when the parsed transaction merchant is empty.
- Show `Amount missing` when the parsed amount is missing or invalid.
- Show `Transaction date missing` when the parsed date is missing.

Warnings do not block import.

## Notes

- Status labels are presented as user-friendly review states:
  - `Ready to Import`
  - `Imported`
  - `Ignored`
  - `Needs Review`
- The inbox review screen is read-only with respect to transaction behavior.
- No schema changes were made.

