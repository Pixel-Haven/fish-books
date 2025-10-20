# Modal and Toast System Documentation

## Overview

Fish Books includes a centralized modal and toast notification system for consistent UX across the application.

## Toast Notifications

### Setup

The `ToastContainer` is already included in `MainLayout.vue`, so toasts work globally.

### Usage

```vue
<script setup lang="ts">
import { useToast } from '@/utils/toast';

const toast = useToast();

// Success toast
toast.success('Operation successful', 'The vessel was created successfully.');

// Error toast
toast.error('Operation failed', 'Unable to save changes. Please try again.');

// Warning toast
toast.warning('Warning', 'This action may affect existing data.');

// Info toast
toast.info('Information', 'New features are available.');

// Custom duration (default is 5000ms)
toast.success('Quick message', undefined, 2000);
</script>
```

### Toast API

```typescript
// Show toast with custom type
toast.show(
  type: 'success' | 'error' | 'warning' | 'info',
  title: string,
  message?: string,
  duration?: number  // milliseconds, default 5000
): number  // returns toast ID

// Show success toast
toast.success(title: string, message?: string, duration?: number): number

// Show error toast
toast.error(title: string, message?: string, duration?: number): number

// Show warning toast
toast.warning(title: string, message?: string, duration?: number): number

// Show info toast
toast.info(title: string, message?: string, duration?: number): number

// Manually remove a toast
toast.remove(id: number): void
```

## Modal System

### Basic Modal

The `Modal` component provides a blank modal container with backdrop and animations.

```vue
<script setup lang="ts">
import { ref } from 'vue';
import Modal from '@/components/Modal.vue';

const showModal = ref(false);
</script>

<template>
  <button @click="showModal = true">Open Modal</button>

  <Modal 
    :show="showModal" 
    max-width="lg"
    :closeable="true"
    @close="showModal = false"
  >
    <div class="p-6">
      <h3 class="text-lg font-semibold">Modal Title</h3>
      <p class="mt-2 text-sm text-muted-foreground">Modal content goes here.</p>
    </div>
    <div class="bg-muted/30 px-6 py-4 flex justify-end gap-2">
      <button @click="showModal = false">Close</button>
    </div>
  </Modal>
</template>
```

### Modal Props

```typescript
interface Props {
  show: boolean;              // Controls visibility
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';  // Default: 'md'
  closeable?: boolean;        // Allow closing with Esc or backdrop click (default: true)
}
```

### Confirm Modal

The `ConfirmModal` component provides a pre-styled confirmation dialog.

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
    // Perform delete operation
    await fetch(`/api/items/${selectedItem.value.id}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    
    toast.success('Deleted', 'Item was deleted successfully.');
    showDeleteModal.value = false;
    // Refresh data...
  } catch (error) {
    toast.error('Delete failed', 'Unable to delete item.');
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
    :message="`Are you sure you want to delete <strong>${selectedItem?.name}</strong>? This action cannot be undone.`"
    confirm-text="Delete"
    cancel-text="Cancel"
    variant="danger"
    :loading="deleting"
    @confirm="handleDelete"
    @cancel="showDeleteModal = false"
  />
</template>
```

### ConfirmModal Props

```typescript
interface Props {
  show: boolean;                           // Controls visibility
  title: string;                           // Modal title
  message: string;                         // Message (supports HTML)
  confirmText?: string;                    // Confirm button text (default: 'Confirm')
  cancelText?: string;                     // Cancel button text (default: 'Cancel')
  variant?: 'danger' | 'warning' | 'info'; // Visual style (default: 'danger')
  loading?: boolean;                       // Show loading state (default: false)
}

// Events
@confirm  // Emitted when confirm button is clicked
@cancel   // Emitted when cancel button or backdrop is clicked
```

### ConfirmModal Variants

- **danger**: Red icon and button (for deletions, destructive actions)
- **warning**: Orange icon and button (for potentially harmful actions)
- **info**: Blue icon and button (for informational confirmations)

## Best Practices

### When to Use Toasts

✅ **Use toasts for:**
- Success confirmations after CRUD operations
- Error messages for failed operations
- Warnings about system state
- Informational messages

❌ **Don't use toasts for:**
- Critical errors that require user action
- Complex multi-step confirmations
- Long-form messages

### When to Use Modals

✅ **Use ConfirmModal for:**
- Delete confirmations
- Irreversible action confirmations
- Simple yes/no decisions

✅ **Use base Modal for:**
- Forms in overlay
- Complex multi-step workflows
- Content preview/detail views

### Combining Toasts and Modals

Common pattern for delete operations:

```vue
<script setup lang="ts">
const confirmDelete = (item) => {
  // Show confirm modal
  selectedItem.value = item;
  showDeleteModal.value = true;
};

const handleDelete = async () => {
  deleting.value = true;
  
  try {
    await deleteItem();
    
    // Close modal first
    showDeleteModal.value = false;
    
    // Then show success toast
    toast.success('Deleted', 'Item removed successfully.');
  } catch (error) {
    // Keep modal open, show error toast
    toast.error('Delete failed', 'Please try again.');
  } finally {
    deleting.value = false;
  }
};
</script>
```

## Styling

All components follow the Fish Books ocean theme:

- Toasts use semantic colors with proper contrast
- Modals have glassmorphic backdrop with blur
- Animations are smooth and professional (200-300ms)
- Focus states are accessible

## Accessibility

- Modals use proper ARIA attributes (`role="dialog"`, `aria-modal="true"`)
- Escape key closes modals (when closeable)
- Focus is trapped within modals
- Toasts are positioned in top-right for visibility
- All colors meet WCAG AA contrast standards

## Examples

See these files for complete examples:
- `resources/js/pages/Vessels/Index.vue`
- `resources/js/pages/CrewMembers/Index.vue`
- `resources/js/pages/FishTypes/Index.vue`
