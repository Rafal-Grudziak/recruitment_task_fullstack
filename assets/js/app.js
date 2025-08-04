import React from 'react';
import ReactDOM from 'react-dom';
import App from './components/App.jsx';

document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('root');
  if (container) {
    ReactDOM.render(<App />, container);
  }
});