import { POS_CONFIG } from "../constants/index.js";

/**
 * Formatea un valor numérico como moneda
 * @param {number} value - Valor a formatear
 * @param {string} currency - Código de moneda (opcional)
 * @param {string} locale - Configuración regional (opcional)
 * @returns {string} Valor formateado como moneda
 */
export function formatCurrency(
    value,
    currency = POS_CONFIG.CURRENCY,
    locale = POS_CONFIG.LOCALE
) {
    const numericValue = Number(value);

    if (isNaN(numericValue)) {
        return formatCurrency(0, currency, locale);
    }

    try {
        return new Intl.NumberFormat(locale, {
            style: "currency",
            currency: currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(numericValue);
    } catch (error) {
        console.error("Error formatting currency:", error);
        return `${currency} ${numericValue.toFixed(2)}`;
    }
}

/**
 * Parsea un string de moneda a número
 * @param {string} currencyString - String de moneda a parsear
 * @returns {number} Valor numérico
 */
export function parseCurrency(currencyString) {
    if (typeof currencyString !== "string") {
        return 0;
    }

    // Remover símbolos de moneda y espacios
    const cleanString = currencyString.replace(/[^\d.,]/g, "");
    const number = parseFloat(cleanString.replace(",", "."));

    return isNaN(number) ? 0 : number;
}

/**
 * Valida si un valor es un precio válido
 * @param {any} value - Valor a validar
 * @returns {boolean} True si es un precio válido
 */
export function isValidPrice(value) {
    const num = typeof value === "string" ? parseFloat(value) : value;
    return typeof num === "number" && !isNaN(num) && num >= 0;
}
