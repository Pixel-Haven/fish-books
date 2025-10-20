# Fish Books - Design System & Coding Guidelines

## 🎨 Design Philosophy

Fish Books uses an **ocean/fishing theme** with glassmorphism accents. The design evokes the maritime environment with deep blues, seafoam greens, and coral accents, creating a professional yet coastal atmosphere.

## 🌊 Color Palette

### Primary Ocean Colors
```css
--ocean-deep: #0A2540      /* Deep ocean blue - primary buttons, headers */
--ocean-medium: #1B4965    /* Medium ocean blue - secondary elements */
--ocean-light: #2C6E8C     /* Light ocean blue - hover states */
--ocean-accent: #5FA8D3    /* Bright ocean accent - CTAs, links */
--ocean-foam: #CAE9FF      /* Light foam/sky blue - backgrounds, highlights */
```

### Accent Colors
```css
--coral: #FF6B6B           /* Coral red - errors, destructive actions */
--sand: #F4E4C1            /* Sandy beige - neutral accents */
--seafoam: #62B6CB         /* Seafoam green/blue - info states */
--sunset: #FF8C42          /* Sunset orange - warnings */
--seaweed: #2A9D8F         /* Seaweed green - success states */
```

### Semantic Colors (OKLCH format for better color uniformity)
- **Primary**: `oklch(0.35 0.08 230)` - Deep ocean blue
- **Secondary**: `oklch(0.45 0.08 220)` - Medium ocean
- **Accent**: `oklch(0.65 0.1 215)` - Bright accent
- **Success**: `oklch(0.55 0.15 165)` - Seaweed green
- **Warning**: `oklch(0.68 0.18 45)` - Sunset orange
- **Destructive**: `oklch(0.62 0.2 15)` - Coral red
- **Muted**: `oklch(0.94 0.01 220)` - Light muted blue-tinted
- **Border**: `oklch(0.88 0.01 220)` - Subtle blue-tinted border

## 📐 Layout & Spacing

### Sidebar Navigation
- **Width**: 256px (w-64)
- **Background**: Gradient from primary to secondary in header
- **Border**: Right border with sidebar-border color
- **Collapsible**: Full slide animation (300ms ease-in-out)

### Content Area
- **Width**: Full width with horizontal padding (w-full px-6)
- **Form Pages**: Centered with max-w-5xl for better readability
- **Padding**: 24px horizontal (px-6)
- **Background**: Subtle gradient `from-background via-muted/20 to-background`

### Card Components
Use `.glass-card` utility class:
```css
.glass-card {
  @apply bg-white/20 dark:bg-black/95;
  @apply backdrop-blur-lg border border-white/10 dark:border-black/10 shadow-lg;
}
```

## 🎯 Component Patterns

### Buttons

#### Primary Button
```vue
<button class="px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
  Button Text
</button>
```

#### Secondary Button
```vue
<button class="px-4 py-2 bg-secondary hover:bg-secondary/90 text-secondary-foreground font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
  Button Text
</button>
```

#### Destructive Button
```vue
<button class="px-4 py-2 bg-destructive hover:bg-destructive/90 text-destructive-foreground font-medium rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
  Delete
</button>
```

### Input Fields

#### Text Input
```vue
<input 
  type="text"
  class="w-full px-4 py-2 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all"
  placeholder="Enter text..."
/>
```

#### Date Input (Datepicker Component)
**ALWAYS use the custom `<Datepicker>` component for date inputs** - never use native `<input type="date">`.

```vue
<script setup lang="ts">
import Datepicker from '@/components/ui/datepicker.vue';

const form = ref({
  date: '', // Will be in YYYY-MM-DD format
});
</script>

<template>
  <div>
    <label class="block text-sm font-medium text-foreground mb-2">
      Date
    </label>
    <Datepicker
      v-model="form.date"
      placeholder="Select date"
    />
  </div>
</template>
```

**Important Notes:**
- Datepicker automatically uses the global `DEFAULT_DATE_FORMAT` (DD/MM/YYYY) for display
- The v-model value is always in `YYYY-MM-DD` format (ISO standard) for backend compatibility
- Display format: `20/10/2025` (user-friendly)
- Data format: `2025-10-20` (backend-friendly)
- Never use native `<input type="date">` as it uses browser locale settings inconsistently

