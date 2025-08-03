import React, { useEffect, useState } from 'react';
import { getHistory } from '../api/rates';
import { decorateRate, round2 } from '../utils/rates';

export default function RateHistoryPanel({ code, date, onDateChange, onClose }) {
  const [history, setHistory] = useState([]);
  const [loading, setLoading] = useState(false);
  const [err, setErr] = useState(null);

  useEffect(() => {
    (async () => {
      try {
        setLoading(true);
        setErr(null);
        const data = await getHistory(code, date);
        setHistory(data);
      } catch (e) {
        setErr(String(e));
      } finally {
        setLoading(false);
      }
    })();
  }, [code, date]);

  return (
    <div className="card mt-4">
      <div className="card-header d-flex justify-content-between align-items-center">
        <div>
          Historia kursów: <strong>{code}</strong> (ostatnie 14 dni przed datą)
        </div>
        <button className="btn btn-sm btn-outline-secondary" onClick={onClose}>
          Zamknij
        </button>
      </div>
      <div className="card-body">
        <div className="row g-3 align-items-end mb-3">
          <div className="col-auto">
            <label className="form-label">Data (domyślnie dziś):</label>
            <input
              type="date"
              className="form-control"
              value={date}
              onChange={(e) => onDateChange(e.target.value)}
            />
          </div>
        </div>

        {loading && <div className="alert alert-info">Ładowanie historii…</div>}
        {err && <div className="alert alert-danger">Błąd: {err}</div>}

        {!loading && !err && history.length === 0 && (
          <div className="alert alert-warning">Brak danych historycznych.</div>
        )}

        {!loading && history.length > 0 && (
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
                  const d = decorateRate(code, h.mid);
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
  );
}
