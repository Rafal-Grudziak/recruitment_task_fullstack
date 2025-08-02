import React, { useEffect, useMemo, useState } from 'react';
import { getTodayRates, getHistory } from '../api/rates';
import { decorateRate, round2 } from '../utils/rates';

function formatToday() {
  const d = new Date();
  const mm = String(d.getMonth()+1).padStart(2,'0');
  const dd = String(d.getDate()).padStart(2,'0');
  return `${d.getFullYear()}-${mm}-${dd}`;
}

export default function App() {
  const [rates, setRates] = useState([]);
  const [loading, setLoading] = useState(true);
  const [err, setErr] = useState(null);

  const [selectedCode, setSelectedCode] = useState(null);
  const [selectedDate, setSelectedDate] = useState(formatToday());
  const [history, setHistory] = useState([]);
  const [historyLoading, setHistoryLoading] = useState(false);
  const [historyErr, setHistoryErr] = useState(null);

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

  useEffect(() => {
    if (!selectedCode) return;
    (async () => {
      try {
        setHistoryLoading(true);
        setHistoryErr(null);
        const data = await getHistory(selectedCode, selectedDate);
        setHistory(data);
      } catch (e) {
        setHistoryErr(String(e));
      } finally {
        setHistoryLoading(false);
      }
    })();
  }, [selectedCode, selectedDate]);

  return (
    <div className="container py-4">
      <h1 className="mb-4">Kursy walut – kantor</h1>

      {loading && <div className="alert alert-info">Ładowanie…</div>}
      {err && <div className="alert alert-danger">Błąd: {err}</div>}

      {!loading && !err && (
        <div className="table-responsive">
          <table className="table table-striped align-middle">
            <thead>
              <tr>
                <th>Waluta</th>
                <th>Nazwa</th>
                <th>Średni (NBP)</th>
                <th>Kupno</th>
                <th>Sprzedaż</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {rates.map((r) => {
                const decorated = decorateRate(r.code, r.mid);
                return (
                  <tr key={r.code}>
                    <td><strong>{r.code}</strong></td>
                    <td>{r.currency}</td>
                    <td>{round2(r.mid)}</td>
                    <td>{decorated.buy ?? '—'}</td>
                    <td>{decorated.sell}</td>
                    <td>
                      <button className="btn btn-sm btn-outline-primary"
                        onClick={() => setSelectedCode(r.code)}>
                        Historia
                      </button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}

      {/* Panel historii */}
      {selectedCode && (
        <div className="card mt-4">
          <div className="card-header d-flex justify-content-between align-items-center">
            <div>
              Historia kursów: <strong>{selectedCode}</strong> (ostatnie 14 dni przed datą)
            </div>
            <button className="btn btn-sm btn-outline-secondary"
              onClick={() => { setSelectedCode(null); setHistory([]); }}>
              Zamknij
            </button>
          </div>
          <div className="card-body">
            <div className="row g-3 align-items-end mb-3">
              <div className="col-auto">
                <label className="form-label">Data (domyślnie dziś):</label>
                <input type="date" className="form-control"
                  value={selectedDate}
                  onChange={(e) => setSelectedDate(e.target.value)} />
              </div>
            </div>

            {historyLoading && <div className="alert alert-info">Ładowanie historii…</div>}
            {historyErr && <div className="alert alert-danger">Błąd: {historyErr}</div>}

            {!historyLoading && !historyErr && history.length === 0 && (
              <div className="text-muted">Brak danych historycznych.</div>
            )}

            {!historyLoading && history.length > 0 && (
              <div className="table-responsive">
                <table className="table table-sm">
                  <thead>
                    <tr>
                      <th>Data</th>
                      <th>Średni (NBP)</th>
                      <th>Kupno</th>
                      <th>Sprzedaż</th>
                    </tr>
                  </thead>
                  <tbody>
                    {history.map((h) => {
                      const d = decorateRate(selectedCode, h.mid);
                      return (
                        <tr key={h.date}>
                          <td>{h.date}</td>
                          <td>{round2(h.mid)}</td>
                          <td>{d.buy ?? '—'}</td>
                          <td>{d.sell}</td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
} 