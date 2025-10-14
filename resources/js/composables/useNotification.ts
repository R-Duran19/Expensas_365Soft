import { useToast } from 'primevue/usetoast';

export const useNotification = () => {
  const toast = useToast();

  const showSuccess = (message: string, detail?: string) => {
    toast.add({
      severity: 'success',
      summary: message,
      detail: detail || '',
      life: 3000,
    });
  };

  const showError = (message: string, detail?: string) => {
    toast.add({
      severity: 'error',
      summary: message,
      detail: detail || '',
      life: 5000,
    });
  };

  const showWarning = (message: string, detail?: string) => {
    toast.add({
      severity: 'warn',
      summary: message,
      detail: detail || '',
      life: 4000,
    });
  };

  const showInfo = (message: string, detail?: string) => {
    toast.add({
      severity: 'info',
      summary: message,
      detail: detail || '',
      life: 3000,
    });
  };

  return {
    showSuccess,
    showError,
    showWarning,
    showInfo,
  };
};