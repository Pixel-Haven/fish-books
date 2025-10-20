# Date Utility System

## Overview

Fish Books uses a centralized date formatting system built on Day.js to ensure consistent date display across the entire application.

## Default Date Format

The application-wide default date format is defined in `resources/js/utils/functions.ts`:

```typescript
export const DEFAULT_DATE_FORMAT = "DD/MM/YYYY";
```

**To change the default format for the entire application**, simply update this constant.

### Common Format Patterns

- `DD/MM/YYYY` - 20/10/2025
- `MM/DD/YYYY` - 10/20/2025 (US format)
- `YYYY-MM-DD` - 2025-10-20 (ISO format)
- `DD MMMM YYYY` - 20 October 2025
- `MMMM DD, YYYY` - October 20, 2025
- `ddd, MMM D, YYYY` - Mon, Oct 20, 2025

See [Day.js format documentation](https://day.js.org/docs/en/display/format) for all options.

## Usage

### In Vue Components

The `formatDate` function is globally available in all Vue components:

```vue
<template>
  <div>
    <!-- Uses default format (DD/MM/YYYY) -->
    <p>{{ formatDate(trip.date) }}</p>
    
    <!-- Custom format -->
    <p>{{ formatDate(trip.date, 'MMMM DD, YYYY') }}</p>
    
    <!-- With locale -->
    <p>{{ formatDate(trip.date, 'DD/MM/YYYY', 'dv') }}</p>
  </div>
</template>
```

### In TypeScript/Script

Import the function from utils:

```typescript
import { formatDate, DEFAULT_DATE_FORMAT } from '@/utils/functions';

const formatted = formatDate(new Date());
const customFormat = formatDate(new Date(), 'YYYY-MM-DD');
```

## API

### `formatDate(date, format?, locale?)`

**Parameters:**
- `date` (Date | dayjs.Dayjs | string | undefined) - The date to format
- `format` (string, optional) - Format pattern (defaults to `DEFAULT_DATE_FORMAT`)
- `locale` (string, optional) - Locale code (defaults to "en")

**Returns:** string - Formatted date string

### `timeAgo(date, locale?, withoutSuffix?)`

Displays relative time (e.g., "2 days ago", "in 3 hours"):

**Parameters:**
- `date` (Date | dayjs.Dayjs | string | undefined) - The date to format
- `locale` (string, optional) - Locale code (defaults to "en")
- `withoutSuffix` (boolean, optional) - If true, removes "ago"/"in" suffix (defaults to false)

**Returns:** string - Relative time string

## Migration from Local formatDate

Many components currently implement their own local `formatDate` functions. These should be replaced with the global utility:

### Before (Local Implementation)
```vue
<script setup lang="ts">
const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-GB', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};
</script>

<template>
  <p>{{ formatDate(item.date) }}</p>
</template>
```

### After (Global Utility)
```vue
<script setup lang="ts">
// formatDate is already available globally - no need to import or define!
</script>

<template>
  <p>{{ formatDate(item.date) }}</p>
</template>
```

## Files to Migrate

The following files have local `formatDate` implementations that should be removed:

- `resources/js/pages/WeeklySheets/Index.vue`
- `resources/js/pages/Trips/Index.vue`
- `resources/js/pages/FishTypes/Form.vue`
- `resources/js/components/project-table.vue`

## Date Input Components

For date input fields, use the `<datepicker>` component which accepts a `format` prop:

```vue
<datepicker 
  v-model="form.date" 
  :format="DEFAULT_DATE_FORMAT"
  placeholder="Select date"
/>
```

### Important: Internal vs Display Format

The `<datepicker>` component separates **display format** from **data format**:

- **Display Format**: Uses `DEFAULT_DATE_FORMAT` (e.g., DD/MM/YYYY) for user-friendly display
- **Data Format**: Always stores and emits dates as `YYYY-MM-DD` (ISO format) for backend/API compatibility

This means:
- The input field shows: `20/10/2025` (user-friendly)
- The v-model value is: `2025-10-20` (backend-friendly)
- No conversion needed in your API calls!

## Backend Date Handling

Laravel automatically handles date serialization for Carbon instances. Ensure your models cast date fields properly:

```php
protected $casts = [
    'date' => 'date',
    'created_at' => 'datetime',
];
```

## Best Practices

1. **Always use the global `formatDate`** instead of creating local implementations
2. **Use default format** unless you have a specific reason to override
3. **Keep date logic in utilities** rather than inline in templates
4. **Test date displays** across different locales if supporting internationalization
5. **Update `DEFAULT_DATE_FORMAT`** in one place to change app-wide format

## Troubleshooting

### Dates showing as "Invalid Date"
- Ensure the date string is in a parseable format
- Check if the value is `null` or `undefined` before passing to `formatDate`

### Format not updating
- Clear cache: `php artisan optimize:clear`
- Rebuild frontend: `npm run build` or restart `npm run dev`

### Timezone issues
- Ensure server and client timezones are configured correctly
- Use UTC in database, convert to local timezone in display layer