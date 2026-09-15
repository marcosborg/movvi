# Web support fixes — 15 September 2026

## TKT-000016 — corrected balance carried into later weeks

Financial Statements now updates the selected closing balance and reconciles the opening/closing balances of subsequent existing weeks, chronologically and in one transaction. Each later week's net movement (closing minus opening), weekly earnings and manual status remain intact. Re-saving a previously corrected balance also repairs stale carry-forwards; repeating the operation is idempotent. The endpoint enforces the Admin restriction already present in the form, validates the balance record and accepts numeric amounts without thousands separators.

Existing production balances are not rewritten during deployment. To apply a correction already made, select that original week and save the intended balance again. This recalculates later carry-forwards, including intervening net payments/adjustments, without revalidating earnings.

## TKT-000014 — Via Verde and duplicate vehicle plates

The assignment service now considers all vehicle records with the normalized plate and finds the usage covering the passage timestamp. It only assigns when exactly one usage matches; overlapping usages remain unresolved. Historical soft-deleted vehicles remain searchable.

Read-only local verification of the five BX-53-OL passages billed in the week starting 2026-09-07 resolves all of them to vehicle 53, usage 619, driver 81 (total €3.14). Previously vehicle 41, a soft-deleted duplicate, was selected and produced `no_usage_match`.

The fix applies on import or when a passage is saved again. Existing production passages are not bulk reassigned: the client explicitly reported a manual €3.14 adjustment for week 37, so reassignment/revalidation must be coordinated with removal of that adjustment to prevent charging twice.

## Pending clarification

- TKT-000008: confirmed company-paid charging and commission rates; requested exact first week (37 or 38) and whether manual refunds already exist.
- TKT-000015: requested weekly/driver scope, start week and partial-week treatment of the 2,000 km allowance.
- TKT-000012: requested Conta Azul import sample and the affected expense-registration page/user.
- TKT-000013 concerns APP and was not handled.

## Validation

19 focused tests / 53 assertions: balance corrections (zero, old zero, repeated saves, chronological ordering, negative/positive amounts, payments, rollback, endpoint authorization/validation), duplicate plate assignment (historical records, conflicting usages, deleted usages), existing assignment and batch validation regressions. SQLite memory database; no production test writes. Local browser balance update returned success and persisted zero; local QA balance data restored from its pre-test backup.
