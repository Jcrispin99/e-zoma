// Constantes de configuración del sistema POS
export const POS_CONFIG = {
  TAX_RATE: 0.18, // 18% de impuestos
  CURRENCY: 'PEN',
  LOCALE: 'es-PE',
  MAX_QUANTITY: 999,
  MIN_QUANTITY: 0,
  DEBOUNCE_DELAY: 0, // Sin delay para respuesta inmediata
};

// Mensajes de error
export const ERROR_MESSAGES = {
  INVALID_QUANTITY: 'Cantidad inválida',
  PRODUCT_NOT_FOUND: 'Producto no encontrado',
  CART_EMPTY: 'El carrito está vacío',
  INVALID_PRODUCT: 'Producto inválido',
};

// Estados del sistema
export const SYSTEM_STATUS = {
  CONNECTED: 'Conectado',
  DISCONNECTED: 'Desconectado',
  LOADING: 'Cargando...',
};
