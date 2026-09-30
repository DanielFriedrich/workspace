/*
 * Spark to Synergy – kleiner Server ohne Abhängigkeiten (Node.js >= 18)
 *
 *  - liefert das Spiel aus (statische Dateien)
 *  - POST /api/participants          speichert eine Gewinnspiel-Teilnahme
 *  - GET  /api/participants.csv?token=…  Export aller Teilnahmen (ADMIN_TOKEN nötig)
 *
 * Start:  node server.js            (Port 4173)
 *         PORT=8080 ADMIN_TOKEN=geheim node server.js
 */
const http = require('http');
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const PORT = Number(process.env.PORT) || 4173;
const ROOT = __dirname;
const DATA_DIR = process.env.DATA_DIR || path.join(ROOT, 'data');
const DATA_FILE = path.join(DATA_DIR, 'participants.jsonl');
const ADMIN_TOKEN = process.env.ADMIN_TOKEN || '';

const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.svg': 'image/svg+xml',
  '.ico': 'image/x-icon'
};

const json = (res, code, obj) => {
  res.writeHead(code, { 'Content-Type': 'application/json; charset=utf-8' });
  res.end(JSON.stringify(obj));
};

function readAll() {
  if (!fs.existsSync(DATA_FILE)) return [];
  return fs.readFileSync(DATA_FILE, 'utf8').split('\n').filter(Boolean).map((l) => {
    try { return JSON.parse(l); } catch (e) { return null; }
  }).filter(Boolean);
}

function handleParticipant(req, res) {
  let body = '';
  req.on('data', (chunk) => {
    body += chunk;
    if (body.length > 5000) req.destroy();
  });
  req.on('end', () => {
    let p;
    try { p = JSON.parse(body); } catch (e) { return json(res, 400, { error: 'Ungültige Daten.' }); }
    const name = typeof p.name === 'string' ? p.name.normalize('NFC').trim().replace(/\s+/g, ' ').slice(0, 120) : '';
    const email = typeof p.email === 'string' ? p.email.normalize('NFC').trim().toLowerCase().slice(0, 200) : '';
    const code = typeof p.code === 'string' && /^S2S-[A-Z0-9]{4}-[A-Z0-9]{4}$/.test(p.code) ? p.code : '';
    if (!name || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) || !code) {
      return json(res, 400, { error: 'Ungültige Teilnahme-Daten.' });
    }
    if (readAll().some((e) => e.email === email)) {
      return json(res, 409, { error: 'Mit dieser E-Mail-Adresse wurde bereits teilgenommen.' });
    }
    const entry = {
      id: crypto.randomUUID(),
      name,
      email,
      newsletter: p.newsletter === true,
      code,
      durationSec: Number.isFinite(p.durationSec) ? Math.round(p.durationSec) : null,
      mistakes: Number.isFinite(p.mistakes) ? Math.round(p.mistakes) : null,
      consentAt: new Date().toISOString()
    };
    fs.mkdirSync(DATA_DIR, { recursive: true });
    fs.appendFileSync(DATA_FILE, `${JSON.stringify(entry)}\n`);
    json(res, 201, { ok: true });
  });
}

function exportCsv(req, res, url) {
  if (!ADMIN_TOKEN || url.searchParams.get('token') !== ADMIN_TOKEN) return json(res, 403, { error: 'Kein Zugriff.' });
  const cols = ['consentAt', 'name', 'email', 'newsletter', 'code', 'durationSec', 'mistakes'];
  const cell = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`;
  const rows = [cols.join(';'), ...readAll().map((e) => cols.map((c) => cell(e[c])).join(';'))];
  res.writeHead(200, { 'Content-Type': 'text/csv; charset=utf-8', 'Content-Disposition': 'attachment; filename="teilnahmen.csv"' });
  res.end(`﻿${rows.join('\n')}`);
}

function serveStatic(req, res, url) {
  const rel = decodeURIComponent(url.pathname === '/' ? '/index.html' : url.pathname);
  const file = path.resolve(ROOT, `.${rel}`);
  const allowed = ['index.html', 'css', 'js', 'assets'].some((p) => file === path.join(ROOT, p) || file.startsWith(path.join(ROOT, p) + path.sep));
  if (!allowed) { res.writeHead(404); return res.end('Not found'); }
  fs.readFile(file, (err, data) => {
    if (err) { res.writeHead(404); return res.end('Not found'); }
    res.writeHead(200, { 'Content-Type': MIME[path.extname(file)] || 'application/octet-stream' });
    res.end(data);
  });
}

http.createServer((req, res) => {
  const url = new URL(req.url, `http://${req.headers.host || 'localhost'}`);
  if (url.pathname === '/api/participants' && req.method === 'POST') return handleParticipant(req, res);
  if (url.pathname === '/api/participants.csv' && req.method === 'GET') return exportCsv(req, res, url);
  if (req.method === 'GET' || req.method === 'HEAD') return serveStatic(req, res, url);
  res.writeHead(405);
  res.end();
}).listen(PORT, () => console.log(`Spark to Synergy läuft auf http://localhost:${PORT}`));
