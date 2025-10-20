# Fish Books — Laravel Implementation Guide

This document is a developer-ready blueprint for implementing the Fish Books fishing crew management and payout calculation system in Laravel.

It maps the key features from the original Next.js + Prisma app to Laravel concepts (migrations, Eloquent models, controllers, services, routes, policies, and jobs). The implementation details below are intentionally explicit about calculations and formatting rules so that results match the original system.

---

## Table of contents

1. Introduction & assumptions
2. Database structure (migrations)
3. Eloquent models & relationships
4. Authentication & roles
5. Functional modules & workflows
6. Routes & controllers (API contract)
7. Services & the payout algorithm
8. Logging & audit

---

## 1. Introduction & assumptions

- Target: Laravel 10+, PHP 8.1+.
- Persistence: Mysql for production; Mysql allowed for local development.

Assumptions:
- Two roles: OWNER and MANAGER.
- Currency: MVR.
- Always format money as: `12,500.00 MVR` and dates as `DD/MM/YYYY` in the UI.

---

## 2. Database structure (migrations)

Note: Use `decimal(14,4)` for monetary fields where sums may grow; consider storing cents as integers for exact math if you prefer.

Suggested migrations (core tables):

- `users`
  - id
  - name
  - email (unique)
  - password
  - role (enum: OWNER, MANAGER)
  - phone
  - timestamps

- `crew_members`
  - id
  - name
  - local_name (nullable)
  - active (boolean, default true)
  - bank_name (nullable)
  - bank_account_number (nullable)
  - created_by (foreign users)
  - timestamps

- `vessels`
  - id
  - name (nullable)
  - registration_no (nullable)
  - home_island (nullable)
  - notes text nullable
  - is_active (boolean, default true)
  - created_by (foreign users)
  - timestamps

- `trips`
  - id
  - vessel_id (foreign vessels)
  - date (date)
  - status (enum: ONGOING, CLOSED)
  - total_sales (decimal(14,2), nullable)
  - balance (decimal(14,2), nullable)
  - net_total (decimal(14,2), nullable)
  - owner_share (decimal(14,2), nullable)
  - crew_share (decimal(14,2), nullable)
  - notes text nullable
  - created_by (users.id)
  - closed_at datetime nullable
  - timestamps

- `fish_types`
  - id
  - name
  - default_rate_per_kilo decimal(10,2)
  - rate_effective_from date nullable
  - rate_effective_to date nullable
  - created_by (users.id)
  - timestamps

- `fish_purchases`
  - id
  - trip_id
  - fish_type_id
  - kilos decimal(10,2)
  - rate_per_kilo decimal(10,2)
  - amount decimal(14,2)
  - created_by (users.id)
  - timestamps

- `expenses`
  - id
  - trip_id (foreign trips)
  - amount decimal(14,2)
  - description text
  - status enum (PENDING, APPROVED, REJECTED)
  - type string
  - approved_by nullable
  - approved_at nullable
  - timestamps

- `bills`
    - id
    - trip_id (foreign trips)
    - bill_type enum (TODAY_SALES, PREVIOUS_DAY_SALES) — use enum from schema
    - bill_no string
    - vendor string nullable
    - description text
    - amount decimal(14,2)
    - bill_date date / DateTime
    - payment_status enum (PAID, UNPAID, PARTIAL)
    - payment_method string nullable
    - notes text nullable
    - created_at / updated_at (timestamps)

- `bill_line_items`
  - id
  - bill_id (foreign bills)
  - fish_type_id (foreign fish_types)
  - quantity decimal(10,2)
  - price_per_kilo decimal(10,2)
  - line_total decimal(14,2)
  - created_by
  - timestamps

- `weekly_sheets`
  - id
  - description
  - amount decimal(14,2)
  - timestamps

- `trip_assignments`
  - id
  - trip_id
  - crew_member_id
  - role enum (BAITING, FISHING, CHUMMER, DIVING, HELPER, SPECIAL)
  - helper_ratio decimal(5,2) nullable
  - kilos_assigned decimal(10,2) nullable
  - created_by
  - timestamps

- `weekly_sheets`
  - id
  - week_start date
  - week_end date
  - status enum (DRAFT, FINALIZED)
  - total_sales decimal(14,2)
  - total_expenses decimal(14,2)
  - processed_at datetime nullable
  - created_by
  - timestamps

