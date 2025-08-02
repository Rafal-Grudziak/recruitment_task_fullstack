export const SUPPORTED = ['EUR','USD','CZK','IDR','BRL'];

export function decorateRate(code, mid) {
  if (code === 'EUR' || code === 'USD') {
    return {
      mid,
      buy: round2(mid - 0.15),
      sell: round2(mid + 0.11),
    };
  }
  return {
    mid,
    buy: null,
    sell: round2(mid + 0.2),
  };
}

export function round2(v) {
  return Math.round(v * 100) / 100;
} 