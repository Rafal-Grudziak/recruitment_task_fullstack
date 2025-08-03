import React, { useEffect, useState } from 'react';
import { getTodayRates } from '../api/rates';
import RateHistoryPanel from './RateHistoryPanel';
import RatesTable from './RatesTable';

function formatToday() {
  const d = new Date();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${d.getFullYear()}-${mm}-${dd}`;
}

export default function App() {
  const [rates, setRates] = useState([]);
  const [loading, setLoading] = useState(true);
  const [err, setErr] = useState(null);

  const [selectedCode, setSelectedCode] = useState(null);
  const [selectedDate, setSelectedDate] = useState(formatToday());

  useEffect(() => {
    (async () => {
      try {
        setLoading(true);
        const data = await getTodayRates();
        setRates(data);
      } catch (e) {
        setErr(String(e));
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  return (
    <div className="container py-4">
      <h1 className="mb-4">Kursy walut – kantor</h1>

      {loading && <div className="alert alert-info">Ładowanie…</div>}
      {err && <div className="alert alert-danger">Błąd: {err}</div>}

      {!loading && !err && (
        <RatesTable rates={rates} onSelectCode={setSelectedCode} />
      )}

      {selectedCode && (
        <RateHistoryPanel
          code={selectedCode}
          date={selectedDate}
          onDateChange={setSelectedDate}
          onClose={() => {
            setSelectedCode(null);
            setSelectedDate(formatToday());
          }}
        />
      )}
    </div>
  );
}
