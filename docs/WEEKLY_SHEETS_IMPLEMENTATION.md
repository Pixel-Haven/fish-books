# Weekly Sheets Module - Implementation Summary

## ✅ Completed (Phase 1)

### **Database Changes**
1. ✅ Migration created: `2025_10_20_144058_add_weekly_sheet_fields_for_workflow.php`
   - Added `vessel_id` to `weekly_sheets` table
   - Added `description` field to `weekly_sheets` table
   - Added `weekly_sheet_id`, `day_of_week`, `is_fishing_day` to `trips` table
   
2. ✅ Cleaned up duplicate migration files
   - Removed pending duplicate migrations
   - All migrations now running cleanly

### **Backend API**

#### **Updated Controllers**

**VesselController** (`app/Http/Controllers/Api/VesselController.php`)
- ✅ Added `getNextWeekStart()` method
  - Returns last week's data for selected vessel
  - Suggests next Saturday start date
  - Returns `null` if no previous weeks (defaults to current Saturday)

**WeeklySheetController** (`app/Http/Controllers/Api/WeeklySheetController.php`)
- ✅ Updated `store()` method to:
  - Validate week_start is Saturday, week_end is Friday
  - Check for existing DRAFT/FINALIZED weeks for same vessel (one week per vessel rule)
  - Auto-generate 6 trips (Saturday through Thursday)
  - Set `day_of_week` for each trip (SAT, SUN, MON, TUE, WED, THU)
  - Default `is_fishing_day = true` for all trips
  
- ✅ Updated `index()` method to:
  - Include vessel relationship
  - Count total trips per week
  - Count closed trips per week
  - Count fishing days per week
  - Support filtering by vessel_id and status

#### **Updated Request Validation**

**StoreWeeklySheetRequest** (`app/Http/Requests/StoreWeeklySheetRequest.php`)
- ✅ Added `vessel_id` (required, must exist)
- ✅ Changed `label` to optional
- ✅ Added `description` (optional, max 500 chars)
- ✅ Kept `week_start` and `week_end` validation

#### **Routes**

**API Routes** (`routes/api.php`)
- ✅ Added `GET /api/vessels/{vessel}/next-week-start` endpoint

**Web Routes** (`routes/web.php`)
- ✅ Updated weekly-sheets routes to use `Create` component instead of `Form`

### **Frontend Pages**

#### **WeeklySheets/Index.vue**
✅ Features:
- List of all weekly sheets with vessel info
- Filters: Status (DRAFT/FINALIZED/PAID), Vessel, Date Range
- Table columns:
  - Week Period (formatted dates)
  - Vessel name
  - Trip counts (total and closed)
  - Fishing days count
  - Total sales (formatted currency)
  - Crew share (formatted currency)
  - Status badge (color-coded)
  - Actions (View, Delete for DRAFT)
- Pagination support
- Search functionality
- Loading states
- Delete with ConfirmModal and toast notifications
- Ocean theme styling with glassmorphism

#### **WeeklySheets/Create.vue**
✅ Features:
- **Vessel Selection:**
  - Dropdown with active vessels only
  - Auto-loads last week data on selection
  
- **Smart Week Suggestion:**
  - Shows last week info if exists
  - Auto-suggests next Saturday after last week
  - Falls back to current/next Saturday if no data
  - Week end auto-set to Friday (6 days after start)
  
- **Trip Preview:**
  - Shows all 6 trips to be created (Sat-Thu)
  - Displays dates for each day
  - Shows Friday as "Pay Day" (no trip)
  
- **Validation:**
  - Week must start on Saturday
  - Week must end on Friday
  - Cannot create duplicate weeks for same vessel
  - Shows clear error messages
  
- **Auto-Trip Generation:**
  - Creates 6 trips automatically on submission
  - Each trip pre-filled with vessel and date
  - Status set to DRAFT
  - All marked as fishing days by default
  
- **UX Features:**
  - Loading states during submission
  - Success toast notification
  - Error toast with details
  - Redirects to weekly sheet detail page on success
  - Ocean theme styling

### **Navigation**
- ✅ Weekly Sheets link added to sidebar (OWNER only)
- ✅ Active state highlighting

---

## 🎯 Workflow Implemented

```
1. OWNER clicks "Create New Week"
   ↓
2. Selects Vessel
   ↓ (Auto-loads last week, suggests next Saturday)
   ↓
3. Confirms week dates (Sat-Fri)
   ↓
4. Clicks "Create Week & Generate Trips"
   ↓
5. Backend:
   - Validates one week per vessel rule
   - Creates weekly_sheet record
   - Auto-generates 6 trips (Sat-Thu)
   - Links trips to weekly sheet
   - Sets day_of_week for each trip
   ↓
6. Redirects to Weekly Sheet detail page
   (Next: MANAGER fills in trip details via wizard)
```

---

## 📊 Database Schema

### **weekly_sheets**
```sql
id
vessel_id           → ADDED (foreign key to vessels)
week_start          (Saturday)
week_end            (Friday)
label               (optional)
description         → ADDED (optional, text)
status              (DRAFT | FINALIZED | PAID)
total_sales
total_expenses
total_weekly_payout
owner_share
crew_share
processed_at
created_by
timestamps
```