#### Search Input (with icon)
```vue
<div class="relative">
  <input class="w-full pl-10 pr-4 py-2 border border-input bg-background rounded-lg focus:outline-none focus:ring-2 focus:ring-ring transition-all" />
  <svg class="absolute left-3 top-2.5 h-5 w-5 text-muted-foreground">
    <!-- Search icon path -->
  </svg>
</div>
```

### Cards

#### Data Card (tables, lists)
```vue
<div class="glass-card rounded-xl border border-border/50 shadow-lg overflow-hidden">
  <!-- Content -->
</div>
```

#### Filter/Search Card
```vue
<div class="glass-card rounded-xl p-6 border border-border/50 shadow-sm">
  <!-- Filters -->
</div>
```

### Tables

```vue
<table class="min-w-full divide-y divide-border">
  <thead class="bg-muted/50">
    <tr>
      <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
        Column Name
      </th>
    </tr>
  </thead>
  <tbody class="bg-card divide-y divide-border">
    <tr class="hover:bg-accent/50 transition-colors">
      <td class="px-6 py-4">Content</td>
    </tr>
  </tbody>
</table>
```

### Status Badges

```vue
<!-- Success/Active -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success/10 text-success border border-success/20">
  Active
</span>

<!-- Warning -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning/10 text-warning border border-warning/20">
  Pending
</span>

<!-- Error/Inactive -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-destructive/10 text-destructive border border-destructive/20">
  Closed
</span>

<!-- Neutral/Muted -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-foreground border border-border">
  Draft
</span>
```

### Modals & Toast Notifications

**ALWAYS use the centralized modal and toast system** - never create inline modal HTML.

#### Toast Notifications
```vue
<script setup lang="ts">
import { useToast } from '@/utils/toast';

const toast = useToast();

// Success, error, warning, info
toast.success('Operation successful', 'Details here...');
toast.error('Operation failed', 'Error details...');
toast.warning('Warning', 'Warning message...');
toast.info('Info', 'Information message...');
</script>
```

#### Confirm Modal (Delete/Destructive Actions)
```vue
<script setup lang="ts">
import { ref } from 'vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import { useToast } from '@/utils/toast';

const toast = useToast();
const showDeleteModal = ref(false);
const deleting = ref(false);
const selectedItem = ref<any>(null);

const confirmDelete = (item: any) => {
  selectedItem.value = item;
  showDeleteModal.value = true;
};

const handleDelete = async () => {
  deleting.value = true;
  try {
    await fetch(`/api/items/${selectedItem.value.id}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: { 'X-CSRF-TOKEN': getCsrfToken() },
    });
    
    toast.success('Deleted', 'Item removed successfully.');
    showDeleteModal.value = false;
    fetchData(); // Refresh list
  } catch (error) {
    toast.error('Delete failed', 'Please try again.');
  } finally {
    deleting.value = false;
  }
};
</script>

<template>
  <button @click="confirmDelete(item)">Delete</button>

  <ConfirmModal
    :show="showDeleteModal"
    title="Delete Item"
    :message="`Are you sure you want to delete <strong>${selectedItem?.name}</strong>?`"
    confirm-text="Delete"
    variant="danger"
    :loading="deleting"
    @confirm="handleDelete"
    @cancel="showDeleteModal = false"
  />
</template>
```

#### Custom Modal (Forms, Complex Content)
```vue
<script setup lang="ts">
import Modal from '@/components/Modal.vue';

const showModal = ref(false);
</script>

<template>
  <Modal :show="showModal" max-width="lg" @close="showModal = false">
    <div class="p-6">
      <h3 class="text-lg font-semibold">Modal Title</h3>
      <!-- Custom content -->
    </div>
    <div class="bg-muted/30 px-6 py-4 flex justify-end gap-2">
      <button @click="showModal = false">Close</button>
    </div>
  </Modal>
</template>
```

See `docs/MODAL_TOAST_SYSTEM.md` for complete documentation.

### Avatar/Initials Circle

```vue
<div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-semibold">
  {{ initials }}
</div>
```

## 🔤 Typography

### Headings
```vue
<h1 class="text-3xl font-bold text-foreground">Page Title</h1>
<h2 class="text-2xl font-bold text-foreground">Section Title</h2>
<h3 class="text-xl font-semibold text-foreground">Subsection</h3>
```

### Body Text
```vue
<p class="text-sm text-muted-foreground">Description or help text</p>
<p class="text-base text-foreground">Main content</p>
```

### Labels
```vue
<label class="block text-sm font-medium text-foreground mb-2">
  Field Label