- `weekly_expenses`
    - id (bigIncrements)
    - weekly_sheet_id (foreignId -> weekly_sheets.id)
    - category (string)
    - amount (decimal(14,2))
    - description (text, nullable)
    - distributed_amount (decimal(14,2), default 0)
    - created_by
    - created_at / updated_at (timestamps)

- `crew_credits`
    - id (bigIncrements)
    - weekly_sheet_id (foreignId -> weekly_sheets.id)
    - crew_member_id (foreignId -> crew_members.id)
    - amount (decimal(14,2))
    - description (text, nullable)
    - credit_date (datetime, default now)
    - created_by
    - created_at / updated_at (timestamps)

- `weekly_payouts`
  - id
  - weekly_sheet_id
  - crew_member_id
  - amount decimal(14,2)
  - is_paid boolean default false
  - paid_at datetime nullable
  - created_by
  - timestamps

Indexes key fields to improve performance.

Default seeds:
- UserSeeder
    - Creates two accounts:
        - owner@hushiyaaru.com — role: OWNER — password: owner123
        - manager@hushiyaaru.com — role: MANAGER — password: manager123
    - Ensure passwords are hashed and roles are set.

- FishTypeSeeder
    - Creates fish types and a current rate record (effectiveFrom = today, effectiveTo = 2099-12-31):
        - Damaged Fish — ratePerKilo: 8.00
        - Proper Fish — ratePerKilo: 16.00
        - Quality Fish — ratePerKilo: 17.00
        - Other — ratePerKilo: 10.00
    - Persist both the fish type and an associated active rate entry so UI logic can pick the current rate.

- CrewMemberSeeder
    - Seed the following crew members (is_active = true) with contact and banking fields:
        - Ahmed Ali — contact: +960 777-1234 — idCardNo: A123456 — bank: Bank of Maldives — account: 1001234567 — holder: Ahmed Ali
        - Ibrahim Hassan — +960 777-2345 — A234567 — Maldivian Heritage Bank — 2001234567 — Ibrahim Hassan
        - Mohamed Waheed — +960 777-3456 — A345678 — Bank of Maldives — 1002234567 — Mohamed Waheed
        - Ali Rasheed — +960 777-4567 — A456789 — State Bank of India — 3001234567 — Ali Rasheed
        - Hassan Moosa — +960 777-5678 — A567890 — Maldivian Heritage Bank — 2002234567 — Hassan Moosa
        - Waheed Ibrahim — +960 777-6789 — A678901 — Bank of Maldives — 1003234567 — Waheed Ibrahim
        - Rasheed Ahmed — +960 777-7890 — A789012 — State Bank of India — 3002234567 — Rasheed Ahmed
        - Moosa Ali — +960 777-8901 — A890123 — Bank of Maldives — 1004234567 — Moosa Ali
        - Ismail Naeem — +960 777-9012 — A901234 — Maldivian Heritage Bank — 2003234567 — Ismail Naeem
        - Naeem Ismail — +960 777-0123 — A012345 — State Bank of India — 3003234567 — Naeem Ismail

Notes
- Implement these as Laravel seeders: UserSeeder, FishTypeSeeder, CrewMemberSeeder (or a DatabaseSeeder that calls them).
- For fish rates, store a separate rate record (rate_per_kilo, effective_from, effective_to, is_active) rather than embedding rate only on the fish_type row.
- If seeding finds existing records, update or skip gracefully to avoid duplicates.
- Use environment-safe defaults for seeded passwords in development only and document the test credentials.
- Run via: php artisan db:seed (or call individual seeders as needed).

---

## 3. Eloquent models & relationships

- User: hasMany(Trip), hasMany(Expense)
- CrewMember: hasMany(TripAssignment), hasMany(WeeklyPayout)
- Vessels: hasMany(Trip)
- Trip: hasMany(FishPurchase), hasMany(Expense), hasMany(Bill), hasMany(TripAssignment)
- FishType: hasMany(FishPurchase)
- FishPurchase: belongsTo(Trip), belongsTo(FishType)
- Expense: belongsTo(User), optional belongsTo(Trip)
- Bill: belongsTo(Trip), hasMany(BillLineItem)
- TripAssignment: belongsTo(Trip), belongsTo(CrewMember)
- WeeklySheet: hasMany(WeeklyPayout), hasMany(WeeklyExpense), hasMany(CrewCredit)
- WeeklyPayout: belongsTo(WeeklySheet), belongsTo(CrewMember)

