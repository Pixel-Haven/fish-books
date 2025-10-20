# Fish Books API Documentation

Base URL: `http://localhost/api`

## Authentication

All endpoints except `/login` require authentication via Laravel Sanctum tokens.

### Login
**POST** `/login`

Request:
```json
{
  "email": "owner@hushiyaaru.com",
  "password": "owner123"
}
```

Response:
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "Owner",
    "email": "owner@hushiyaaru.com",
    "role": "OWNER"
  }
}
```

### Logout
**POST** `/logout`

Headers: `Authorization: Bearer {token}`

### Get Current User
**GET** `/user`

Headers: `Authorization: Bearer {token}`

---

## Crew Members

### List Crew Members
**GET** `/crew-members`

Query Parameters:
- `search` - Search by name or ID card
- `active` - Filter by active status (1/0)
- `per_page` - Results per page (default: 15)

### Create Crew Member
**POST** `/crew-members`

Authorization: OWNER or MANAGER

Request:
```json
{
  "name": "John Doe",
  "id_card_no": "A123456",
  "bank_name": "BML",
  "bank_account_no": "7730001234567",
  "phone": "7777777",
  "notes": "Experienced diver"
}
```

### Get Crew Member
**GET** `/crew-members/{id}`

### Update Crew Member
**PUT** `/crew-members/{id}`

Request: Same as create

Note: Only OWNER can change `active` status

### Delete Crew Member
**DELETE** `/crew-members/{id}`

Authorization: OWNER only

Note: Soft delete. Cannot delete if crew has payout records.

### Restore Crew Member
**POST** `/crew-members/{id}/restore`

Authorization: OWNER only

---

## Vessels

Authorization: OWNER only for all endpoints

### List Vessels
**GET** `/vessels`

Query Parameters:
- `search` - Search by name or registration
- `active` - Filter by active status (1/0)
- `per_page` - Results per page (default: 15)

### Create Vessel
**POST** `/vessels`

Request:
```json
{
  "name": "Vessel Alpha",
  "registration_no": "V001",
  "capacity": "50 tons",
  "notes": "Large fishing vessel"
}
```

### Get Vessel
**GET** `/vessels/{id}`

### Update Vessel
**PUT** `/vessels/{id}`

Request: Same as create

### Delete Vessel
**DELETE** `/vessels/{id}`

Note: Soft delete. Cannot delete if vessel has trip history.

---

## Fish Types

Authorization: OWNER only for all endpoints

### List Fish Types
**GET** `/fish-types`

Query Parameters:
- `active` - Filter by active status (1/0)

### Create Fish Type
**POST** `/fish-types`

Request:
```json
{
  "name": "Quality Fish",
  "default_rate_per_kilo": 17.00
}
```

Note: Automatically creates initial FishTypeRate record

### Get Fish Type
**GET** `/fish-types/{id}`

### Update Fish Type
**PUT** `/fish-types/{id}`

Request: Same as create

Note: Creates new FishTypeRate if `default_rate_per_kilo` changes

### Delete Fish Type
**DELETE** `/fish-types/{id}`

Note: Soft delete. Cannot delete if used in transactions.

---

## Trips

### List Trips
**GET** `/trips`

Query Parameters:
- `status` - Filter by status (DRAFT, ONGOING, CLOSED)
- `vessel_id` - Filter by vessel
- `from_date` - Start of date range
- `to_date` - End of date range
- `sort_by` - Sort field (default: date)
- `sort_direction` - Sort direction (asc/desc, default: desc)
- `per_page` - Results per page (default: 15)

### Create Trip (Step 1: Basics)
**POST** `/trips`

Authorization: OWNER or MANAGER

Request:
```json
{
  "vessel_id": 1,
  "date": "2025-01-15",
  "notes": "Morning trip"
}
```

### Get Trip
**GET** `/trips/{id}`

Response includes full details and calculations:
```json
{
  "trip": {...},
  "calculations": {
    "revenue": {...},
    "expenses": {...},
    "distribution": {...},
    "crew": [...]
  }
}
```

### Update Trip
**PUT** `/trips/{id}`

Authorization: 
- DRAFT/ONGOING: OWNER or MANAGER
- CLOSED: OWNER only

Request: Same as create, plus `status` field

### Delete Trip
**DELETE** `/trips/{id}`

Authorization: OWNER only

Note: Cannot delete CLOSED trips

### Assign Crew (Step 2)
**POST** `/trips/{id}/assign-crew`

Request:
```json
{
  "assignments": [
    {
      "crew_member_id": 1,
      "role": "DIVING",
      "helper_ratio": 1.0
    },
    {
      "crew_member_id": 2,
      "role": "BAITING"
    }
  ]
}
```

Roles: BAITING, FISHING, CHUMMER, DIVING, HELPER, SPECIAL

### Add Bills (Step 3)
**POST** `/trips/{id}/add-bills`

Request:
```json
{
  "bills": [
    {
      "bill_type": "TODAY_SALES",
      "bill_no": "B001",
      "vendor": "Hulhumale Market",
      "bill_date": "2025-01-15",
      "amount": 5000.00,
      "payment_status": "PAID",
      "line_items": [
        {
          "fish_type_id": 1,
          "quantity": 100.5,
          "price_per_kilo": 16.00
        }
      ]
    }
  ]
}
```

Bill Types: TODAY_SALES, PREVIOUS_DAY_SALES, OTHER

### Add Fish Purchases (Step 4)
**POST** `/trips/{id}/add-purchases`

Request:
```json
{
  "purchases": [
    {
      "fish_type_id": 2,
      "kilos": 50.0,
      "rate_per_kilo": 15.00,
      "amount": 750.00
    }
  ]
}
```

### Add Expenses (Step 5)
**POST** `/trips/{id}/add-expenses`

Request:
```json
{
  "expenses": [
    {
      "amount": 200.00,
      "description": "Fuel",
      "type": "FUEL"
    }
  ]
}
```

### Finalize Trip (Step 6)
**POST** `/trips/{id}/finalize`

Authorization: OWNER only

Validates crew assignments and calculates totals. Changes status to ONGOING.

### Close Trip
**POST** `/trips/{id}/close`

Authorization: OWNER only

Recalculates totals and sets status to CLOSED.

### Reopen Trip
**POST** `/trips/{id}/reopen`

Authorization: OWNER only

Changes status from CLOSED back to ONGOING.

---

## Weekly Sheets

Authorization: OWNER only for all endpoints

### List Weekly Sheets
**GET** `/weekly-sheets`

Query Parameters:
- `status` - Filter by status (DRAFT, FINALIZED)
- `from_date` - Start of date range
- `to_date` - End of date range
- `sort_by` - Sort field (default: from_date)
- `sort_direction` - Sort direction (asc/desc, default: desc)
- `per_page` - Results per page (default: 15)

### Create Weekly Sheet
**POST** `/weekly-sheets`

Request:
```json
{
  "from_date": "2025-01-13",
  "to_date": "2025-01-19",
  "notes": "Week 3"
}
```

Note: Date range must not overlap with existing sheets

### Get Weekly Sheet
**GET** `/weekly-sheets/{id}`

Response includes expenses, credits, and payouts.

### Update Weekly Sheet
**PUT** `/weekly-sheets/{id}`

Request: Same as create

### Delete Weekly Sheet
**DELETE** `/weekly-sheets/{id}`

Note: Cannot delete FINALIZED sheets

### Add Weekly Expenses
**POST** `/weekly-sheets/{id}/add-expenses`

Request:
```json
{
  "expenses": [
    {
      "amount": 500.00,
      "description": "Office rent",
      "type": "RENT"
    }
  ]
}
```

### Add Crew Credits
**POST** `/weekly-sheets/{id}/add-credits`

Request:
```json
{
  "credits": [
    {
      "crew_member_id": 1,
      "amount": 100.00,
      "description": "Cash advance"
    }
  ]
}
```

### Calculate Payouts
**GET** `/weekly-sheets/{id}/calculate`

Returns payout calculations without saving.

Response:
```json
{
  "calculations": {
    "total_revenue": 50000.00,
    "total_expenses": 5000.00,
    "total_crew_share": 30000.00,
    "total_owner_share": 15000.00,
    "crew_payouts": [
      {
        "crew_member_id": 1,
        "crew_member_name": "John Doe",
        "gross_amount": 5000.00,
        "credit_deduction": 100.00,
        "net_amount": 4900.00
      }
    ]
  },
  "weekly_sheet": {...}
}
```

### Finalize Weekly Sheet
**POST** `/weekly-sheets/{id}/finalize`

Calculates payouts and creates WeeklyPayout records. Changes status to FINALIZED.

### Reopen Weekly Sheet
**POST** `/weekly-sheets/{id}/reopen`

Deletes payout records and changes status back to DRAFT.

---

## Payout Calculation Algorithm

### Trip Payout Formula

1. **Total Sales** = Today Sales + Previous Sales + Fish Purchases + Crew Baseline Kilos Value
   - Crew Baseline: 4kg per BAITING/FISHING role × Proper Fish rate

2. **Balance** = Total Sales

3. **Maintenance (10%)** = Balance × 0.10

4. **Net Total** = Balance - Maintenance - Approved Expenses

5. **Owner Share (1/3)** = Net Total × 0.333333

6. **Crew Share (2/3)** = Net Total - Owner Share

7. **Crew Distribution**:
   - Calculate total units based on roles:
     - DIVING: 1.0 unit
     - BAITING, FISHING, CHUMMER, HELPER: 0.5 unit each
     - SPECIAL: 0.5 × helper_ratio
   - Per Unit Amount = Crew Share / Total Units
   - Individual Payout = Per Unit Amount × Crew Member's Units

### Weekly Payout Process

1. Aggregate all CLOSED trips in date range
2. Calculate weekly expenses per fishing day
3. Apply crew credits/deductions
4. Prevent negative payouts (minimum 0.00)

---

## Test Credentials

- **Owner**: owner@hushiyaaru.com / owner123
- **Manager**: manager@hushiyaaru.com / manager123

---

## Error Responses

All errors follow this format:

```json
{
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

HTTP Status Codes:
- `200` - Success
- `201` - Created
- `401` - Unauthorized (missing/invalid token)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `422` - Validation Error

---

## Notes

- All monetary values are in MVR (Maldivian Rufiyaa)
- Dates are in `YYYY-MM-DD` format
- All timestamps are in UTC
- Soft deletes are used for crew members and vessels
- Trip calculations are performed in real-time on show/update
- Weekly payouts are only created when sheet is finalized
