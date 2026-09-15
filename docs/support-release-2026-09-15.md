# Web support fixes — 15 September 2026

## TKT-000016 — corrected balance carried into later weeks

Financial Statements now updates the selected closing balance and reconciles the opening/closing balances of subsequent existing weeks, chronologically and in one transaction. Each later week's net movement (closing minus opening), weekly earnings and manual status remain intact. Re-saving a previously corrected balance also repairs stale carry-forwards; repeating the operation is idempotent. The endpoint enforces the Admin restriction already present in the form, validates the balance record and accepts numeric amounts without thousands separators.

Existing production balances are not rewritten during deployment. To apply a correction already made, select that original week and save the intended balance again. This recalculates later carry-forwards, including intervening net payments/adjustments, without revalidating earnings.

## TKT-000014 — Via Verde and duplicate vehicle plates

The assignment service now considers all vehicle records with the normalized plate and finds the usage covering the passage timestamp. It only assigns when exactly one usage matches; overlapping usages remain unresolved. Historical soft-deleted vehicles remain searchable.

Read-only local verification of the five BX-53-OL passages billed in the week starting 2026-09-07 resolves all of them to vehicle 53, usage 619, driver 81 (total €3.14). Previously vehicle 41, a soft-deleted duplicate, was selected and produced `no_usage_match`.

The fix applies on import or when a passage is saved again. Existing production passages are not bulk reassigned: the client explicitly reported a manual €3.14 adjustment for week 37, so reassignment/revalidation must be coordinated with removal of that adjustment to prevent charging twice.

## Follow-up release after customer replies

- TKT-000016: the validation screenshot was a week without a saved balance. Such weeks now show an explanation and a link to the driver's last saved balance instead of submitting record ID 0. The link also selects the matching month/year. Invalid balance submissions have a readable Portuguese message. Local browser verification: Diogo's week 36 has no record; the link opens week 35 with the existing balance.
- TKT-000008: customer confirmed week 37 refunds are already complete and requested week 38. From 2026-09-14, company 1 pays imported electric charging for contracts with company percentages 50/55/60. These charging costs no longer reduce driver expenses or the driver VAT base and appear as company expense. Other contracts, combustion charges and earlier weeks retain their existing rules. Imported totals remain visible for reconciliation. No past production financial records are changed.
- TKT-000012: custom expense group names can be entered when creating/editing expenses and reused/filterable thereafter. Added authenticated company-scoped, paginated read API with stable source IDs and deletion markers, documented in `vehicle-expenses-api.md`. Carlos's XLS model was inspected read-only. No external Conta Azul postings, credentials or new permissions are created. Carlos still implements destination mapping and idempotency.
- TKT-000014: customer chose to retain the manual week 37 adjustment and use the deployed assignment fix for subsequent imports.

Validation of this follow-up: 25 focused tests / 91 assertions passed, including API authentication, permissions, company isolation, pagination, repeat IDs, deletion filtering, custom group compatibility and prior financial/import regressions. Read-only local report simulation passed for 10 eligible drivers; week 37 source records remained unchanged. New group form rendered successfully in localhost. No database migration required.

## Pending clarification

- TKT-000015: confirmed only cedência, from week 38, default 2,000 km at €0.10/km. Awaiting names for 2,200 km / €0.05 exceptions and partial-week treatment (asked at 10:09). Automatic deductions are not enabled until these rules are supplied.
- TKT-000013 concerns APP and was not handled.

## Validation

19 focused tests / 53 assertions: balance corrections (zero, old zero, repeated saves, chronological ordering, negative/positive amounts, payments, rollback, endpoint authorization/validation), duplicate plate assignment (historical records, conflicting usages, deleted usages), existing assignment and batch validation regressions. SQLite memory database; no production test writes. Local browser balance update returned success and persisted zero; local QA balance data restored from its pre-test backup.