---

## 4. Authentication & roles

- Install Breeze (Blade or Inertia) and Sanctum: use Breeze for web scaffolding.
- Add `role` to `users` table; seed OWNER and MANAGER.
- Simple Role Middleware:
  - `RoleMiddleware` checks `auth()->user()->role` against required roles.
  - Use in routes: `->middleware(['auth:sanctum','role:OWNER'])`

Policies and gates:
- Owner: can do everything.
- Manager: can update trip details once created, will be finalized by owner. can add trip expenses, approved by owner.

## 5. Functional modules & workflows

Mirror the production-ready flows from the Next.js build so that functionality remains identical after porting to Laravel. Each module lists key actions, fields, validation rules, authorisation notes, and downstream effects on calculations or reporting.

### Crew member management
- Create, edit, archive, and reactivate crew with fields: `full_name`, `local_name`, `phone`, `id_card_no`, `bank_name`, `bank_account_number`, `bank_account_holder`, `notes`, `active`.
- OWNER can archive or reactivate members; MANAGER can create or edit but not change `active` once a member has historic payouts.
- Enforce unique `id_card_no` and normalised phone numbers; return validation errors with field-level messages.
- Provide filters for active status and text search (`name`, `local_name`, `phone`). Always load related banking info for payout exports.
- Log status and banking changes in `crew_member_audits` (JSON payload of previous vs new values) for compliance reviews.

### Vessel management
- CRUD for vessels with fields: `name`, `registration_no`, `home_island`, `notes`, `is_active` (soft delete instead of hard delete to preserve trip history).
- Only OWNER can create, edit, and deactivate/activate.
- Enforce unique `name` and `registration_no` while active. Provide a lightweight overview screen showing last trip date per vessel.

### Trip lifecycle
- Trip statuses: `DRAFT` (wizard in progress), `ONGOING` (active trip), `CLOSED` (locked). Use enum casting on the model.
- Persist wizard progress after every step via `trip_wizard_states` table keyed by trip id and step. Auto-clean when trip closes.
- Closing a trip stamps `closed_at`, locks mutable fields, and triggers payout recalculation + weekly sheet rollup.

#### Wizard step 1: Basics
- Fields: `vessel_id`, `fishing_date`, `departure_time` (nullable), `return_time` (nullable), `remarks`, `weather_notes`.
- Validation: vessel must exist and be active; fishing date cannot exceed today when closing same day; remarks <= 500 chars.
- Default status to `DRAFT`; set `created_by` to the authenticated user.

#### Wizard step 2: Crew & roles
- Present active crew list grouped by role speciality. Require at least one Diver or Fishing role.
- Role assignment: `BAITING`, `FISHING`, `CHUMMER`, `DIVING`, `HELPER`, `SPECIAL`. Prevent duplicate role per member per trip; enforce exclusive diver per member.
- `helper_ratio` allowed for `SPECIAL` roles (range 0.1 - 2.0, default 1.0). Store as decimal(5,2).
- Automatically record baseline kilos (8 kg per active member at 16 MVR/kg) for summary display.

#### Wizard step 3: Sales & bills
- Capture bill header: `bill_type`, `bill_no`, `vendor`, `bill_date`, `payment_status`, `notes`.
- Nested `bill_line_items`: `fish_type_id`, `quantity` (kg), `rate_per_kilo`, `line_total`. Auto-sum to bill `amount`.
- Enforce line items for `TODAY_SALES` and `PREVIOUS_DAY_SALES` bills; optional for other bill types.

#### Wizard step 4: Fish purchases
- Fields: `fish_type_id`, `kilos`, `rate_per_kilo`, `amount`, `notes`.
- Default `rate_per_kilo` from current effective `fish_type_rate` (fall back to 16.00 MVR for Proper Fish).
- When `amount` differs from computed `kilos * rate`, require `override_reason` and persist both values for audit.

#### Wizard step 5: Trip expenses
- Inline management of trip-scoped expenses with fields: `category`, `amount`, `description`, `status` (defaults `PENDING`).
- MANAGER may create/update pending expenses; OWNER approves or rejects, stamping `approved_by` and `approved_at`.
- Pending expenses remain visible in summaries but are excluded from calculations until approved.

