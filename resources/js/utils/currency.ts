/**
 * Utilidades para manejo de monedas y formateo de precios
 */

export type Currency = 'USD' | 'BOB'

export interface CurrencyConfig {
  symbol: string
  name: string
  code: Currency
}

export const CURRENCIES: Record<Currency, CurrencyConfig> = {
  USD: {
    symbol: '$',
    name: 'Dólares Americanos',
    code: 'USD',
  },
  BOB: {
    symbol: 'Bs.',
    name: 'Bolivianos',
    code: 'BOB',
  },
}

/**
 * Formatea un precio con el símbolo de moneda
 * @param price - Precio numérico
 * @param currency - Código de moneda (USD o BOB)
 * @returns Precio formateado con símbolo (ej: "$150,000.00" o "Bs.150,000.00")
 */
export function formatPrice(price: number | string, currency: Currency = 'USD'): string {
  const config = CURRENCIES[currency] || CURRENCIES.USD
  const numericPrice = typeof price === 'string' ? parseFloat(price) : price

  if (isNaN(numericPrice)) {
    return `${config.symbol}0.00`
  }

  const formattedPrice = numericPrice.toLocaleString('es-BO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })

  return `${config.symbol}${formattedPrice}`
}

/**
 * Formatea un precio sin el símbolo de moneda
 * @param price - Precio numérico
 * @returns Precio formateado (ej: "150,000.00")
 */
export function formatPriceNumber(price: number | string): string {
  const numericPrice = typeof price === 'string' ? parseFloat(price) : price

  if (isNaN(numericPrice)) {
    return '0.00'
  }

  return numericPrice.toLocaleString('es-BO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

/**
 * Obtiene el símbolo de una moneda
 * @param currency - Código de moneda
 * @returns Símbolo de la moneda
 */
export function getCurrencySymbol(currency: Currency = 'USD'): string {
  return CURRENCIES[currency]?.symbol || '$'
}

/**
 * Obtiene el nombre de una moneda
 * @param currency - Código de moneda
 * @returns Nombre de la moneda
 */
export function getCurrencyName(currency: Currency = 'USD'): string {
  return CURRENCIES[currency]?.name || 'Dólares Americanos'
}

/**
 * Obtiene la lista de monedas disponibles
 * @returns Array de configuraciones de moneda
 */
export function getAvailableCurrencies(): CurrencyConfig[] {
  return Object.values(CURRENCIES)
}

/**
 * Convierte un precio entre monedas (tasa de cambio aproximada)
 * @param price - Precio a convertir
 * @param fromCurrency - Moneda de origen
 * @param toCurrency - Moneda de destino
 * @param exchangeRate - Tasa de cambio (por defecto, BOB/USD ≈ 7)
 * @returns Precio convertido
 */
export function convertCurrency(
  price: number | string,
  fromCurrency: Currency,
  toCurrency: Currency,
  exchangeRate: number = 7
): number {
  const numericPrice = typeof price === 'string' ? parseFloat(price) : price

  if (isNaN(numericPrice)) {
    return 0
  }

  if (fromCurrency === toCurrency) {
    return numericPrice
  }

  // Convertir de USD a BOB
  if (fromCurrency === 'USD' && toCurrency === 'BOB') {
    return numericPrice * exchangeRate
  }

  // Convertir de BOB a USD
  if (fromCurrency === 'BOB' && toCurrency === 'USD') {
    return numericPrice / exchangeRate
  }

  return numericPrice
}
