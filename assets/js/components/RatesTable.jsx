import React from 'react';
import { round2 } from '../utils/rates';

export default function RatesTable({ rates, onSelectCode }) {
  return (
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
          {rates.map((r) => (
            <tr key={r.code}>
              <td><strong>{r.code}</strong></td>
              <td>{r.currency}</td>
              <td>{round2(r.mid)}</td>
              <td>{r.buy ?? '—'}</td>
              <td>{r.sell}</td>
              <td>
                <button
                  className="btn btn-sm btn-outline-primary"
                  onClick={() => onSelectCode(r.code)}
                >
                  Historia
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