</label>
```

## ⚡ Animations & Transitions

### Hover Effects
- **Lift on hover**: `transform hover:-translate-y-0.5 transition-all duration-200`
- **Shadow increase**: `shadow-md hover:shadow-lg`
- **Background opacity**: `hover:bg-accent/50`

### Loading States
```vue
<div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
```

### Staggered Float In
```css
.staggered-float-in {
  opacity: 0;
  animation-name: floatIn;
  animation-duration: 320ms;
  animation-fill-mode: both;
  animation-delay: calc(var(--anim-delay, 0) * 100ms);
}
```

## 🎭 Icon Guidelines

**ALWAYS use the appropriate icon library - NEVER hardcode inline SVGs (except for custom branding like logos)**

### Icon Strategy: Heroicons (UI) + Lucide (Domain)

Fish Books uses **two complementary icon libraries**:
- **Heroicons**: General UI actions (edit, delete, search, menu, navigation)
- **Lucide**: Domain-specific icons (fish, vessels, maritime, finance)

This combination provides comprehensive coverage while maintaining visual consistency.

### Heroicons (UI Icons)

#### Package & Usage
- **Package**: `@heroicons/vue` (v2.1.5)
- **Style**: **outline** (24x24) for all UI icons
- **Alternative**: solid (24x24) for filled variants, mini (20x20) for tiny icons

```vue
<script setup lang="ts">
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';
</script>

<template>
  <PlusIcon class="w-5 h-5" />
</template>
```

#### Common Heroicons
- Add: `PlusIcon`
- Edit: `PencilIcon`
- Delete: `TrashIcon`
- View: `EyeIcon`
- Search: `MagnifyingGlassIcon`
- Info: `InformationCircleIcon`
- Warning: `ExclamationTriangleIcon`
- Success: `CheckCircleIcon`
- Error: `XCircleIcon`
- Back: `ChevronLeftIcon`
- Close: `XMarkIcon`
- Menu: `Bars3Icon`
- Home: `HomeIcon`
- Settings: `Cog6ToothIcon`

### Lucide (Domain Icons)

#### Package & Usage
- **Package**: `lucide-vue-next`
- **Style**: Outline style (consistent with Heroicons)
- **Size Control**: Use `:size` prop or Tailwind classes

```vue
<script setup lang="ts">
import { Fish, Anchor, Waves, Ship } from 'lucide-vue-next';
</script>

<template>
  <Fish :size="20" class="text-primary" />
  <!-- OR -->
  <Fish class="w-5 h-5 text-primary" />
</template>
```

#### Common Lucide Icons by Category

**Fishing & Maritime:**
- `Fish` - Fish/catch representation
- `Anchor` - Vessel/maritime theme
- `Waves` - Ocean/trip status
- `Ship` - Vessel/boat
- `Sailboat` - Alternative vessel icon

**Finance & Sales:**
- `Banknote` - Bills/payments
- `DollarSign` - Currency (use for Rf)
- `Receipt` - Bills/receipts
- `CreditCard` - Payments
- `Wallet` - Crew payouts
- `Scale` - Weight/kilos

**Crew & People:**
- `Users` - Crew members
- `UserPlus` - Add crew
- `UserCheck` - Assigned crew
- `HardHat` - Worker/crew role

**Trips & Calendar:**
- `Calendar` - Trip dates
- `CalendarDays` - Weekly sheets
- `Clock` - Time tracking
- `MapPin` - Location

**Analytics & Reports:**
- `TrendingUp` - Sales increase
- `TrendingDown` - Expenses
- `BarChart3` - Reports/analytics
- `PieChart` - Distribution charts

**Other Useful:**
- `Package` - Cargo/catch
- `Boxes` - Inventory
- `ListChecks` - Checklist/tasks
- `FileText` - Documents/reports

### Icon Sizing (Both Libraries)
- **Buttons**: `w-5 h-5` (20px) or `:size="20"`
- **Navigation**: `w-5 h-5` (20px) or `:size="20"`
- **Headers**: `w-6 h-6` (24px) or `:size="24"`
- **Large/Feature**: `w-8 h-8` (32px) or `:size="32"`
- **Empty States**: `w-12 h-12` (48px) or `:size="48"` / `w-16 h-16` (64px) or `:size="64"`

### Icon Colors (Both Libraries)
- **Default**: `text-muted-foreground`
- **Active**: `text-primary`
- **Hover**: `group-hover:text-primary`
- **Danger**: `text-destructive`
- **Success**: `text-success`
- **Warning**: `text-warning`
- **Info**: `text-accent`

### Dynamic Icons (Both Libraries)
```vue
<script setup lang="ts">
import { HomeIcon } from '@heroicons/vue/24/outline';
import { Fish, Ship } from 'lucide-vue-next';

