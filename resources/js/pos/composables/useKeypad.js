import { ref, computed } from 'vue';
import { POS_CONFIG } from '../constants/index.js';

export function useKeypad() {
  const currentNumber = ref('');
  const isProcessing = ref(false);
  let debounceTimer = null;

  // Computed properties
  const displayNumber = computed(() => {
    return currentNumber.value || '0';
  });

  const hasValue = computed(() => {
    return currentNumber.value.length > 0;
  });

  const numericValue = computed(() => {
    const num = parseInt(currentNumber.value);
    return isNaN(num) ? 0 : num;
  });

  // Validar entrada numérica
  const validateInput = (input) => {
    const str = String(input);
    return /^\d+$/.test(str) && str.length <= 3; // Máximo 3 dígitos
  };

  // Agregar número con debounce
  const addNumber = (number, callback) => {
    if (!validateInput(number)) {
      console.warn('Invalid input:', number);
      return;
    }

    const newValue = currentNumber.value + String(number);
    const numValue = parseInt(newValue);

    if (numValue > POS_CONFIG.MAX_QUANTITY) {
      console.warn('Quantity exceeds maximum:', numValue);
      return;
    }

    currentNumber.value = newValue;

    // Debounce para evitar múltiples llamadas
    if (debounceTimer) {
      clearTimeout(debounceTimer);
    }

    debounceTimer = setTimeout(() => {
      if (callback && typeof callback === 'function') {
        isProcessing.value = true;
        try {
          callback(numValue);
        } catch (error) {
          console.error('Error in keypad callback:', error);
        } finally {
          isProcessing.value = false;
        }
      }
    }, POS_CONFIG.DEBOUNCE_DELAY);
  };

  // Limpiar número
  const clear = () => {
    currentNumber.value = '';
    if (debounceTimer) {
      clearTimeout(debounceTimer);
      debounceTimer = null;
    }
  };

  // Borrar último dígito
  const backspace = () => {
    if (currentNumber.value.length > 0) {
      currentNumber.value = currentNumber.value.slice(0, -1);
    }
  };

  // Establecer valor directamente
  const setValue = (value) => {
    const numValue = parseInt(value);
    if (
      !isNaN(numValue) &&
      numValue >= 0 &&
      numValue <= POS_CONFIG.MAX_QUANTITY
    ) {
      currentNumber.value = String(numValue);
    }
  };

  // Cleanup al desmontar
  const cleanup = () => {
    if (debounceTimer) {
      clearTimeout(debounceTimer);
      debounceTimer = null;
    }
  };

  return {
    // State
    currentNumber,
    isProcessing,

    // Computed
    displayNumber,
    hasValue,
    numericValue,

    // Methods
    addNumber,
    clear,
    backspace,
    setValue,
    cleanup,
  };
}
