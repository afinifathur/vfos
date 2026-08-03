# Financial Decision Engine - vFOS 2.0 Architectural Freeze

This document describes the design and flow of the vFOS 2.0 Financial Decision Engine.

---

## 1. Domain Pipeline

```
  [Legacy Transaction]
           │
           ▼
[TransactionClassificationContext]
           │
           ▼
  [FinancialEventClassifier]   ─── (Answers: "What happened?")
           │
           ▼
 [FinancialEventResult / Reason]
           │
           ▼
[FinancialEventDecisionEngine] ─── (Answers: "What should the system do?")
           │
           ▼
   [FinancialEventDecision]    ─── (Holds: Execution Flags, Target Ledgers, Warnings)
```

---

## 2. Separation of Responsibilities

To achieve clean, maintainable accounting domains, the pipeline separates concerns into three distinct stages:

### A. Classification ("What happened?")
Managed by `FinancialEventClassifier`. It takes the legacy context (type, categories, amount in integer Rupiah, and notes) and maps it to a semantic domain category (`FinancialEventType`) and diagnostic reason (`FinancialEventReason`). It is entirely stateless and does not know anything about downstream ledger modifications or sub-ledgers.

### B. Decision ("What should the system do?")
Managed by `FinancialEventDecisionEngine`. It takes the classification outcome and maps it to a structured plan (`FinancialEventDecision`). It determines:
* Which general ledger accounts should update (`updateAccounts`).
* Whether to include the event in Profit & Loss calculations (`includeProfitLoss`).
* Whether to route the transaction to sub-ledgers (`updateDebt`, `updateReceivable`, `updateInvestment`).
* Where to route the transaction (`targetLedgerType`, e.g., `DEBT`, `RECEIVABLE`, `INVESTMENT`).
* If the user needs to select a specific sub-ledger target account (`requiresUserSelection`).
* Diagnostic warnings (e.g. module missing errors) and decision confidence.

### C. Execution (Database Side Effects)
**Execution is NOT performed in this phase.** The Decision Engine remains 100% read-only.
In future phases, the Execution Coordinator will accept the `FinancialEventDecision` and coordinate the physical database writes (creating sub-ledger records, updating account balances, and inserting ledger journal entries).

---

## 3. Why Decision Never Performs Writes

1. **Testability**: Because the classification and decisioning steps are pure functions of transaction context, they can be tested exhaustively without database seeds, mocks, or side-effects.
2. **Pre-flight Validation**: The application can run "pre-flight" checks on transactions (e.g. during imports) to show the user what will happen before they confirm.
3. **Safety**: Separation of planning from execution prevents partial writes or orphaned records if a sub-ledger update fails.

---

## 4. Why Execution is Replaceable

By decoupling *planning* from *writing*, the execution layer becomes a pluggable driver:
* In a local development environment, we might use database transactions.
* In high-performance settings, we can swap the execution driver to write asynchronously to a queue or log, without altering the classification or decision engines.

---

## 5. Future Ledger Integrations (Phase 4 - 9 Roadmap)

The decision flags and target ledger fields prepare vFOS for the upcoming phases:

### Phase 4: Debt Integration (`updateDebt = true`)
When active, the execution coordinator will detect `updateDebt` and pass the transaction details to the `DebtLedgerService`. The `requiresUserSelection` flag will prompt the user in the UI to choose which specific `Debt` account (e.g., mortgage, auto loan) to reduce or create.

### Phase 5: Receivable Integration (`updateReceivable = true`)
Routes collections or lendings to the `ReceivableLedgerService` to automatically decrease or increase outstanding receivables when a transaction categorized under loan repayments is recorded.

### Phase 6: Investment Integration (`updateInvestment = true`)
Routes asset purchases/disposals/valuation adjustments to the `InvestmentLedgerService` to track stock portfolio quantities, average buying prices, and realized/unrealized capital gains.

### Phase 7: Finance Import Intelligence
Uses the `DecisionConfidence` enum (`HIGH`, `MEDIUM`, `LOW`) to determine automation boundaries. `HIGH` confidence events can be imported and executed automatically. `LOW` or `MEDIUM` confidence events are flagged for user review.

### Phase 8 & 9: Profit & Loss and Wealth Statement Refactor
Replaces the legacy controllers to generate statements by querying the event-driven ledger tables instead of parsing the legacy transaction category fields.
