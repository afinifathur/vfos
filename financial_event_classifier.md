# Financial Event Classifier Specification (Phase 2)

This document details the responsibilities, classification mappings, decision trees, and current limitations of the Phase 2 Financial Event Classifier.

---

## 1. Classifier Responsibility
The `FinancialEventClassifier` is a stateless, pure domain utility whose sole purpose is to translate transaction contexts (containing legacy type, amounts, categories, and account references) into the new vFOS 2.0 `FinancialEventType` structure. 

In Phase 2, this is a **read-only instrumentation tool** with:
* No database write hooks.
* No changes to transaction properties or existing tables.
* No integration into controllers, models, or import pipelines.

---

## 2. Decision Tree & Mapping Specification

```
      [Legacy Transaction Type]
                 │
      ┌──────────┼──────────┬──────────┐
      ▼          ▼          ▼          ▼
   income     expense    transfer  withdrawal
      │          │          │          │
      ▼          ▼          └────┬─────┘
    VALUE_     VALUE_            │
   CREATION  CONSUMPTION         ▼
                             INTERNAL_
                             TRANSFER
```

### Deterministic Mappings:
1. `income` $\rightarrow$ `VALUE_CREATION`
2. `expense` $\rightarrow$ `VALUE_CONSUMPTION`
3. `transfer` $\rightarrow$ `INTERNAL_TRANSFER`
4. `withdrawal` $\rightarrow$ `INTERNAL_TRANSFER`
5. Any other type $\rightarrow$ `UNKNOWN`

---

## 3. Contextual Examples

| Legacy Type | Amount | Account | Categories | Classified Event |
| :--- | :---: | :--- | :--- | :--- |
| `income` | Rp15.000.000 | Mandiri Payroll | `Income` | `VALUE_CREATION` |
| `expense` | Rp75.000 | BCA Debit | `Food/makan` | `VALUE_CONSUMPTION` |
| `transfer` | Rp500.000 | BCA Debit | `Transfer` | `INTERNAL_TRANSFER` |
| `withdrawal` | Rp100.000 | ATM Cash | `Cash withdrawal` | `INTERNAL_TRANSFER` |

---

## 4. Current Limitations (Phase 2)

Because Phase 2 is a read-only instrumentation layer designed for backward compatibility, the classifier does **not** yet evaluate sub-ledger states or category definitions:
* **Debt Repayment:** Currently mapped to `VALUE_CONSUMPTION` (legacy `expense`), even though principal repayments represent `LIABILITY_REDUCTION`.
* **Receivable Collection:** Currently mapped to `VALUE_CREATION` (legacy `income`), even though they represent `ASSET_DISPOSAL` (principal recovery).
* **Investment Purchase:** Currently mapped to `VALUE_CONSUMPTION` (legacy `expense`), even though buying stock or gold represents `ASSET_ACQUISITION`.
* **Mortgage Splits:** Not yet split. Entire payments are classified as `VALUE_CONSUMPTION`.

---

## 5. Future Phase Integrations
Future phases will extend `FinancialEventClassifier` logic to evaluate the `TransactionClassificationContext` metadata (such as `category_names` and `category_types` arrays, account parameters, and linked models) to perform advanced classifications:
* **Debt Integration:** Link to the `Debt` module to classify debt payments.
* **Receivable Integration:** Link to the `Receivable` module to classify repayments.
* **Investment Integration:** Evaluate stock and gold transaction categories to record `ASSET_ACQUISITION` or `ASSET_DISPOSAL`.
