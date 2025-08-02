const BASE = '';

export async function getTodayRates() {
  const res = await fetch(`${BASE}/api/rates/today`, { headers: { 'Accept': 'application/json' }});
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}

export async function getHistory(code, date /* YYYY-MM-DD */) {
  const q = new URLSearchParams({ code, ...(date ? { date } : {}) }).toString();
  const res = await fetch(`${BASE}/api/rates/history?${q}`, { headers: { 'Accept': 'application/json' }});
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
} 