#### Wizard step 6: Review & finalize
- Display read-only breakdown: revenue, purchases, crew baseline value, expenses, distribution waterfall, per-member units.
- Allow navigation back to earlier steps prior to submission; prevent finalisation if required data missing (no crew, no bills, etc.).
- OWNER finalises: set status `ONGOING` or `CLOSED`, trigger `TripCalculationService`, snapshot JSON payload for audit, dispatch domain event `TripFinalized`.

### Trip updates post-wizard
- ONGOING trips permit additional bills, purchases, expenses; each change re-runs `TripCalculationService` and persists recalculated snapshot.
- Closing sets status `CLOSED`, locks editing, but OWNER may adjust expense statuses; recalculations append to trip audit log.
- OWNER can reopen closed trips (status back to `ONGOING`) after providing reason; log event and notify stakeholders.

### Expense management (trip-level)
- REST actions: index (filter by `status`, `trip_id`), store, update, approve, reject, soft delete.
- Approval path stores approver meta (`approved_by`, `approved_at`) and raises `ExpenseApproved` event.
- Use policies so creators may edit pending expenses while OWNER retains override capabilities.

### Weekly expense management
- Weekly expenses live under weekly sheets. Fields: `category`, `amount`, `description`, `status` (PENDING/APPROVED), `attachment_path`.
- OWNER approves and locks entries. `approved` expenses feed into weekly allocation shared with trips.
- Provide UI to distribute portions manually if business rules change; keep `distributed_amount` for tracking.

### Crew credits
- Credits represent advances deducted from payouts. Fields: `crew_member_id`, `weekly_sheet_id`, `amount`, `credit_date`, `description`.
- Credits cannot be negative; reversing requires a separate positive adjustment.
- Editing locked once the weekly sheet is finalised; record audit entries for compliance.

### Fish types & rate history
- Manage fish types and `fish_type_rates` (effective windows) via dedicated CRUD interfaces.
- Validate non-overlapping effective ranges per fish type. Allow scheduling future rates without altering historical records.
- Trip wizard uses the rate effective on `fishing_date`; fallback to default Proper Fish rate of 16.00 MVR when no match.

### Weekly sheets
- Capture `week_start`, `week_end`, optional `label`, and related trip ids.
- Status flow: `DRAFT` -> `READY_FOR_APPROVAL` -> `FINALIZED` -> `PAID` (optional). OWNER controls transitions beyond draft.
- Finalising triggers `WeeklyPayoutService`, locks associated trips/expenses/credits, and stores a financial snapshot.
- Weekly sheets expose aggregated metrics (sales, expenses, maintenance, owner share, crew share) and per-member payout rows.

### Payout processing
- Generate `weekly_payouts` per crew member: fields `base_amount`, `credit_deduction`, `final_amount`, `is_paid`, `paid_at`, `payment_reference`.
- Provide bulk mark-as-paid, CSV export, and printable statements with formatted amounts (`formatAmount`) and dates (`formatDate`).
- Store payload snapshot referencing source trips, rates, credits to ensure reproducible audits.

### Reporting & dashboards
- Owner dashboard: weekly totals, outstanding approvals (expenses, credits), closed vs open trips.
- Manager dashboard: upcoming trips, pending expenses, crew availability.
- Reporting endpoints: revenue trends, expense categories, crew earnings history (filtered by date range, vessel, crew member).

## 6. Routes & controllers (API contract)

Use API endpoints for operations. 

---

## 7. Services & the payout algorithm

A trip-level calculation service and a weekly payout aggregation service that both rely on shared helpers and produce identical fields. Keeping the sequence and naming aligned avoids divergence between the two stacks.

### TripCalculationService

**Inputs**
- Bills grouped by `bill_type` (`TODAY_SALES`, `PREVIOUS_DAY_SALES`) with optional line items.
- Fish purchases (kilos, resolved rate, total amount fallback).
- Expenses (only `APPROVED` amounts are deductible).
- Crew assignments per trip (roles + diving roles).
- Fish type catalog to resolve the Proper Fish baseline rate (16 MVR/kg).
- Optional weekly expense allocation injected per trip.

