# Implementation Checklist

## Phase 0 · Discovery
- [ ] Review `Requirements.md` and capture open questions for the product owner.
- [ ] Define primary user personas, core use cases, and acceptance criteria.
- [ ] Audit competitor apps for UX patterns and note inspiration references.
- [ ] Decide hosting stack, deployment target, and required service integrations.

## Phase 1 · Visual Direction
- [x] Establish brand attributes, tone, and visual design principles for the app.
  - Ocean/fishing theme with glassmorphism accents
- [x] Choose color palette, typography scale, spacing tokens, and elevation rules.
  - Deep ocean blues (#0A2540, #1B4965, #2C6E8C), accent (#5FA8D3), coral (#FF6B6B), seaweed (#2A9D8F)
  - OKLCH format for better color uniformity
- [x] Produce mood board and low-fidelity wireframes for critical flows.
- [x] Iterate high-fidelity mockups for desktop and mobile breakpoints.
  - Login page, Dashboard, Crew Members list/form
- [x] Validate mockups against accessibility contrast ratios (WCAG AA or better).
- [x] Document approved layouts, components, and interaction patterns in a design brief.
  - `.github/copilot-instructions.md` with full design system
- [x] Obtain stakeholder sign-off on the visual direction and design deliverables.
- [x] Create copilot-instruction files to ensure the coding agent follows the UI standards.

## Phase 2 · Environment & Tooling
- [x] Install PHP 8.2+, Composer, Node.js 18+, npm, and a SQL database that matches project needs.
- [x] Copy `.env.example` to `.env`, set app name, URL, timezone, and mail settings.
- [x] Configure database credentials and storage paths in `.env`.
- [x] Run `php artisan key:generate` to set the application key.
- [x] Install backend dependencies with `composer install`.
- [x] Install frontend dependencies with `npm install`.
- [x] Verify local development tooling (IDE, debug bar, linting, Pint, PHPUnit, Pest if used).

## Phase 3 · Data & Domain Modeling
- [x] Review existing migrations; extend or add new ones for all Fish Books entities.
  - [x] Added `vessel_id`, `description` to weekly_sheets
  - [x] Added `weekly_sheet_id`, `day_of_week`, `is_fishing_day` to trips
  - [x] Week-first workflow implemented (create week → auto-generate 6 trips)
- [x] Create or update Eloquent models with relationships, casts, and scopes.
  - User (with role), CrewMember, Vessel, FishType, FishTypeRate
  - Trip (with weeklySheet relationship), FishPurchase, Expense, Bill, BillLineItem, TripAssignment
  - WeeklySheet (with vessel and trips relationships), WeeklyExpense, CrewCredit, WeeklyPayout
- [x] Implement database seeders/factories for core entities and sample data.
  - UserSeeder: owner@hushiyaaru.com / manager@hushiyaaru.com (password: owner123 / manager123)
  - FishTypeSeeder: 4 fish types with rates
  - CrewMemberSeeder: 10 crew members with banking details
- [x] Run `php artisan migrate --seed` to validate schema and seeding.
- [x] Document entity diagrams or relationship maps for team reference.
  - `docs/MODAL_TOAST_SYSTEM.md` - Modal and toast usage patterns
  - `docs/WEEKLY_SHEETS_IMPLEMENTATION.md` - Weekly sheets workflow documentation

## Phase 4 · Backend Services
- [x] Configure authentication/authorization (e.g., Laravel Sanctum, policies, gates).
  - RoleMiddleware for OWNER/MANAGER roles
  - Session-based authentication with Sanctum stateful domains
  - Web AuthController with login/logout
  - API AuthController for token-based (future mobile app)
- [x] Implement core business logic services
  - TripCalculationService: Full payout algorithm (10% maintenance, 1/3 owner, 2/3 crew with role weights)
  - WeeklyPayoutService: Weekly aggregation, expense distribution, credit deductions
- [x] Scaffold controllers, form requests, and resources for required API endpoints.
  - [x] CrewMemberController (CRUD with role-based access, restore capability)
  - [x] VesselController (CRUD with OWNER-only access, getNextWeekStart endpoint)
  - [x] FishTypeController (CRUD with OWNER-only access, auto rate tracking)
  - [x] TripController (index with filters, trip wizard - 6 steps pending)
  - [x] WeeklySheetController (index, store with auto-trip generation, weekly management pending)
- [x] Implement validation rules and error handling aligned with requirements.
  - StoreTripRequest, UpdateTripRequest with role-based authorization
  - StoreWeeklySheetRequest, UpdateWeeklySheetRequest (OWNER only)
  - Inline validation for wizard steps (crew assignment, bills, purchases, expenses, weekly expenses/credits)
- [ ] Add services/jobs for background tasks (notifications, imports, etc.).
- [ ] Wire up events/listeners for domain actions if applicable.
- [ ] Write feature tests covering critical scenarios and edge cases.
- [x] Expose API documentation (OpenAPI, Postman collection, or Markdown).
  - `docs/API.md` with complete endpoint documentation

## Phase 5 · Frontend Experience
- [x] Decide on SPA vs. SSR approach (Inertia.js, Livewire, or Blade-only).
  - **Inertia.js + Vue 3 + TypeScript** chosen for SPA-like experience with SSR benefits
- [x] Build foundational layout, navigation, and shared UI components defined in the design brief.
  - [x] MainLayout with collapsible sidebar navigation
  - [x] Role-based menu items (OWNER sees all, MANAGER sees limited)
  - [x] User dropdown with avatar and logout
  - [x] Ocean theme with glassmorphism effects
  - [x] Centralized Modal system (Modal.vue, ConfirmModal.vue)
  - [x] Centralized Toast system (ToastContainer.vue, useToast composable)
  - [x] Custom Datepicker component (DD/MM/YYYY display, YYYY-MM-DD storage)
- [x] Implement authentication pages
  - [x] Login page with ocean gradient background and glassmorphic card
  - [x] Session-based auth with Inertia forms
  - [x] CSRF token handling
  - [x] Proper redirect flow (guest → login, authenticated → dashboard)
- [x] Implement Dashboard
  - [x] Welcome header with user name
  - [x] Stats cards (Crew Members, Active Trips, Vessels, Pending Payouts)
  - [x] Quick Actions section
  - [x] Getting Started guide
  - [x] Role-based visibility (OWNER sees vessels stat)
- [x] Implement Crew Members module
  - [x] Index page with search, filters, pagination
  - [x] Full-width layout with glassmorphic cards
  - [x] Delete confirmation modal
  - [x] Form page (create/edit) with 3-section layout
  - [x] Validation with error display
  - [x] Active/Inactive toggle
- [x] Implement Vessels module (OWNER only)
  - [x] Index page with CRUD operations, search, filters
  - [x] Form page with vessel details (name, registration, capacity)
  - [x] Active/Inactive status toggle
- [x] Implement Fish Types module (OWNER only)
  - [x] Index page with current rates and rate history count
  - [x] Form page with rate management and historical tracking
  - [x] Currency formatting for rates (MVR format)
  - [x] Info card explaining rate history preservation
  - [x] Migrated to centralized modal/toast system
- [x] Implement Trips module (partially)
  - [x] Index page with filters (status, vessel, date range)
  - [x] Trip list with status badges, fishing day indicators
  - [x] Delete functionality (DRAFT only, OWNER only)
  - [x] Migrated to centralized modal/toast system
  - [ ] Trip detail view (Show page)
  - [ ] 6-step wizard (basics, crew, bills, purchases, expenses, finalize)
  - [ ] Trip calculations display
- [x] Implement Weekly Sheets module (partially, OWNER only)
  - [x] Index page with filters (status, vessel, date range)
  - [x] Week list with trip counts, fishing day counts, sales totals
  - [x] Migrated to centralized modal/toast system
  - [x] Create page with vessel selection and auto-trip generation
  - [x] Smart week date suggestion (next Saturday after last week)
  - [x] Trip preview (6 days: Sat-Thu) and pay day indicator
  - [x] Proper DD/MM/YYYY date formatting throughout
  - [ ] Detail view (Show page) with trip management hub
  - [ ] Expense and credit management
  - [ ] Calculate and finalize capabilities
  - [ ] Reopen functionality
  - [ ] Mark as paid functionality
- [x] Integrate API calls via Axios/fetch and handle loading/error states.
  - Session-based auth with `credentials: 'include'`
  - CSRF token in all requests
  - Loading spinners and error handling
- [x] Apply design system/branding, responsive styles, and accessibility checks.
  - Ocean color palette with OKLCH format
  - Tailwind 4.0 with CSS-based theme configuration
  - Responsive grid layouts (mobile-first)
  - Focus states and keyboard navigation
- [ ] Add client-side tests (Vitest/Jest/Cypress) for critical flows.

## Phase 6 · Observability & Quality
- [ ] Configure logging channels, error reporting, and monitoring hooks.
- [ ] Set up automated CI for linting, tests, and static analysis.
- [ ] Measure performance (Laravel Telescope, debug bar) and tune queries/cache.
- [ ] Validate security (CSRF, rate limiting, password rules, secrets management).

## Phase 7 · Delivery & Maintenance
- [ ] Prepare build artifacts with `npm run build` and `php artisan config:cache`.
- [ ] Create deployment pipeline (GitHub Actions, Forge, Vapor, etc.).
- [ ] Run smoke tests in staging; verify database migrations and seeded data.
- [ ] Draft release notes and user onboarding materials.
- [ ] Schedule post-launch review to capture feedback and plan iterations.

---

## Recent Updates (October 20, 2025)

### ✅ Centralized UI System
- Created Modal.vue base component with backdrop, animations, ESC key support
- Created ConfirmModal.vue with 3 variants (danger/warning/info) and loading states
- Created ToastContainer.vue with slide-in animations and color-coded types
- Created useToast() composable for global toast state management
- Migrated all index pages (Vessels, CrewMembers, FishTypes) to use new system
- Reduced code duplication by 117 lines total

### ✅ Weekly Sheets Module (Week-First Workflow)
- Implemented week-first workflow: Create week → auto-generate 6 trips (Sat-Thu)
- Friday is pay day (no trip created)
- One week per vessel at a time (enforced)
- Week auto-advances from last finalized week
- Created Index page with filters, status badges, trip counts
- Created Create page with smart date suggestions and trip preview
- Added VesselController.getNextWeekStart() endpoint
- Updated WeeklySheetController.store() to auto-generate 6 trips with day_of_week codes
- Fixed model relationships: WeeklySheet.trips(), Trip.weeklySheet(), WeeklySheet.vessel()
- Added missing fillable fields: vessel_id, description, weekly_sheet_id, day_of_week, is_fishing_day
- Proper DD/MM/YYYY date formatting throughout (Datepicker component)

### ✅ Trips Module (Initial)
- Created Index page with filters (status, vessel, date range)
- Table shows: date, day of week, vessel, week period, status, fishing day indicator, sales
- Status badges color-coded (DRAFT/ONGOING/CLOSED)
- Delete functionality for DRAFT trips (OWNER only)
- Migrated to centralized modal/toast system

### 🔧 Technical Improvements
- Fixed WeeklySheetController.index() to use withCount() with subqueries
- Fixed Trip model to include weekly_sheet_id, day_of_week, is_fishing_day
- Fixed controller to use 'date' column instead of 'trip_date'
- Added defensive API response handling in frontend
- Proper TypeScript interfaces matching Laravel response format
- All dates use DD/MM/YYYY display format, YYYY-MM-DD storage format

### 📚 Documentation
- Created docs/MODAL_TOAST_SYSTEM.md - Complete modal/toast API documentation
- Created docs/MODAL_TOAST_MIGRATION.md - Migration guide with checklist
- Created docs/WEEKLY_SHEETS_IMPLEMENTATION.md - Weekly sheets workflow documentation
- Updated .github/copilot-instructions.md with modal/toast examples and date handling patterns

### ✅ Heroicons Migration (October 20, 2025)
- **Completed**: 100% - All 50+ hardcoded SVG icons replaced with Heroicons
- **Files Migrated**: 18 total (MainLayout, Dashboard, CrewMembers, Vessels, FishTypes, Trips, WeeklySheets, ConfirmModal)
- **Icons**: PlusIcon, MagnifyingGlassIcon, PencilIcon, TrashIcon, EyeIcon, ChevronLeftIcon, UserGroupIcon, TruckIcon, ScaleIcon, CalendarIcon, DocumentTextIcon, InformationCircleIcon, BanknotesIcon, MapIcon, LightBulbIcon, UsersIcon, ClipboardDocumentListIcon, CreditCardIcon, ExclamationTriangleIcon, Bars3Icon, ChevronDownIcon, ArrowRightOnRectangleIcon, BellIcon
- **Benefits**: Maintainable, consistent, tree-shakeable, better DX
- **Documentation**:
  - Created docs/HEROICONS_MIGRATION.md (400+ lines comprehensive guide)
  - Created docs/HEROICONS_MIGRATION_COMPLETE.md (completion summary)
  - Updated .github/copilot-instructions.md with Heroicons-only policy (60+ lines)
- **Patterns Established**: Dynamic component rendering, consistent sizing (h-5 w-5 buttons, h-6 w-6 headers, h-12 w-12 empty states), semantic color classes
- **Verification**: 0 compilation errors, 100% design system compliance

### 🚀 Next Steps
1. Build Weekly Sheet Show page (trip management hub)
2. Implement Trip wizard (6 steps for filling trip details)
3. Add weekly expenses and crew credits management
4. Implement weekly calculations and finalization
5. Add mark as paid functionality

```