const navItems = [
  { name: 'Home', icon: HomeIcon }, // Heroicon
  { name: 'Trips', icon: Ship },    // Lucide
  { name: 'Fish Types', icon: Fish }, // Lucide
];
</script>

<template>
  <component :is="item.icon" class="w-5 h-5" />
</template>
```

### Choosing the Right Library

**Use Heroicons for:**
- Standard CRUD actions (add, edit, delete, view)
- Navigation controls (menu, close, back, forward)
- Form controls (search, filter, sort)
- General UI feedback (info, warning, error, success)

**Use Lucide for:**
- Domain-specific concepts (fish, vessels, maritime)
- Finance/money representations (use `DollarSign` for Rf currency)
- Specialized business objects (crew roles, trips, catch)
- Industry-specific visualizations

### Accessibility
- Add `aria-label` or `title` for icon-only buttons
- Use semantic colors (red for delete, green for success)
- Ensure WCAG AA contrast (4.5:1 minimum)
- Both libraries support proper ARIA attributes

**Reference**: See `docs/ICON_REFERENCE.md` for complete icon mapping guide

## 📱 Responsive Design

### Breakpoints (Tailwind defaults)
- **sm**: 640px
- **md**: 768px
- **lg**: 1024px
- **xl**: 1280px
- **2xl**: 1536px

### Mobile-First Approach
Always design for mobile first, then enhance for larger screens:
```vue
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
```

## ♿ Accessibility

### Color Contrast
- All text meets WCAG AA standards (4.5:1 minimum)
- Interactive elements have visible focus states: `focus:outline-none focus:ring-2 focus:ring-ring`

### Semantic HTML
- Use proper heading hierarchy (h1 → h2 → h3)
- Include `aria-label` on icon-only buttons
- Use `role` and `aria-*` attributes for modals and complex widgets

### Keyboard Navigation
- Ensure all interactive elements are keyboard accessible
- Include visible focus indicators
- Logical tab order

## 🔧 Code Conventions

### Vue 3 Composition API
- Always use `<script setup lang="ts">`
- Use TypeScript for type safety
- Define interfaces for API responses
- Use `ref` for reactive primitives, `computed` for derived state

### File Structure
```
resources/js/
├── pages/          # Page components (one per route)
├── layouts/        # Layout components (MainLayout, etc.)
├── components/     # Reusable components
└── utils/          # Helper functions, API calls
```

### Naming Conventions
- **Components**: PascalCase (e.g., `CrewMemberList.vue`)
- **Composables**: camelCase with `use` prefix (e.g., `useAuth.ts`)
- **Constants**: SCREAMING_SNAKE_CASE
- **Variables**: camelCase

### API Calls
- Use **session-based authentication** (NOT token-based)
- Include `credentials: 'include'` in all fetch requests
- Include CSRF token in headers: `'X-CSRF-TOKEN': getCsrfToken()`
- Use try-catch for error handling
- Show loading states during async operations
- Handle 401 (redirect to login) and 403 (show error) gracefully
- Dates in API calls are always in `YYYY-MM-DD` format (automatically handled by Datepicker)

### State Management
- Use Vue's reactivity system (`ref`, `computed`, `reactive`)
- Access user data via `page.props.auth.user` (shared by Inertia)
- Use `router.post('/logout')` for logout (NOT manual fetch)
- For complex state, consider Pinia (if needed later)

### Date Handling
- **Display Format**: `DD/MM/YYYY` (configured in `DEFAULT_DATE_FORMAT`)
- **Storage Format**: `YYYY-MM-DD` (ISO standard - automatically handled)
- Use `formatDate()` utility for displaying dates in templates
- Use `<Datepicker>` component for all date inputs
- Import: `import { formatDate } from '@/utils/functions'`
- Import: `import Datepicker from '@/components/ui/datepicker.vue'`

### Currency Formatting
- **Currency**: MVR (Maldivian Rufiyaa, ISO 4217: MVR, code: 462)
- **Symbol**: Rf (e.g., "Rf 1,234.50")
- **Subdivisions**: 1 Rufiyaa = 100 laari
- **ALWAYS use** the centralized `formatCurrency()` utility function
- Import: `import { formatCurrency } from '@/utils/functions'`
- Example usage: `formatCurrency(amount)` → "Rf 1,234.50"
- Never hardcode USD or $ - always use MVR/Rf
- Input field prefix: Use `<span class="absolute left-3 top-2.5 text-muted-foreground text-sm">Rf</span>`

## 🎣 Fishing Domain Terminology

- **Crew Member**: Individual working on fishing trips
- **Vessel**: Fishing boat
- **Trip**: Single fishing expedition with assignments, sales, and expenses
- **Weekly Sheet**: Aggregated payouts for a week period
- **Fish Types**: Damaged, Proper, Quality, Other (with per-kilo rates)
- **Roles**: BAITING, FISHING, CHUMMER, DIVING, HELPER, SPECIAL
- **Bill Types**: TODAY_SALES, PREVIOUS_DAY_SALES, OTHER
- **Trip Status**: DRAFT → ONGOING → CLOSED
- **Weekly Status**: DRAFT → FINALIZED

## 📋 Best Practices

1. **Consistency**: Always use the established patterns above
2. **Reusability**: Extract repeated patterns into components
3. **Performance**: Lazy load heavy components, debounce search inputs
4. **Testing**: Test with both OWNER and MANAGER roles
5. **Validation**: Show clear error messages with field-level validation
6. **Feedback**: Always show loading states and success/error messages
7. **Documentation**: Comment complex calculations or business logic
8. **Date Inputs**: Always use `<Datepicker>` component, never native `<input type="date">`

## 🌟 Example Page Structure

### List/Dashboard Pages (Full Width)
```vue
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import MainLayout from '@/layouts/MainLayout.vue';