### **trips**
```sql
id
vessel_id
weekly_sheet_id     → ADDED (foreign key to weekly_sheets)
date
day_of_week         → ADDED (SAT, SUN, MON, TUE, WED, THU)
is_fishing_day      → ADDED (boolean, default true)
status              (DRAFT | ONGOING | CLOSED)
... (other trip fields)
```

---

## 🔒 Business Rules Enforced

1. ✅ **One Week Per Vessel**: Cannot create multiple DRAFT/FINALIZED weeks for same vessel
2. ✅ **Week Structure**: Must start Saturday, end Friday (6 working days + Friday pay day)
3. ✅ **Auto Trip Generation**: Always creates exactly 6 trips per week
4. ✅ **Smart Date Suggestion**: Follows last week automatically
5. ✅ **OWNER Only**: Only OWNER can create weekly sheets

---

## 🚀 Next Steps (Phase 2)

### **1. Weekly Sheet Detail Page** (`WeeklySheets/Show.vue`)
- Display week summary
- Show all 6 trips with status
- Quick link to edit each trip
- Toggle "is_fishing_day" (OWNER only)
- Add weekly expenses section
- Add crew credits section
- Show calculation preview
- Finalize button

### **2. Trip Wizard** (6 Steps)
- Manager workflow to fill trip details
- Pre-filled with vessel, date, weekly_sheet_id
- Step 1: Basic info
- Step 2: Crew assignments
- Step 3: Bills/Sales
- Step 4: Fish purchases
- Step 5: Expenses
- Step 6: Review & close

### **3. Weekly Finalization**
- Calculate payouts (WeeklyPayoutService)
- Lock all data
- Generate payout report
- Mark as PAID functionality

---

## 📝 Testing Checklist

### **Weekly Sheet Creation**
- [ ] Create week with vessel that has no history → Uses current Saturday
- [ ] Create week with vessel that has history → Uses next Saturday after last week
- [ ] Try creating duplicate week for same vessel → Shows error
- [ ] Week dates auto-set correctly (Sat-Fri)
- [ ] 6 trips created automatically with correct dates
- [ ] Each trip has correct day_of_week (SAT, SUN, etc.)
- [ ] All trips linked to weekly_sheet_id
- [ ] Redirects to detail page after creation

### **Weekly Sheets Index**
- [ ] Shows all weekly sheets
- [ ] Filters work (Status, Vessel, Date Range)
- [ ] Pagination works
- [ ] Delete DRAFT week works
- [ ] Cannot delete FINALIZED/PAID weeks
- [ ] Navigation to detail page works

### **Permissions**
- [ ] OWNER can create weekly sheets
- [ ] MANAGER cannot access create page
- [ ] Sidebar shows Weekly Sheets for OWNER only

---

## 🎨 Design Elements Used

- **Ocean Theme Colors**: Primary blue, accent, success green, destructive coral
- **Glassmorphism**: `.glass-card` utility on all cards
- **Status Badges**: Color-coded (DRAFT=gray, FINALIZED=blue, PAID=green)
- **Currency Formatting**: `12,500.00 MVR` format
- **Date Formatting**: `DD/MM/YYYY` format (e.g., 19/10/2025)
- **Loading States**: Spinner with disabled buttons
- **Toast Notifications**: Success/error feedback
- **ConfirmModal**: Delete confirmations with loading state
- **Responsive**: Mobile-first design with proper breakpoints

---

## 🔧 Technical Decisions

1. **Week Start Auto-Detection**: Uses last week's end date + 1 day for continuity
2. **Trip Day Naming**: Stored as 3-letter codes (SAT, SUN, etc.) for consistency
3. **One Week Rule**: Enforced at database level with validation in controller
4. **Friday Exclusion**: Friday is pay day, no trip created (only 6 trips)
5. **Default Fishing Days**: All trips default to fishing day, OWNER can toggle later
6. **CSRF Protection**: All POST requests include CSRF token
7. **Session Auth**: Uses session-based authentication (not token)

---

## 📚 Files Modified/Created

### **Created**
1. `database/migrations/2025_10_20_144058_add_weekly_sheet_fields_for_workflow.php`
2. `resources/js/pages/WeeklySheets/Index.vue`
3. `resources/js/pages/WeeklySheets/Create.vue`
4. `docs/WEEKLY_SHEETS_IMPLEMENTATION.md`

### **Modified**
1. `routes/api.php` - Added next-week-start endpoint
2. `routes/web.php` - Updated weekly-sheets routes
3. `app/Http/Controllers/Api/VesselController.php` - Added getNextWeekStart method
4. `app/Http/Controllers/Api/WeeklySheetController.php` - Updated store and index methods
5. `app/Http/Requests/StoreWeeklySheetRequest.php` - Updated validation rules

### **Cleaned**
1. Removed duplicate migration files

---

## ✅ Status: Phase 1 Complete

**Weekly Sheet creation workflow is fully functional and ready for testing!**

The system now supports:
- ✅ Creating weekly sheets with auto-trip generation
- ✅ Smart week date suggestions
- ✅ One week per vessel enforcement
- ✅ Proper date validation (Sat-Fri structure)
- ✅ OWNER-only access control
- ✅ Beautiful ocean-themed UI

**Next**: Implement Weekly Sheet Detail page (Show.vue) with trip management hub.
