# Financial Event Philosophy - vFOS 2.0 Evolution

This document outlines the architectural transition of the vFOS Financial Engine from a basic transaction logger to a robust, event-driven, single-source-of-truth financial ledger.

---

## 1. Current Architecture
The existing transaction engine operates on a cash-flow logging model. Every transaction is classified as one of four types:
* `income`
* `expense`
* `transfer`
* `withdrawal`

Account balances are updated instantly via Eloquent observers using a simple summation hook:
$$\text{Balance} = \text{Initial Balance} + \text{Income} + \text{Incoming Transfers} - \text{Expenses} - \text{Outgoing Transfers} - \text{Withdrawals}$$

The Profit & Loss statement is built by querying transaction items grouped by categories, excluding transactions with the type `transfer`.

---

## 2. Problem Statement
The legacy model conflates **cash flow movements** with **economic value changes**. Because any ledger entry that is not a transfer must be classified as either income or expense, the following distortions occur:
1. **Asset Swaps treated as Expenses:** Purchasing stock or buying physical assets (like gold or office equipment) reduces cash. The current engine logs this as an `expense`, reducing the Profit & Loss statement, despite the user merely converting one asset (Cash) into another (Investment/Equipment) with zero change in net worth.
2. **Liability Reductions treated as Expenses:** Repaying the principal on a debt reduces cash and is categorized as an `expense`, which distorts the P/L. Only the interest portion of a debt payment represents true value consumption (expense).
3. **Receivable Collections treated as Income:** Collecting money owed from a borrower is logged as `income`, artificially inflating the P/L. It is actually a balance sheet shift (Receivable $\rightarrow$ Cash).
4. **Double Entry Overhead:** Sub-ledgers (Investments, Debts, Receivables) are decoupled from the main transactions ledger. The user must manually input a transaction (Cash out) and then manually update the asset/liability values in a separate form.

---

## 3. Financial Event Philosophy
The new philosophy shifts from recording "What happened to cash?" to **"How did economic value change?"**. Every financial transaction is classified as a specific **Financial Event** that determines how value moves between accounts and whether it affects the Profit & Loss statement or only the Wealth Statement (Balance Sheet).

* Only events that **create** or **consume** economic value are registered in the Profit & Loss statement.
* Events that merely **swap** asset types or **reduce/create** liabilities affect the Wealth Statement only.

---

## 4. Terminology

* **Value Creation:** An event that generates new economic value (e.g. earning a salary). Affects P/L (Income) and increases Net Worth.
* **Value Consumption:** An event that consumes economic value (e.g. paying for food or rent). Affects P/L (Expense) and decreases Net Worth.
* **Asset Swap:** Exchanging one asset type for another (e.g. transferring money between own bank accounts). Neutral to P/L and Net Worth.
* **Asset Acquisition:** Purchasing a long-term appreciating or depreciating asset (e.g. buying stock, purchasing gold). Neutral to P/L; shifts cash to investment assets on the Wealth Statement.
* **Asset Disposal:** Selling an asset for cash. Neutral to P/L; realizes principal recovery.
* **Liability Creation:** Incurring a debt (e.g. taking a bank loan). Cash increases while a corresponding liability increases. Neutral to P/L.
* **Liability Reduction:** Repaying debt principal. Cash decreases while liability decreases. Neutral to P/L.
* **Valuation Adjustment:** Non-transactional adjustment to reflect market fluctuations or depreciation of owned assets. Affects Wealth Statement (and optionally P/L for realized adjustments).
* **Internal Transfer:** Moving money between internal liquid accounts (e.g. Cash to Bank). Neutral to P/L.

---

## 5. Examples

| Event / Action | Legacy Classification | Financial Event Type | P/L Impact | Wealth Statement Impact |
| :--- | :--- | :--- | :---: | :--- |
| **Salary Credit** | `income` | `VALUE_CREATION` | Increase | Cash $\uparrow$, Net Worth $\uparrow$ |
| **QRIS Restaurant Payment** | `expense` | `VALUE_CONSUMPTION` | Decrease | Cash $\downarrow$, Net Worth $\downarrow$ |
| **Transfer Mandiri $\rightarrow$ BCA** | `transfer` | `INTERNAL_TRANSFER` | None | Cash swap (neutral) |
| **Topup GoPay from Bank** | `transfer` | `INTERNAL_TRANSFER` | None | Cash swap (neutral) |
| **Investment Purchase** (Buy Stock) | `expense` | `ASSET_ACQUISITION` | None | Cash $\downarrow$, Investment $\uparrow$ |
| **Debt Payment (Principal)** | `expense` | `LIABILITY_REDUCTION` | None | Cash $\downarrow$, Liability $\downarrow$ |
| **Debt Payment (Interest)** | `expense` | `VALUE_CONSUMPTION` | Decrease | Cash $\downarrow$, Net Worth $\downarrow$ |
| **Loan Collection (Receivable)** | `income` | `ASSET_DISPOSAL` | None | Cash $\uparrow$, Receivable $\downarrow$ |
| **Gold Price Appreciation** | None (manual update) | `VALUATION_ADJUSTMENT` | None | Gold Asset $\uparrow$, Net Worth $\uparrow$ |

---

## 6. Migration Strategy
To evolve vFOS safely without breaking the production application:
1. **Phase 1: Foundation (Current):** Introduce domain enums, stubs, and Transaction Context DTOs. Zero integration or modifications are made to existing models/controllers.
2. **Phase 2: Classification Integration:** Hook `FinancialEventClassifier` into the transaction store/update pipelines. Classify transactions on-the-fly and save the `financial_event_type` to a new column on the transactions table. All legacy features continue using the legacy type.
3. **Phase 3: Dual-Reporting & Split Ledgers:** Generate experimental P/L and Balance Sheets using the classified events alongside the old reports to verify mathematical correctness.
4. **Phase 4: Direct Accounting:** deprecate the old transaction types entirely, making `FinancialEventType` the single source of truth for the entire database.