// Types
interface DataType {
  id: number;
  // ... fields
}

interface User {
  id: number;
  name: string;
  email: string;
  role: 'OWNER' | 'MANAGER';
}

interface PageProps {
  auth: {
    user: User | null;
  };
}

// Access authenticated user
const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);

// State
const data = ref<DataType[]>([]);
const loading = ref(true);

// Helper for CSRF token
const getCsrfToken = (): string => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  return token || '';
};

// Methods
const fetchData = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/resource', {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include', // Include session cookies
    });
    const result = await response.json();
    data.value = result.data;
  } catch (error) {
    console.error('Failed to fetch data:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchData();
});
</script>

<template>
  <MainLayout>
    <div class="w-full px-6 space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-foreground">Page Title</h1>
          <p class="mt-1 text-sm text-muted-foreground">Description</p>
        </div>
        <!-- Actions -->
      </div>

      <!-- Content Cards -->
      <div class="glass-card rounded-xl border border-border/50 shadow-lg">
        <!-- Card content -->
      </div>
    </div>
  </MainLayout>
</template>
```

### Form Pages (Centered)
```vue
<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import MainLayout from '@/layouts/MainLayout.vue';

interface Props {
  id?: number;
}

const props = defineProps<Props>();

const form = ref({
  name: '',
  // ... other fields
});

const errors = ref<Record<string, string>>({});
const submitting = ref(false);

const getCsrfToken = (): string => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  return token || '';
};

const submit = async () => {
  submitting.value = true;
  errors.value = {};
  
  const url = props.id ? `/api/resource/${props.id}` : '/api/resource';
  const method = props.id ? 'PUT' : 'POST';
  
  try {
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      credentials: 'include',
      body: JSON.stringify(form.value),
    });
    
    const data = await response.json();
    
    if (!response.ok) {
      if (data.errors) {
        errors.value = data.errors;
      }
      return;
    }
    
    // Success - redirect
    router.visit('/resource');
  } catch (error) {
    console.error('Failed to submit:', error);
  } finally {
    submitting.value = false;
  }
};
</script>

<template>
  <MainLayout>
    <div class="max-w-5xl mx-auto px-6 space-y-6">
      <!-- Form content -->
    </div>
  </MainLayout>
</template>
```

---

**Remember**: The goal is a cohesive, professional fishing crew management system that feels like a breath of ocean air while maintaining enterprise-grade functionality. 🌊🐟