**Steps**
1. `today_sales = sum(bills where bill_type=TODAY_SALES)`.
2. `previous_sales = sum(bills where bill_type=PREVIOUS_DAY_SALES)`.
3. `bill_total = today_sales + previous_sales`.
4. `purchase_total = sum(purchase.totalAmount || purchase.kilos * purchase.ratePerKilo)`.
5. Compute crew baseline kilos: each baiting role = 4 kg, each fishing role = 4 kg (chummer/diver do not add kilos). Multiply by Proper Fish rate (default/fallback 16). `crew_kilos_value = crew_kilos * rate`.
6. `total_sales = bill_total + purchase_total + crew_kilos_value`.
7. `approved_expenses = sum(expense.amount where status=APPROVED)`.
8. `total_expenses = approved_expenses + weekly_expense_share` (weekly share already approved; pending expenses remain informational only).
9. `balance = total_sales - total_expenses`.
10. `vessel_maintenance = balance * 0.10` (ten percent before any splits).
11. `net_total = balance - vessel_maintenance`.
12. `owner_share = net_total / 3` (owner always receives one third; no diving deductions).
13. `crew_share = net_total * (2/3)`.
14. Calculate role weights per assignment:
  - Baiting, Fishing, Chummer: 0.5 units each.
  - Diver: 1.0 unit.
  - Helper: 0.5 unit.
  - (If Laravel introduces `SPECIAL` with `helper_ratio`, multiply helper unit by that ratio.)
15. `per_unit_value = crew_share / sum(weight_units)` (guard against divide-by-zero).
16. For every crew member, sum the units they hold across the trip and multiply by `per_unit_value` to derive `member.total_amount`.
17. Surface the following output structure for UI parity:
  - Revenue: today sales, previous day sales, bill total, purchase total, crew kilos value, total sales.
  - Expenses: approved, pending, weekly share, total expenses.
  - Distribution: balance, vessel maintenance, net total, owner share, crew share.
  - Crew data: total crew kilos, per-member base payout, staff units, per-unit value.

### WeeklyPayoutService

**Inputs**
- All closed trips flagged as `is_fishing_day` for the target week.
- Weekly expenses (only approved amounts matter).
- Crew credits (loan deductions) per member.
- Fish type rates effective for each trip date.

**Steps**
1. `weekly_expense_amount = sum(weekly_expenses APPROVED)`.
2. Count fishing days, defaulting to 1 if none to avoid division by zero.
3. `weekly_expense_share = weekly_expense_amount / fishing_day_count`.
4. For each trip, call `TripCalculationService` injecting `weekly_expense_share` so that the deduction is amortized across fishing days only.
5. Reduce across all trip results by summing the financial fields (sales, expenses, maintenance, owner share, crew share, etc.).
6. Aggregate `weightedShares.memberShares` by crew ID to produce cumulative weekly totals. Reuse the staff-unit math from the trip service rather than recomputing it differently.
7. For payout rows: 
   - `basePayout = aggregated_member.total_amount`.
   - `creditDeduction = sum(crew_credits for member)`.
   - `finalPayout = max(0, basePayout - creditDeduction)`.
8. Persist totals back onto the weekly sheet: `totalWeeklyPayout = crew_share`, `ownerShare`, `crewShare`, `status = READY_FOR_PAYOUT` (or equivalent state machine).

**Outputs**
- Weekly rollup of revenue, expenses, maintenance, net value, owner share, crew share.
- Per-member payouts with credits deducted and payment status flags.
- Roll-forward figures for UI (per-day expense allocation, role counts, staff units) to keep dashboards aligned with the Next.js behaviour.

### Validation Checklist
- Preserve the order: Balance → 10% Maintenance → Net → Owner 1/3 → Crew 2/3.
- Always include crew baseline kilos at the Proper Fish rate even if no purchases exist.
- Expenses marked `PENDING` must never reduce the balance but remain visible.
- Diving payouts come from the crew pool—not an owner deduction.
- Guard divisions with zero staff units (return zero payouts instead of crashing).
- Use centralized helpers for formatting (`formatAmount`, `formatDate`) when serializing results.
- Weekly credits subtract from final payouts but never create negatives (clamp at zero).
- Weekly exp share must only hit fishing days; rest days receive `0`.

## 8. Logging & audit
- Implement soft deletes on financial tables (trips, bills, expenses, credits, payouts) to support recovery while maintaining history.
- Persist structured audit logs for financial mutations (status changes, overrides, payouts) with actor id, before/after payload, and timestamp.
- Expose read-only audit endpoints for compliance reviews; restrict to OWNER role.
