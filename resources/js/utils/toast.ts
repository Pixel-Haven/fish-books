import { ref, reactive } from 'vue';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
  id: number;
  type: ToastType;
  title: string;
  message?: string;
  duration?: number;
}

interface ToastState {
  toasts: Toast[];
}

const state = reactive<ToastState>({
  toasts: [],
});

let nextId = 0;

export function useToast() {
  const show = (
    type: ToastType,
    title: string,
    message?: string,
    duration: number = 5000
  ) => {
    const id = nextId++;
    const toast: Toast = {
      id,
      type,
      title,
      message,
      duration,
    };

    state.toasts.push(toast);

    if (duration > 0) {
      setTimeout(() => {
        remove(id);
      }, duration);
    }

    return id;
  };

  const remove = (id: number) => {
    const index = state.toasts.findIndex((t) => t.id === id);
    if (index > -1) {
      state.toasts.splice(index, 1);
    }
  };

  const success = (title: string, message?: string, duration?: number) => {
    return show('success', title, message, duration);
  };

  const error = (title: string, message?: string, duration?: number) => {
    return show('error', title, message, duration);
  };

  const warning = (title: string, message?: string, duration?: number) => {
    return show('warning', title, message, duration);
  };

  const info = (title: string, message?: string, duration?: number) => {
    return show('info', title, message, duration);
  };

  return {
    toasts: state.toasts,
    show,
    remove,
    success,
    error,
    warning,
    info,
  };
}
