/*
 * Spark to Synergy – Spiellogik
 * Inhalte & Zahlen stehen in js/content.js.
 */
(() => {
  'use strict';

  const C = window.S2S_CONTENT;
  const E = C.economy;
  const SECTORS = ['mensch', 'technik', 'natur'];
  const PAIRS = [['mensch', 'natur'], ['technik', 'natur'], ['mensch', 'technik']];
  const CX = 500, CY = 500;
  const RING_R = [430, 350, 276, 206, 140];
  const CORE_NODE_R = [33, 31, 32, 30, 28];
  // Winkelversatz für die zwei Äste in Ring 1 und 2 (slot a/b)
  const SLOT_OFFSET = [22, 19, 0, 0, 0];
  const CENTER_R = 60;
  const SAVE_KEY = 's2s-save-v2';
  const ROUND_KEY = 's2s-round';
  const SVG_NS = 'http://www.w3.org/2000/svg';

  const $ = (sel) => document.querySelector(sel);
  const svg = $('#board');

  /* ------------------------------------------------------------------ */
  /* Hilfsfunktionen                                                     */
  /* ------------------------------------------------------------------ */
  const polar = (r, deg) => {
    const a = (deg - 90) * Math.PI / 180;
    return { x: CX + r * Math.cos(a), y: CY + r * Math.sin(a) };
  };
  const el = (tag, attrs = {}, parent) => {
    const node = document.createElementNS(SVG_NS, tag);
    for (const [k, v] of Object.entries(attrs)) node.setAttribute(k, v);
    if (parent) parent.appendChild(node);
    return node;
  };
  const h = (tag, cls, html) => {
    const node = document.createElement(tag);
    if (cls) node.className = cls;
    if (html !== undefined) node.innerHTML = html;
    return node;
  };
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const nf1 = new Intl.NumberFormat('de-DE', { maximumFractionDigits: 1 });
  const nf0 = new Intl.NumberFormat('de-DE', { maximumFractionDigits: 0 });
  const fmt = (n) => (n < 100 ? nf1.format(Math.floor(n * 10) / 10) : nf0.format(Math.floor(n)));
  const shuffle = (arr) => {
    const a = arr.slice();
    for (let i = a.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
  };
  const midAngle = (a1, a2) => (Math.abs(a1 - a2) > 180 ? ((a1 + a2 + 360) / 2) % 360 : (a1 + a2) / 2);
  const sectorLabel = (s) => C.sectors[s].label;
  // Titel dürfen ein „|“ als Umbruchstelle für das Spielbrett enthalten
  const dn = (n) => n.title.replace('|', '');

  /* ------------------------------------------------------------------ */
  /* Knoten-Registry                                                     */
  /* ------------------------------------------------------------------ */
  const NODES = {};
  C.nodes.forEach((n) => {
    const sec = C.sectors[n.sector];
    const offset = n.slot === 'a' ? -SLOT_OFFSET[n.ring] : n.slot === 'b' ? SLOT_OFFSET[n.ring] : 0;
    const angle = sec.angle + offset;
    NODES[n.id] = {
      ...n, kind: 'core', angle, offset, ...polar(RING_R[n.ring], angle),
      r: CORE_NODE_R[n.ring], color: sec.color,
      cost: E.coreCost[n.ring], output: E.coreOutput[n.ring],
      requires: n.requires || []
    };
  });
  C.bridges.forEach((b) => {
    const angle = midAngle(C.sectors[b.sectors[0]].angle, C.sectors[b.sectors[1]].angle);
    const radius = RING_R[b.ring] - 30;
    NODES[b.id] = {
      ...b, kind: 'bridge', angle, offset: 0, ...polar(radius, angle),
      r: 27, color: C.sectors[b.sectors[0]].color, color2: C.sectors[b.sectors[1]].color,
      cost: E.bridgeCost[b.ring], output: E.bridgeOutput[b.ring]
    };
  });
  const CORE_IDS = C.nodes.map((n) => n.id);
  const BRIDGE_IDS = C.bridges.map((b) => b.id);
  const ALL_IDS = [...CORE_IDS, ...BRIDGE_IDS];
  const PER_SECTOR = CORE_IDS.filter((id) => NODES[id].sector === 'mensch').length;
  const RING_IDS = [0, 1, 2, 3, 4].map((r) => CORE_IDS.filter((id) => NODES[id].ring === r));

  /* ------------------------------------------------------------------ */
  /* Spielstand                                                          */
  /* ------------------------------------------------------------------ */
  const fresh = () => ({
    funken: 0, total: 0, solved: {}, answered: {}, level: {}, sp: 0,
    harmony: [false, false, false, false, false],
    mistakes: 0, taps: 0, start: Date.now(), last: Date.now(),
    won: false, wonAt: null, code: null, submitted: false, sound: true, seenHelp: false,
    pick: {}, round: readRound()
  });
  // Durchgang zählen: bei jedem neuen Spiel kommt aus jedem Aufgaben-Pool die nächste Aufgabe
  function readRound() { try { return Number(localStorage.getItem(ROUND_KEY)) || 0; } catch (e) { return 0; } }
  function nextRound() { try { localStorage.setItem(ROUND_KEY, String(readRound() + 1)); } catch (e) { /* ignorieren */ } }
  let S = fresh();
  try {
    const raw = localStorage.getItem(SAVE_KEY);
    if (raw) S = Object.assign(fresh(), JSON.parse(raw));
  } catch (e) { /* kein Speicher verfügbar */ }
  const taskIndex = (id) => {
    const pool = NODES[id].tasks;
    if (S.pick[id] === undefined) S.pick[id] = S.round % pool.length;
    return S.pick[id] % pool.length;
  };
  const taskFor = (id) => NODES[id].tasks[taskIndex(id)];
  const save = () => {
    S.last = Date.now();
    try { localStorage.setItem(SAVE_KEY, JSON.stringify(S)); } catch (e) { /* ignorieren */ }
  };

  /* ------------------------------------------------------------------ */
  /* Abgeleitete Werte                                                   */
  /* ------------------------------------------------------------------ */
  const counts = () => {
    const c = { mensch: 0, technik: 0, natur: 0 };
    CORE_IDS.forEach((id) => { if (S.solved[id]) c[NODES[id].sector]++; });
    return c;
  };
  const imbalance = (c = counts()) => Math.max(...Object.values(c)) - Math.min(...Object.values(c));
  const balanceFactor = () => E.balanceFactor[Math.min(imbalance(), 2)];
  const nodeOutput = (id, lvl = S.level[id] || 0) => NODES[id].output * (1 + E.upgradeOutputStep * lvl);
  const perSec = () => {
    let sum = 0;
    ALL_IDS.forEach((id) => { if (S.solved[id]) sum += nodeOutput(id); });
    return sum * balanceFactor();
  };
  const tapValue = () => E.tapBase + E.tapShare * perSec();
  const upgradeCost = (id) => Math.round(NODES[id].cost * 0.8 * Math.pow(E.upgradeCostFactor, (S.level[id] || 0) + 1));
  const bridgePairDone = (pair) => BRIDGE_IDS.some((id) => S.solved[id] &&
    NODES[id].sectors.includes(pair[0]) && NODES[id].sectors.includes(pair[1]));
  const allCoreDone = () => CORE_IDS.every((id) => S.solved[id]);
  const centerReady = () => allCoreDone() && PAIRS.every(bridgePairDone);
  const currentPhase = () => { const i = S.harmony.indexOf(false); return i === -1 ? 5 : i; };

  function wouldTip(id) {
    const n = NODES[id];
    if (n.kind !== 'core') return false;
    const c = counts();
    c[n.sector]++;
    return imbalance(c) > E.maxImbalance;
  }

  function status(id) {
    const n = NODES[id];
    if (S.solved[id]) return 'solved';
    if (!n.requires.every((r) => S.solved[r])) return 'locked';
    if (S.sp < E.ringGate[n.ring]) return 'gated';
    if (wouldTip(id)) return 'blocked';
    return S.funken >= n.cost ? 'ready' : 'visible';
  }

  /* ------------------------------------------------------------------ */
  /* Ton                                                                 */
  /* ------------------------------------------------------------------ */
  let actx = null;
  function tone(freq, dur = 0.12, type = 'sine', vol = 0.06, delay = 0) {
    if (!S.sound) return;
    try {
      actx = actx || new (window.AudioContext || window.webkitAudioContext)();
      const t = actx.currentTime + delay;
      const o = actx.createOscillator();
      const g = actx.createGain();
      o.type = type; o.frequency.value = freq;
      g.gain.setValueAtTime(0.0001, t);
      g.gain.exponentialRampToValueAtTime(vol, t + 0.01);
      g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
      o.connect(g).connect(actx.destination);
      o.start(t); o.stop(t + dur + 0.05);
    } catch (e) { /* Audio nicht verfügbar */ }
  }
  const sfx = {
    tap: () => tone(700 + Math.random() * 300, 0.07, 'triangle', 0.035),
    right: () => { tone(523, 0.12); tone(659, 0.12, 'sine', 0.06, 0.08); tone(784, 0.2, 'sine', 0.06, 0.16); },
    wrong: () => { tone(220, 0.18, 'sawtooth', 0.03); tone(180, 0.22, 'sawtooth', 0.03, 0.1); },
    unlock: () => [392, 523, 659, 784, 1046].forEach((f, i) => tone(f, 0.25, 'triangle', 0.05, i * 0.06)),
    harmony: () => [523, 659, 784].forEach((f) => tone(f, 0.8, 'sine', 0.04)),
    win: () => [262, 330, 392, 523, 659, 784, 1046, 1318].forEach((f, i) => tone(f, 1.2, 'sine', 0.045, i * 0.12))
  };

  /* ------------------------------------------------------------------ */
  /* Toasts                                                              */
  /* ------------------------------------------------------------------ */
  function toast(html, kind = '') {
    const t = h('div', `toast ${kind}`, html);
    $('#toasts').appendChild(t);
    setTimeout(() => t.remove(), 4200);
    while ($('#toasts').children.length > 3) $('#toasts').firstChild.remove();
  }

  /* ------------------------------------------------------------------ */
  /* Spielbrett aufbauen                                                 */
  /* ------------------------------------------------------------------ */
  const nodeEls = {};
  const linkEls = [];
  const ringEls = [];
  let centerEl = null;
  let plate = null;
  const tiltNow = { x: 0, y: 0 };
  const tiltTarget = { x: 0, y: 0 };

  function arcPath(r, a0, a1) {
    // Für den unteren Teil des Kreises Text gegen den Uhrzeigersinn führen, damit er lesbar bleibt
    const m = ((a0 + a1) / 2 + 360) % 360;
    const bottom = m > 90 && m < 270;
    const p0 = polar(r, bottom ? a1 : a0);
    const p1 = polar(r, bottom ? a0 : a1);
    return `M ${p0.x} ${p0.y} A ${r} ${r} 0 0 ${bottom ? 0 : 1} ${p1.x} ${p1.y}`;
  }

  function wedgePath(r, a0, a1) {
    const p0 = polar(r, a0), p1 = polar(r, a1);
    return `M ${CX} ${CY} L ${p0.x} ${p0.y} A ${r} ${r} 0 0 1 ${p1.x} ${p1.y} Z`;
  }

  /* Beschriftungen so platzieren, dass sie sich möglichst nicht überlappen */
  const LABELS = {};
  function placeLabels() {
    const LH = 18;
    const obstacles = ALL_IDS.map((id) => {
      const n = NODES[id];
      const R = n.r + 12;
      return { x0: n.x - R, y0: n.y - R, x1: n.x + R, y1: n.y + R };
    });
    obstacles.push({ x0: CX - 70, y0: CY - 70, x1: CX + 70, y1: CY + 70 });
    // Bereichsnamen am Rand freihalten
    SECTORS.forEach((sec) => {
      const p = polar(492, C.sectors[sec].angle);
      obstacles.push({ x0: p.x - 95, y0: p.y - 22, x1: p.x + 95, y1: p.y + 22, label: true });
    });
    const area = (a, b) => Math.max(0, Math.min(a.x1, b.x1) - Math.max(a.x0, b.x0)) * Math.max(0, Math.min(a.y1, b.y1) - Math.max(a.y0, b.y0));
    const order = [...CORE_IDS.slice().sort((a, b) => NODES[a].ring - NODES[b].ring), ...BRIDGE_IDS];
    order.forEach((id) => {
      const n = NODES[id];
      const parts = n.title.split('|');
      const lines = parts.length < 2 ? [n.title]
        : parts[1].startsWith(' ') ? [parts[0], parts[1].trim()]
          : [parts[0].endsWith('-') ? parts[0] : `${parts[0]}-`, parts[1]];
      const w = Math.max(...lines.map((l) => l.length * 9.4), 64);
      const hgt = (lines.length + 1) * LH;
      const gap = n.r + 8;
      // Bevorzugte Richtung: zur Mittellinie des Bereichs bzw. bei Brücken nach außen
      const tangentX = -Math.sin((n.angle - 90) * Math.PI / 180) * (n.offset < 0 ? -1 : 1);
      const prefRight = n.kind === 'core' ? tangentX >= 0 : n.x >= CX;
      const cands = [
        { k: 'right', dir: { x: 1, y: 0 }, box: { x0: n.x + gap, x1: n.x + gap + w, y0: n.y - hgt / 2, y1: n.y + hgt / 2 }, anchor: 'start', x: gap, y: -hgt / 2 + 14 },
        { k: 'left', dir: { x: -1, y: 0 }, box: { x0: n.x - gap - w, x1: n.x - gap, y0: n.y - hgt / 2, y1: n.y + hgt / 2 }, anchor: 'end', x: -gap, y: -hgt / 2 + 14 },
        { k: 'below', dir: { x: 0, y: 1 }, box: { x0: n.x - w / 2, x1: n.x + w / 2, y0: n.y + n.r + 4, y1: n.y + n.r + 4 + hgt }, anchor: 'middle', x: 0, y: n.r + 18 },
        { k: 'above', dir: { x: 0, y: -1 }, box: { x0: n.x - w / 2, x1: n.x + w / 2, y0: n.y - n.r - 4 - hgt, y1: n.y - n.r - 4 }, anchor: 'middle', x: 0, y: -n.r - hgt + 10 }
      ];
      if (!prefRight) [cands[0], cands[1]] = [cands[1], cands[0]];
      let best = null;
      cands.forEach((c, i) => {
        let score = i * 5;
        obstacles.forEach((o) => { score += area(c.box, o) * (o.label ? 2 : 1); });
        // nicht über den Bildrand hinaus
        score += (Math.max(0, -10 - c.box.x0) + Math.max(0, c.box.x1 - 1010) + Math.max(0, -10 - c.box.y0) + Math.max(0, c.box.y1 - 1010)) * 400;
        if (!best || score < best.score) best = { ...c, score };
      });
      obstacles.push({ ...best.box, label: true });
      LABELS[id] = { lines, lh: LH, anchor: best.anchor, x: best.x, y: best.y, dir: best.dir };
    });
  }

  function buildBoard() {
    svg.innerHTML = '';
    placeLabels();
    const defs = el('defs', {}, svg);
    defs.innerHTML = `
      <radialGradient id="g-center" cx="50%" cy="50%" r="50%">
        <stop offset="0%" stop-color="#fff6d8"/><stop offset="45%" stop-color="#E9C46A"/><stop offset="100%" stop-color="#E9C46A" stop-opacity="0"/>
      </radialGradient>
      <radialGradient id="g-glow" cx="50%" cy="50%" r="50%">
        <stop offset="0%" stop-color="#E9C46A" stop-opacity="0.35"/><stop offset="100%" stop-color="#E9C46A" stop-opacity="0"/>
      </radialGradient>
      <filter id="f-glow" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="6" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter>`;
    SECTORS.forEach((s) => {
      defs.insertAdjacentHTML('beforeend', `<radialGradient id="g-w-${s}" cx="500" cy="500" r="470" gradientUnits="userSpaceOnUse">
        <stop offset="0%" stop-color="${C.sectors[s].color}" stop-opacity="0.02"/><stop offset="100%" stop-color="${C.sectors[s].color}" stop-opacity="0.16"/></radialGradient>`);
    });
    BRIDGE_IDS.forEach((id) => {
      const n = NODES[id];
      defs.insertAdjacentHTML('beforeend', `<linearGradient id="g-${id}" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0%" stop-color="${n.color}"/><stop offset="100%" stop-color="${n.color2}"/></linearGradient>`);
    });

    // Sterne im Hintergrund
    const stars = el('g', { opacity: 0.5, 'pointer-events': 'none' }, svg);
    for (let i = 0; i < 70; i++) {
      el('circle', { cx: Math.random() * 1000, cy: Math.random() * 1000, r: Math.random() * 1.6 + 0.3, fill: '#cfe6dc', opacity: Math.random() * 0.6 + 0.2 }, stars);
    }

    // Sektoren (Bamboleo-Scheibe)
    const disc = el('g', { 'pointer-events': 'none' }, svg);
    el('circle', { cx: CX, cy: CY, r: 470, fill: 'rgba(255,255,255,0.015)', stroke: 'rgba(255,255,255,0.08)', 'stroke-width': 2 }, disc);
    SECTORS.forEach((s) => {
      const a = C.sectors[s].angle;
      el('path', { d: wedgePath(470, a - 60, a + 60), fill: `url(#g-w-${s})`, class: `wedge wedge-${s}` }, disc);
      const border = polar(470, a + 60);
      el('line', { x1: CX, y1: CY, x2: border.x, y2: border.y, stroke: 'rgba(255,255,255,0.07)', 'stroke-width': 2 }, disc);
      const pathId = `lbl-${s}`;
      el('path', { id: pathId, d: arcPath(496, a - 40, a + 40), fill: 'none' }, disc);
      const t = el('text', { class: 'sector-label', fill: C.sectors[s].color, dy: 8 }, disc);
      const tp = el('textPath', { href: `#${pathId}`, startOffset: '50%' }, t);
      tp.textContent = C.sectors[s].label.toUpperCase();
    });
    el('circle', { cx: CX, cy: CY, r: 478, fill: 'none', stroke: 'rgba(233,196,106,0.18)', 'stroke-width': 1.5, 'stroke-dasharray': '2 14', class: 'spin-slow' }, disc);

    // Schattierung für die Bamboleo-Neigung
    defs.insertAdjacentHTML('beforeend', `<linearGradient id="g-shade" gradientUnits="userSpaceOnUse" x1="${CX}" y1="${CY - 470}" x2="${CX}" y2="${CY + 470}">
      <stop offset="0%" stop-color="#000" stop-opacity="0"/><stop offset="100%" stop-color="#000" stop-opacity="0"/></linearGradient>`);
    el('circle', { cx: CX, cy: CY, r: 470, fill: 'url(#g-shade)', 'pointer-events': 'none' }, disc);

    // Ringe
    RING_R.forEach((r, i) => {
      ringEls[i] = el('circle', { cx: CX, cy: CY, r, fill: 'none', 'pointer-events': 'none', stroke: '#1f3a32', 'stroke-width': 2, 'stroke-dasharray': '4 10', class: 'ring' }, svg);
    });
    // Verbindungen
    const links = el('g', { 'pointer-events': 'none' }, svg);
    const addLink = (a, b, color, dashed) => {
      const pa = a === 'center' ? { x: CX, y: CY } : NODES[a];
      const pb = NODES[b];
      const line = el('line', { x1: pa.x, y1: pa.y, x2: pb.x, y2: pb.y, class: 'link', stroke: color, 'stroke-width': 3, 'stroke-linecap': 'round', 'stroke-dasharray': dashed ? '5 7' : '0' }, links);
      linkEls.push({ line, a, b, color });
    };
    CORE_IDS.forEach((id) => {
      const n = NODES[id];
      n.requires.forEach((r) => addLink(r, id, n.color, false));
      if (n.ring === 4) addLink('center', id, n.color, false);
    });
    BRIDGE_IDS.forEach((id) => NODES[id].requires.forEach((r) => addLink(r, id, NODES[r].color, true)));

    // Zentrum
    centerEl = el('g', { class: 'core-center', tabindex: 0, role: 'button', 'aria-label': 'Synergie' }, svg);
    el('circle', { cx: CX, cy: CY, r: 110, fill: 'url(#g-glow)', class: 'core-halo halo' }, centerEl);
    el('circle', { cx: CX, cy: CY, r: CENTER_R, fill: 'rgba(12,27,23,0.9)', stroke: '#E9C46A', 'stroke-width': 3, class: 'core-body' }, centerEl);
    SECTORS.forEach((s, i) => {
      const p = polar(20, C.sectors[s].angle);
      el('circle', { cx: p.x, cy: p.y - 8, r: 17, fill: C.sectors[s].color, opacity: 0.25, class: `core-dot core-dot-${i}` }, centerEl);
    });
    const ct = el('text', { x: CX, y: CY + 4, 'text-anchor': 'middle', fill: '#E9C46A', 'font-size': 15, 'letter-spacing': 3, 'font-weight': 700 }, centerEl);
    ct.textContent = 'SYNERGIE';
    const cs = el('text', { x: CX, y: CY + 26, 'text-anchor': 'middle', fill: '#e6f2ed', 'font-size': 13, class: 'core-sub', opacity: 0.8 }, centerEl);
    cs.textContent = '';
    centerEl.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); onCenterClick(); } });

    // Knoten
    const nodesG = el('g', {}, svg);
    ALL_IDS.forEach((id) => {
      const n = NODES[id];
      const g = el('g', { class: `node ${n.kind}`, tabindex: 0, role: 'button', 'data-id': id, transform: `translate(${n.x} ${n.y})` }, nodesG);
      el('circle', { r: n.r + 14, fill: n.kind === 'bridge' ? `url(#g-${id})` : n.color, opacity: 0, class: 'halo' }, g);
      if (n.kind === 'bridge') {
        const pts = [];
        for (let i = 0; i < 6; i++) {
          const a = Math.PI / 3 * i + Math.PI / 6;
          pts.push(`${(n.r + 4) * Math.cos(a)},${(n.r + 4) * Math.sin(a)}`);
        }
        el('polygon', { points: pts.join(' '), class: 'body', fill: 'rgba(12,27,23,0.92)', stroke: `url(#g-${id})`, 'stroke-width': 3 }, g);
      } else {
        el('circle', { r: n.r, class: 'body', fill: 'rgba(12,27,23,0.92)', stroke: n.color, 'stroke-width': 3 }, g);
      }
      const ico = el('text', { class: 'ico', y: 2, style: `font-size:${Math.round(n.r * 0.95)}px` }, g);
      ico.textContent = n.icon;
      // Beschriftung: freie Seite wählen (siehe placeLabels)
      const L = LABELS[id];
      const lbl = el('text', { class: 'lbl', 'text-anchor': L.anchor }, g);
      L.lines.forEach((line, i) => {
        const t = el('tspan', { x: L.x, y: L.y + i * L.lh }, lbl);
        t.textContent = line;
      });
      el('text', { class: 'cost', x: L.x, y: L.y + L.lines.length * L.lh, 'text-anchor': L.anchor }, g);
      const pips = el('g', { class: 'pips' }, g);
      const base = Math.atan2(-L.dir.y, -L.dir.x);
      for (let i = 0; i < E.maxUpgrade; i++) {
        const a = base + (i - (E.maxUpgrade - 1) / 2) * 22 * Math.PI / 180;
        el('circle', { cx: (n.r + 8) * Math.cos(a), cy: (n.r + 8) * Math.sin(a), r: 3.4, fill: 'rgba(255,255,255,0.15)' }, pips);
      }
      g.setAttribute('aria-label', `${dn(n)}${n.kind === 'bridge' ? ' (Brücke)' : ''}`);
      g.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openNode(id); } });
      nodeEls[id] = { g, body: g.querySelector('.body'), cost: g.querySelector('.cost'), pips: [...pips.children], lastState: '' };
    });

    // Alles außer Hintergrund in die „Bamboleo-Scheibe“ legen, die sich neigt (2D-SVG-Transform: überall zuverlässig klickbar)
    plate = el('g', { id: 'plate' });
    [...svg.children].filter((c) => c !== defs && c !== stars).forEach((c) => plate.appendChild(c));
    svg.appendChild(plate);
  }

  /* Ein zentraler Klick-Handler: öffnet den getroffenen oder nächstgelegenen Knoten */
  function svgPoint(clientX, clientY) {
    const m = plate && plate.getScreenCTM();
    if (!m) return null;
    const pt = svg.createSVGPoint();
    pt.x = clientX; pt.y = clientY;
    return pt.matrixTransform(m.inverse());
  }
  svg.addEventListener('click', (e) => {
    const t = e.target;
    const g = t && t.closest ? t.closest('g.node') : null;
    if (g) { openNode(g.dataset.id); return; }
    if (t && t.closest && t.closest('.core-center')) { onCenterClick(); return; }
    const p = svgPoint(e.clientX, e.clientY);
    if (!p) return;
    let best = null, bestD = Infinity;
    ALL_IDS.forEach((id) => {
      const n = NODES[id];
      const d = Math.hypot(n.x - p.x, n.y - p.y);
      if (d < n.r + 24 && d < bestD) { best = id; bestD = d; }
    });
    if (best) openNode(best);
    else if (Math.hypot(p.x - CX, p.y - CY) < CENTER_R + 20) onCenterClick();
  });

  /* ------------------------------------------------------------------ */
  /* Darstellung aktualisieren                                           */
  /* ------------------------------------------------------------------ */
  function refreshBoard() {
    ALL_IDS.forEach((id) => {
      const n = NODES[id];
      const ne = nodeEls[id];
      const st = status(id);
      const answered = !!S.answered[id];
      const key = `${st}|${S.level[id] || 0}|${answered}`;
      if (key === ne.lastState) return;
      ne.lastState = key;
      ne.g.setAttribute('class', `node ${n.kind} ${st === 'visible' ? 'visible poor' : st === 'gated' || st === 'blocked' ? `visible ${st}` : st}`);
      const solved = st === 'solved';
      ne.body.setAttribute('fill', solved ? (n.kind === 'bridge' ? `url(#g-${id})` : n.color) : 'rgba(12,27,23,0.92)');
      ne.body.setAttribute('fill-opacity', solved ? 0.85 : 1);
      ne.body.setAttribute('stroke', solved ? '#E9C46A' : (n.kind === 'bridge' ? `url(#g-${id})` : n.color));
      ne.body.setAttribute('stroke-width', solved ? 4 : 3);
      let costText = '';
      if (st === 'ready' || st === 'visible') costText = `${answered ? '✓ ' : ''}${fmt(n.cost)} ✨`;
      else if (st === 'gated') costText = `${E.ringGate[n.ring]} ✦ nötig`;
      else if (st === 'blocked') costText = '⚖️ Balance!';
      ne.cost.textContent = costText;
      ne.pips.forEach((p, i) => {
        p.style.display = solved ? '' : 'none';
        p.setAttribute('fill', i < (S.level[id] || 0) ? '#E9C46A' : 'rgba(255,255,255,0.18)');
      });
    });

    linkEls.forEach((l) => {
      const aDone = l.a === 'center' ? S.won : !!S.solved[l.a];
      const bDone = !!S.solved[l.b];
      const both = aDone && bDone;
      l.line.setAttribute('stroke', S.won ? '#E9C46A' : l.color);
      l.line.setAttribute('opacity', both ? 0.95 : (aDone || bDone) ? 0.45 : 0.1);
      l.line.setAttribute('stroke-width', both ? 4 : 2.5);
    });

    ringEls.forEach((r, i) => {
      r.setAttribute('stroke', S.harmony[i] ? 'rgba(233,196,106,0.55)' : '#1f3a32');
      r.setAttribute('stroke-dasharray', S.harmony[i] ? '0' : '4 10');
    });

    const ready = centerReady();
    centerEl.setAttribute('class', `core-center ${ready || S.won ? 'ready' : ''}`);
    const done = ALL_IDS.filter((id) => S.solved[id]).length;
    centerEl.querySelector('.core-sub').textContent = S.won ? '✦ erreicht ✦' : ready ? 'bereit!' : `${done} / ${ALL_IDS.length}`;
    centerEl.querySelector('.core-body').setAttribute('fill', S.won ? 'url(#g-center)' : 'rgba(12,27,23,0.9)');
    const c = counts();
    SECTORS.forEach((s, i) => centerEl.querySelector(`.core-dot-${i}`).setAttribute('opacity', 0.15 + 0.17 * c[s]));

    updateTilt();
  }

  function updateTilt() {
    const c = counts();
    let vx = 0, vy = 0;
    SECTORS.forEach((sec) => {
      const a = C.sectors[sec].angle * Math.PI / 180;
      vx += c[sec] * Math.sin(a);
      vy += -c[sec] * Math.cos(a);
    });
    // Neigungsvektor zur schwersten Seite (0 = waagerecht, max. 1)
    const mag = Math.min(Math.hypot(vx, vy) / 2.5, 1);
    const celebrating = document.body.classList.contains('celebrate') || S.won;
    const len = Math.hypot(vx, vy) || 1;
    tiltTarget.x = celebrating ? 0 : (vx / len) * mag;
    tiltTarget.y = celebrating ? 0 : (vy / len) * mag;
    $('#board-tilt').classList.toggle('wobble', imbalance(c) >= 2);
  }

  // Neigung weich animieren: Scheibe wird zur schweren Seite hin verkürzt, verschoben und abgedunkelt
  function applyTilt() {
    const dx = tiltTarget.x - tiltNow.x, dy = tiltTarget.y - tiltNow.y;
    if (!plate || (Math.abs(dx) < 0.0005 && Math.abs(dy) < 0.0005 && tiltNow.done)) return;
    const settle = Math.abs(dx) < 0.0005 && Math.abs(dy) < 0.0005;
    tiltNow.x = settle ? tiltTarget.x : tiltNow.x + dx * 0.08;
    tiltNow.y = settle ? tiltTarget.y : tiltNow.y + dy * 0.08;
    tiltNow.done = settle;
    const mag = Math.hypot(tiltNow.x, tiltNow.y);
    if (mag < 0.002) { plate.removeAttribute('transform'); return; }
    const ux = tiltNow.x / mag, uy = tiltNow.y / mag;
    const ang = Math.atan2(uy, ux) * 180 / Math.PI;
    const sc = 1 - 0.09 * mag;
    const shift = 22 * mag;
    plate.setAttribute('transform', `translate(${(CX + ux * shift).toFixed(2)} ${(CY + uy * shift).toFixed(2)}) rotate(${ang.toFixed(2)}) scale(${sc.toFixed(4)} 1) rotate(${(-ang).toFixed(2)}) translate(${-CX} ${-CY})`);
    const grad = svg.querySelector('#g-shade');
    if (grad) {
      grad.setAttribute('x1', CX - ux * 470); grad.setAttribute('y1', CY - uy * 470);
      grad.setAttribute('x2', CX + ux * 470); grad.setAttribute('y2', CY + uy * 470);
      grad.children[1].setAttribute('stop-opacity', (0.38 * mag).toFixed(3));
    }
  }


  function refreshPanel() {
    const ps = perSec();
    $('#p-funken').textContent = fmt(S.funken);
    $('#hud-funken').textContent = fmt(S.funken);
    $('#p-rate').textContent = fmt(ps);
    $('#p-tap').textContent = `+${fmt(tapValue())} pro Klick`;
    $('#p-sp').textContent = S.sp;
    $('#hud-sp').textContent = S.sp;
    const bf = balanceFactor();
    const f = $('#p-factor');
    f.textContent = S.solved && Object.keys(S.solved).length ? `×${nf1.format(bf)}` : '';
    f.className = `factor ${bf < 1 ? 'bad' : ''}`;
  }

  function refreshSide() {
    const c = counts();
    const diff = imbalance(c);
    const bars = $('#balance-bars');
    bars.innerHTML = '';
    SECTORS.forEach((s) => {
      const row = h('div', 'bal-row');
      row.innerHTML = `<span style="color:${C.sectors[s].color}">${sectorLabel(s)}</span>
        <div class="bal-track"><div class="bal-fill" style="width:${(c[s] / PER_SECTOR) * 100}%;background:${C.sectors[s].color}"></div></div>
        <span class="bal-num">${c[s]}/${PER_SECTOR}</span>`;
      bars.appendChild(row);
    });
    const badge = $('#balance-badge');
    const text = $('#balance-text');
    const total = c.mensch + c.natur + c.technik;
    const min = Math.min(...Object.values(c));
    const behind = SECTORS.filter((s) => c[s] === min).map(sectorLabel).join(' & ');
    if (diff === 0) {
      badge.textContent = total ? 'Harmonie ×1,25' : 'ruhig';
      badge.className = 'badge good';
      text.textContent = total ? 'Perfekt ausbalanciert – alle Bereiche erhalten einen Harmonie-Bonus.' : 'Entwickle alle drei Bereiche gleichmäßig – sonst kippt das Brett.';
    } else if (diff === 1) {
      badge.textContent = 'stabil ×1,0';
      badge.className = 'badge';
      text.textContent = `Leicht geneigt. Für den Harmonie-Bonus: ${behind} weiterentwickeln.`;
    } else {
      badge.textContent = 'wackelt ×0,6';
      badge.className = 'badge warn';
      text.textContent = `Das Brett wackelt! ${behind} ist zu weit zurück. Mehr Unterschied lässt das Bamboleo nicht zu.`;
    }

    const phase = currentPhase();
    const pl = $('#phase-list');
    pl.innerHTML = '';
    C.rings.forEach((r, i) => {
      const li = h('li', S.harmony[i] ? 'done' : i === phase ? 'current' : '');
      const gate = E.ringGate[i] && S.sp < E.ringGate[i] ? `<span class="gate">🔒 ${E.ringGate[i]} ✦</span>` : '';
      li.innerHTML = `<span class="num">${S.harmony[i] ? '✓' : i + 1}</span><span>${r.name}</span>${gate}`;
      li.title = r.text;
      pl.appendChild(li);
    });
    const liS = h('li', S.won ? 'done' : phase === 5 ? 'current' : '');
    liS.innerHTML = `<span class="num">${S.won ? '✓' : '✦'}</span><span>Synergie</span>`;
    pl.appendChild(liS);

    const gl = $('#goal-list');
    gl.innerHTML = '';
    const goal = (ok, label) => {
      const li = h('li', ok ? 'ok' : '');
      li.innerHTML = `<span class="tick">${ok ? '✅' : '◻️'}</span><span>${label}</span>`;
      gl.appendChild(li);
    };
    SECTORS.forEach((s) => goal(c[s] === PER_SECTOR, `${sectorLabel(s)}: alle ${PER_SECTOR} Knoten <span class="muted">(${c[s]}/${PER_SECTOR})</span>`));
    PAIRS.forEach((p) => goal(bridgePairDone(p), `Brücke ${sectorLabel(p[0])} ∩ ${sectorLabel(p[1])}`));
    goal(S.won, 'Letzte Aufgabe im Zentrum');

    $('#board-hint').innerHTML = hint();
  }

  function hint() {
    if (S.won) return '✦ Du hast die Synergie erreicht! Tippe auf das Zentrum, um deinen Gewinn-Dialog erneut zu öffnen.';
    if (centerReady()) return '🌟 Das Zentrum leuchtet – löse die letzte Aufgabe!';
    const solvedCount = Object.keys(S.solved).length;
    if (!solvedCount) {
      return S.funken < E.coreCost[0]
        ? `Schlage ${E.coreCost[0]} Funken, dann löse einen der drei äußeren Knoten.`
        : 'Tippe einen leuchtenden äußeren Knoten an und löse die Aufgabe.';
    }
    if (imbalance() >= 2) return '⚖️ Das Brett wackelt – entwickle den Bereich, der zurückliegt.';
    const gated = ALL_IDS.find((id) => status(id) === 'gated');
    if (gated) {
      const need = E.ringGate[NODES[gated].ring];
      return `✦ Für den Ring „${C.rings[NODES[gated].ring].name}“ brauchst du ${need} Synergiepunkte – löse Brücken (⬡) oder schließe Ringe in allen Bereichen ab.`;
    }
    const ready = ALL_IDS.filter((id) => status(id) === 'ready');
    if (ready.length) return `Bereit: ${ready.map((id) => dn(NODES[id])).join(', ')}`;
    return 'Sammle Funken – gelöste Knoten produzieren automatisch. Tipp: Gelöste Knoten lassen sich stärken.';
  }

  function refreshAll() {
    refreshBoard();
    refreshPanel();
    refreshSide();
    refreshOpenModal();
  }

  /* ------------------------------------------------------------------ */
  /* Modals                                                              */
  /* ------------------------------------------------------------------ */
  let openId = null;

  function showModal(id) {
    $(id).hidden = false;
    const focusable = $(id).querySelector('button, input, a');
    if (focusable) setTimeout(() => focusable.focus({ preventScroll: true }), 50);
  }
  function hideModal(modal) {
    modal.hidden = true;
    if (modal.id === 'task-modal') openId = null;
  }
  document.querySelectorAll('.modal').forEach((m) => {
    m.addEventListener('click', (e) => {
      if (e.target === m || e.target.closest('[data-close]')) hideModal(m);
    });
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') document.querySelectorAll('.modal:not([hidden])').forEach(hideModal);
  });

  function setTaskHeader(n, meta) {
    const icon = $('#task-icon');
    icon.textContent = n.icon;
    icon.style.borderColor = n.color;
    icon.style.background = n.kind === 'bridge' ? `linear-gradient(135deg, ${n.color}33, ${n.color2}33)` : `${n.color}22`;
    $('#task-meta').textContent = meta;
    $('#task-title').textContent = dn(n);
    $('#task-title').style.color = n.color;
    $('#task-blurb').textContent = n.blurb || '';
  }

  function nodeMeta(n) {
    if (n.kind === 'bridge') return `Brücke · ${sectorLabel(n.sectors[0])} ∩ ${sectorLabel(n.sectors[1])} · Sonderaufgabe`;
    return `${sectorLabel(n.sector)} · Ring ${n.ring + 1}: ${C.rings[n.ring].name}`;
  }

  function openNode(id) {
    const n = NODES[id];
    const st = status(id);
    if (st === 'locked') {
      const missing = n.requires.filter((r) => !S.solved[r]).map((r) => dn(NODES[r]));
      toast(`🔒 <b>${esc(dn(n))}</b> ist noch verschlossen. Löse zuerst: ${esc(missing.join(' und '))}.`);
      return;
    }
    openId = id;
    setTaskHeader(n, nodeMeta(n));
    renderNodeBody(id);
    showModal('#task-modal');
  }

  function renderNodeBody(id) {
    const n = NODES[id];
    const st = status(id);
    const body = $('#task-body');
    body.innerHTML = '';
    body.dataset.state = st;

    if (st === 'solved') {
      const lvl = S.level[id] || 0;
      const box = h('div', 'info-box');
      box.innerHTML = `<b>✓ Gelöst.</b> Dieser Knoten erzeugt jetzt <b>${fmt(nodeOutput(id) * balanceFactor())} Funken/Sek.</b>`;
      body.appendChild(box);
      const learn = h('div', 'info-box', `<b>Das hast du gelernt:</b><br>${esc(taskFor(id).fact)}`);
      body.appendChild(learn);
      const up = h('div', 'info-box');
      if (lvl >= E.maxUpgrade) {
        up.innerHTML = `<b>⬆ Stärke ${lvl}/${E.maxUpgrade}</b> – maximal gestärkt!`;
        body.appendChild(up);
      } else {
        const cost = upgradeCost(id);
        up.innerHTML = `<b>⬆ Knoten stärken</b> (Stufe ${lvl}/${E.maxUpgrade})
          <div class="upgrade-row"><span>Produktion</span><span>${fmt(nodeOutput(id))} → ${fmt(nodeOutput(id, lvl + 1))} /Sek.</span></div>`;
        body.appendChild(up);
        const foot = h('div', 'task-footer');
        const btn = h('button', 'btn btn-primary', `Stärken für ${fmt(cost)} ✨`);
        btn.dataset.cost = cost;
        btn.disabled = S.funken < cost;
        btn.addEventListener('click', () => upgrade(id));
        foot.appendChild(btn);
        body.appendChild(foot);
      }
      return;
    }

    if (st === 'gated') {
      body.appendChild(h('div', 'info-box', `✦ Für den Ring <b>„${C.rings[n.ring].name}“</b> brauchst du <b>${E.ringGate[n.ring]} Synergiepunkte</b> (du hast ${S.sp}).<br><br>
        Synergiepunkte bekommst du, wenn ein Ring in <i>allen drei</i> Bereichen fertig ist, und für jede gelöste Brücke (⬡) zwischen zwei Bereichen.`));
      return;
    }
    if (st === 'blocked') {
      const c = counts();
      const min = Math.min(...Object.values(c));
      const behind = SECTORS.filter((s) => c[s] === min).map(sectorLabel).join(' und ');
      body.appendChild(h('div', 'info-box', `⚖️ <b>Das Bamboleo-Brett würde kippen!</b><br>${esc(sectorLabel(n.sector))} ist schon weit voraus. Entwickle zuerst <b>${esc(behind)}</b> weiter – Synergie entsteht nur im Gleichgewicht.`));
      return;
    }

    if (S.answered[id]) {
      body.appendChild(h('div', 'fact', `<span class="eyebrow">✓ Aufgabe gelöst · 💡 Wusstest du?</span><p>${esc(taskFor(id).fact)}</p>`));
      body.appendChild(unlockFooter(id));
      return;
    }

    renderTask(body, taskFor(id), () => {
      S.answered[id] = true;
      save();
      body.appendChild(unlockFooter(id));
      refreshBoard();
    }, n.tasks.length > 1 ? {
      onSwap: () => { S.pick[id] = taskIndex(id) + 1; save(); renderNodeBody(id); }
    } : {});
  }

  function unlockFooter(id) {
    const n = NODES[id];
    const foot = h('div', 'task-footer');
    const line = h('span', 'costline', '');
    const btn = h('button', 'btn btn-primary unlock-btn', `Freischalten für ${fmt(n.cost)} ✨`);
    btn.dataset.cost = n.cost;
    const update = () => {
      const missing = n.cost - S.funken;
      btn.disabled = missing > 0;
      line.textContent = missing > 0 ? `Noch ${fmt(Math.ceil(missing))} Funken sammeln …` : 'Genug Funken!';
    };
    update();
    btn._update = update;
    btn.addEventListener('click', () => unlock(id));
    foot.appendChild(line);
    foot.appendChild(btn);
    return foot;
  }

  function refreshOpenModal() {
    if (!openId || openId === 'center' || $('#task-modal').hidden) return;
    const body = $('#task-body');
    const st = status(openId);
    if (body.dataset.state !== st && !(body.dataset.state === 'ready' && st === 'visible') && !(body.dataset.state === 'visible' && st === 'ready')) {
      if (!body.querySelector('.task-ui') || S.answered[openId]) renderNodeBody(openId);
      return;
    }
    body.querySelectorAll('.unlock-btn').forEach((b) => b._update && b._update());
    body.querySelectorAll('.task-footer .btn[data-cost]:not(.unlock-btn)').forEach((b) => { b.disabled = S.funken < Number(b.dataset.cost); });
  }

  /* ---------- Aufgaben ---------- */
  const DEBUG = { fastBreath: false };

  // Gemeinsamer Ablauf aller Aufgabentypen: Antwort geben → „Prüfen“ → Feedback bzw. „Wusstest du?“
  function renderTask(container, task, onSolved, opts = {}) {
    const q = h('p', 'task-q', esc(task.q));
    const ui = h('div', 'task-ui');
    const fb = h('div', 'task-fb');
    const foot = h('div', 'task-footer check-foot');
    container.append(q, ui, fb, foot);
    const view = VIEWS[task.type](task, { fb, q });
    view.mount(ui);
    const note = h('span', 'costline', task.note ? esc(task.note) : '');
    foot.appendChild(note);
    if (opts.onSwap) {
      const swap = h('button', 'btn btn-ghost', '🔄 Andere Aufgabe');
      swap.type = 'button';
      swap.title = 'Eine andere Aufgabe für diesen Knoten';
      swap.addEventListener('click', opts.onSwap);
      foot.appendChild(swap);
    }
    const check = h('button', 'btn btn-primary', view.checkLabel || 'Prüfen');
    check.type = 'button';
    foot.appendChild(check);
    const sync = () => { check.disabled = !view.ready(); };
    view.onChange = sync;
    sync();
    check.addEventListener('click', () => {
      if (!view.ready()) return;
      const ok = view.check();
      if (ok === null) { fb.innerHTML = ''; sync(); return; }
      if (ok) {
        foot.remove();
        if (view.lock) view.lock();
        fb.innerHTML = `<div class="feedback ok"><b>${task.type === 'reflect' || task.type === 'breathe' ? '💛 Schön!' : '✓ Richtig!'}</b></div>
          ${task.fact ? `<div class="fact"><span class="eyebrow">💡 Wusstest du?</span><p>${esc(task.fact)}</p></div>` : ''}`;
        sfx.right();
        onSolved();
      } else {
        S.mistakes++;
        sfx.wrong();
        fb.innerHTML = `<div class="feedback no"><b>${esc(view.wrongText || 'Noch nicht ganz.')}</b>Fehler gehören dazu – sie helfen dir zu lernen. Versuch es nochmal.</div>`;
        if (view.afterWrong) view.afterWrong();
        sync();
      }
    });
  }

  const optBtn = (cls, mark, text, attrs = '') => `<button type="button" class="opt ${cls}" ${attrs}><span class="mark">${mark}</span><span>${esc(text)}</span></button>`;
  const base = (o) => Object.assign({ onChange() {} }, o);

  const VIEWS = {
    quiz(t) {
      const order = shuffle(t.options.map((_, i) => i));
      let sel = null, box;
      const v = base({
        mount(b) {
          box = b;
          b.innerHTML = `<div class="opts">${order.map((i, k) => optBtn('', 'ABCDEF'[k], t.options[i], `data-i="${i}"`)).join('')}</div>`;
          b.querySelectorAll('.opt').forEach((btn) => btn.addEventListener('click', () => {
            sel = +btn.dataset.i;
            b.querySelectorAll('.opt').forEach((x) => { x.classList.toggle('sel', x === btn); x.classList.remove('wrong'); });
            v.onChange();
          }));
        },
        ready: () => sel !== null,
        check: () => sel === t.answer,
        lock() { box.querySelectorAll('.opt').forEach((x) => { x.disabled = true; if (+x.dataset.i === t.answer) x.classList.add('right'); }); },
        afterWrong() { box.querySelector(`.opt[data-i="${sel}"]`).classList.add('wrong'); }
      });
      return v;
    },

    multi(t) {
      const order = shuffle(t.options.map((_, i) => i));
      const sel = new Set();
      const need = t.answers.length;
      let box;
      const v = base({
        mount(b) {
          box = b;
          b.innerHTML = `${t.visual === 'bulb' ? bulbSvg() : ''}<p class="small muted hint-line">Wähle ${need} Antworten.</p><div class="opts">${order.map((i) => optBtn('square', '✓', t.options[i], `data-i="${i}" aria-pressed="false"`)).join('')}</div>`;
          b.querySelectorAll('.opt').forEach((btn) => btn.addEventListener('click', () => {
            const i = +btn.dataset.i;
            if (sel.has(i)) sel.delete(i); else sel.add(i);
            btn.classList.toggle('sel', sel.has(i));
            btn.setAttribute('aria-pressed', sel.has(i));
            b.querySelectorAll('.opt').forEach((x) => x.classList.remove('wrong'));
            v.onChange();
          }));
        },
        ready: () => sel.size > 0,
        check() {
          const ok = sel.size === need && t.answers.every((a) => sel.has(a));
          if (ok && t.visual === 'bulb') box.querySelector('.bulb')?.classList.add('on');
          return ok;
        },
        lock() { box.querySelectorAll('.opt').forEach((x) => { x.disabled = true; if (sel.has(+x.dataset.i)) x.classList.add('right'); }); },
        get wrongText() { return sel.size !== need ? `Gesucht sind genau ${need} Antworten.` : 'Mindestens eine Auswahl passt nicht.'; },
        afterWrong() { sel.forEach((i) => { if (!t.answers.includes(i)) box.querySelector(`.opt[data-i="${i}"]`).classList.add('wrong'); }); }
      });
      return v;
    },

    order(t) {
      let shown = shuffle(t.items.map((_, i) => i));
      if (shown.every((x, i) => x === i)) shown = shown.reverse();
      let seq = [], box, done = false;
      const v = base({
        mount(b) {
          box = b;
          v.redraw();
        },
        redraw() {
          box.innerHTML = `<p class="small muted hint-line">Tippe die Einträge der Reihe nach an. Nochmal tippen nimmt ihn (und alle danach) zurück.</p>
            <div class="opts">${shown.map((i) => { const k = seq.indexOf(i); return optBtn(`${k >= 0 ? 'sel' : ''} ${done ? 'right' : ''}`, k >= 0 ? k + 1 : '', t.items[i], `data-i="${i}"`); }).join('')}</div>
            ${done ? '' : '<button type="button" class="btn btn-ghost btn-small" data-reset>Reihenfolge zurücksetzen</button>'}`;
          box.querySelectorAll('.opt').forEach((btn) => btn.addEventListener('click', () => {
            if (done) return;
            const i = +btn.dataset.i;
            const k = seq.indexOf(i);
            if (k >= 0) seq = seq.slice(0, k); else seq.push(i);
            v.redraw(); v.onChange();
          }));
          box.querySelector('[data-reset]')?.addEventListener('click', () => { seq = []; v.redraw(); v.onChange(); });
        },
        ready: () => seq.length === t.items.length,
        check: () => seq.every((i, k) => i === k),
        lock() { done = true; v.redraw(); },
        wrongText: 'Die Reihenfolge stimmt noch nicht.',
        afterWrong() {
          let k = 0;
          while (k < seq.length && seq[k] === k) k++;
          seq = seq.slice(0, k);
          v.redraw();
          if (k > 0) box.insertAdjacentHTML('afterbegin', `<div class="feedback hint">Die ersten ${k} Einträge stimmen und bleiben stehen.</div>`);
        }
      });
      return v;
    },

    pairs(t) {
      const rightOrder = shuffle(t.right.map((_, i) => i));
      const match = {};
      let pickL = 0, box, done = false;
      const hues = ['#E9C46A', '#8fd3ff', '#b6f09c', '#f7a8d8', '#c9a4f5'];
      const ownerOf = (j) => Object.keys(match).find((k) => match[k] === j);
      const v = base({
        mount(b) { box = b; v.redraw(); },
        redraw() {
          box.innerHTML = `<p class="small muted hint-line">Tippe links einen Begriff an und dann rechts das passende Gegenstück.</p>
            <div class="pairs"><div class="col">${t.left.map((l, i) => {
              const hue = match[i] !== undefined ? hues[i] : null;
              return `<button type="button" class="opt ${pickL === i && !done ? 'sel' : ''} ${done ? 'right' : ''}" data-l="${i}" ${hue ? `style="--pair:${hue}"` : ''}><span class="mark ${hue ? 'paired' : ''}"></span><span>${esc(l)}</span></button>`;
            }).join('')}</div><div class="col">${rightOrder.map((j) => {
              const li = ownerOf(j);
              const hue = li !== undefined ? hues[li] : null;
              return `<button type="button" class="opt ${done ? 'right' : ''}" data-r="${j}" ${hue ? `style="--pair:${hue}"` : ''}><span class="mark ${hue ? 'paired' : ''}"></span><span>${esc(t.right[j])}</span></button>`;
            }).join('')}</div></div>`;
          if (done) return;
          box.querySelectorAll('[data-l]').forEach((btn) => btn.addEventListener('click', () => {
            const i = +btn.dataset.l;
            delete match[i];
            pickL = i;
            v.redraw(); v.onChange();
          }));
          box.querySelectorAll('[data-r]').forEach((btn) => btn.addEventListener('click', () => {
            const j = +btn.dataset.r;
            const owner = ownerOf(j);
            if (owner !== undefined) delete match[owner];
            if (pickL !== null) {
              match[pickL] = j;
              const next = t.left.findIndex((_, i) => match[i] === undefined);
              pickL = next >= 0 ? next : null;
            }
            v.redraw(); v.onChange();
          }));
        },
        ready: () => Object.keys(match).length === t.left.length,
        check: () => t.left.every((_, i) => match[i] === i),
        lock() { done = true; v.redraw(); },
        wrongText: 'Nicht alle Paare passen.',
        afterWrong() {
          t.left.forEach((_, i) => { if (match[i] !== i) delete match[i]; });
          pickL = t.left.findIndex((_, i) => match[i] === undefined);
          v.redraw();
        }
      });
      return v;
    },

    sort(t) {
      const items = shuffle(t.items.map((_, i) => i));
      const pick = {};
      let box;
      const v = base({
        mount(b) {
          box = b;
          b.innerHTML = `<div class="sort-list">${items.map((i) => `<div class="sort-row" data-row="${i}"><span>${esc(t.items[i][0])}</span><div class="seg" role="group" aria-label="${esc(t.items[i][0])}">${t.cats.map((c, ci) => `<button type="button" data-i="${i}" data-c="${ci}" aria-pressed="false">${esc(c)}</button>`).join('')}</div></div>`).join('')}</div>`;
          b.querySelectorAll('.seg button').forEach((btn) => btn.addEventListener('click', () => {
            const i = +btn.dataset.i;
            pick[i] = +btn.dataset.c;
            btn.parentElement.querySelectorAll('button').forEach((x) => { x.classList.toggle('on', x === btn); x.setAttribute('aria-pressed', x === btn); });
            btn.closest('.sort-row').classList.remove('wrong');
            v.onChange();
          }));
        },
        ready: () => Object.keys(pick).length === t.items.length,
        check: () => t.items.every((it, i) => pick[i] === it[1]),
        lock() { box.querySelectorAll('.sort-row').forEach((r) => r.classList.add('right')); box.querySelectorAll('button').forEach((x) => { x.disabled = true; }); },
        wrongText: 'Ein paar Einträge liegen im falschen Fach (rot markiert).',
        afterWrong() { t.items.forEach((it, i) => { if (pick[i] !== it[1]) box.querySelector(`[data-row="${i}"]`).classList.add('wrong'); }); }
      });
      return v;
    },

    estimate(t) {
      let val = t.start, touched = false, box;
      const v = base({
        mount(b) {
          box = b;
          b.innerHTML = `<div class="estimate"><output id="est-out" for="est-range">${val} ${esc(t.unit)}</output>
            <input type="range" id="est-range" min="${t.min}" max="${t.max}" step="${t.step || 1}" value="${val}" aria-label="Schätzung in ${esc(t.unit)}">
            <div class="scale"><span>${t.min}</span><span>${t.max}</span></div></div>`;
          const r = b.querySelector('#est-range');
          r.addEventListener('input', () => { val = +r.value; touched = true; b.querySelector('#est-out').textContent = `${val} ${t.unit}`; v.onChange(); });
        },
        ready: () => touched,
        check: () => Math.abs(val - t.answer) <= t.tolerance,
        lock() {
          const r = box.querySelector('#est-range');
          r.disabled = true;
          box.querySelector('#est-out').textContent = `${t.answer} ${t.unit}`;
        },
        get wrongText() { return val > t.answer ? 'Zu hoch geschätzt.' : 'Zu niedrig geschätzt.'; }
      });
      return v;
    },

    breathe(t) {
      let done = false, running = false;
      const v = base({
        checkLabel: 'Fertig',
        mount(b) {
          const phase = DEBUG.fastBreath ? 150 : 4000;
          b.innerHTML = `<div class="breath"><div class="breath-circle"><span>Bereit?</span></div>
            <div class="breath-count">${t.breaths} Atemzüge · ca. ${t.breaths * 8} Sekunden</div>
            <button type="button" class="btn btn-primary" data-go>Übung starten</button></div>`;
          const c = b.querySelector('.breath-circle'), tx = c.querySelector('span'), cn = b.querySelector('.breath-count'), go = b.querySelector('[data-go]');
          go.addEventListener('click', () => {
            if (running) return;
            running = true;
            go.hidden = true;
            let i = 0;
            const cycle = () => {
              if (!document.body.contains(c)) return;
              if (i >= t.breaths) { tx.textContent = 'Schön.'; cn.textContent = 'Du bist angekommen.'; done = true; v.onChange(); return; }
              cn.textContent = `Atemzug ${i + 1} von ${t.breaths}`;
              c.classList.add('in');
              tx.textContent = 'Einatmen';
              setTimeout(() => {
                if (!document.body.contains(c)) return;
                c.classList.remove('in');
                tx.textContent = 'Ausatmen';
                setTimeout(() => { i++; cycle(); }, phase);
              }, phase);
            };
            cycle();
          });
        },
        ready: () => done,
        check: () => true
      });
      return v;
    },

    reflect(t) {
      // Mit options: jede Auswahl zählt. Ohne options: eigener kurzer Satz (minLength Zeichen).
      if (t.options) {
        const v = VIEWS.quiz({ ...t, answer: -1 });
        v.checkLabel = 'Weiter';
        v.check = () => true;
        v.lock = () => {};
        return v;
      }
      let txt = '';
      const v = base({
        checkLabel: 'Fertig',
        mount(b) {
          b.innerHTML = `<label for="reflect-text" class="eyebrow">Dein Satz</label>
            <textarea id="reflect-text" maxlength="280" placeholder="${esc(t.placeholder || '')}"></textarea>`;
          const ta = b.querySelector('#reflect-text');
          setTimeout(() => ta.focus({ preventScroll: true }), 60);
          ta.addEventListener('input', () => { txt = ta.value.trim(); v.onChange(); });
        },
        ready: () => txt.length >= (t.minLength || 1),
        check: () => true,
        lock() { const ta = document.querySelector('#reflect-text'); if (ta) ta.disabled = true; }
      });
      return v;
    },

    // Finale: erst das variado-Puzzle legen, dann die Synergie-Frage
    final(t, ctx) {
      const target = { tl: 'technik', tr: 'mensch', b: 'natur' };
      const placed = { tl: null, tr: null, b: null };
      const slotPaths = {
        tl: 'M0 0 L-39.84 23 A46 46 0 0 1 0 -46 Z',
        tr: 'M0 0 L0 -46 A46 46 0 0 1 39.84 23 Z',
        b: 'M0 0 L39.84 23 A46 46 0 0 1 -39.84 23 Z'
      };
      const slotName = { tl: 'oben links', tr: 'oben rechts', b: 'unten' };
      let pick = null, step = 1, quizView = null, box;
      const pieces = shuffle(['technik', 'mensch', 'natur']);
      const v = base({
        mount(b) { box = b; v.redraw(); },
        redraw() {
          if (step === 2) return;
          const left = pieces.filter((k) => !Object.values(placed).includes(k));
          box.innerHTML = `<div class="puzzle"><svg viewBox="-56 -56 112 112" role="group" aria-label="Puzzle mit drei Plätzen">
            ${Object.keys(slotPaths).map((s) => {
              const k = placed[s];
              return `<g class="slot" data-s="${s}" tabindex="0" role="button" aria-label="Platz ${slotName[s]}${k ? `: ${C.sectors[k].label}` : ', frei'}"><path d="${slotPaths[s]}" fill="${k ? C.sectors[k].color : '#1c3a31'}" stroke="#0b1814" stroke-width="3"/></g>`;
            }).join('')}
            <circle r="9" fill="#E9C46A" stroke="#0b1814" stroke-width="3"/></svg>
            <div class="opts">${left.map((k) => `<button type="button" class="opt piece ${pick === k ? 'sel' : ''}" data-k="${k}" style="--piece:${C.sectors[k].color}"><span class="mark"></span><span>${C.sectors[k].label}</span></button>`).join('') || '<div class="feedback hint">Alle Teile liegen. Prüfe, ob sie richtig sitzen.</div>'}</div></div>
            <div class="feedback hint">Tipp: Schau auf dein Spielfeld. Es ist genauso aufgebaut wie das variado-Logo.</div>`;
          box.querySelectorAll('[data-k]').forEach((btn) => btn.addEventListener('click', () => { pick = btn.dataset.k; v.redraw(); }));
          box.querySelectorAll('.slot').forEach((g) => {
            const act = () => {
              const s = g.dataset.s;
              if (pick) { placed[s] = pick; pick = null; } else if (placed[s]) placed[s] = null;
              v.redraw(); v.onChange();
            };
            g.addEventListener('click', act);
            g.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); act(); } });
          });
        },
        ready: () => (step === 1 ? Object.values(placed).every(Boolean) : quizView.ready()),
        check() {
          if (step === 1) {
            if (Object.keys(target).every((s) => placed[s] === target[s])) {
              step = 2;
              ctx.q.textContent = `Das Puzzle sitzt! ${t.q2}`;
              quizView = VIEWS.quiz({ options: t.options, answer: t.answer });
              quizView.onChange = () => v.onChange();
              quizView.mount(box);
              return null;
            }
            v.wrongText = 'Die Teile sitzen noch nicht richtig.';
            Object.keys(placed).forEach((s) => { if (placed[s] !== target[s]) placed[s] = null; });
            v.redraw();
            return false;
          }
          v.wrongText = 'Das ist noch nicht die variado-Synergie.';
          return quizView.check();
        },
        lock() { if (quizView) quizView.lock(); },
        afterWrong() { if (step === 2) quizView.afterWrong(); }
      });
      return v;
    }
  };

  function bulbSvg() {
    return `<svg class="bulb" viewBox="0 0 320 110" aria-hidden="true">
      <rect x="20" y="30" width="46" height="50" rx="6" fill="#1c3a31" stroke="#7d968c" stroke-width="2"/>
      <text x="43" y="61" text-anchor="middle" font-size="14" fill="#cfe6dc">+ −</text>
      <path d="M66 45 H150 M190 45 H250 M66 70 H250" stroke="#cfe6dc" stroke-width="3" fill="none"/>
      <path d="M150 45 l8 -7 M190 45 l-8 -7" stroke="#cfe6dc" stroke-width="3"/>
      <text x="170" y="30" text-anchor="middle" font-size="11" fill="#E9C46A">Lücke</text>
      <circle class="bulb-glow" cx="262" cy="55" r="34" fill="#E9C46A"/>
      <circle cx="262" cy="52" r="16" fill="#10231d" stroke="#E9C46A" stroke-width="2.5"/>
      <rect x="254" y="66" width="16" height="10" rx="2" fill="#7d968c"/>
      <path d="M250 45 V52 M250 70 V66" stroke="#cfe6dc" stroke-width="3"/></svg>`;
  }

  /* ------------------------------------------------------------------ */
  /* Aktionen                                                            */
  /* ------------------------------------------------------------------ */
  function nodeScreenPos(id) {
    const n = id === 'center' ? { x: CX, y: CY } : NODES[id];
    const m = (plate || svg).getScreenCTM();
    if (!m) return { x: innerWidth / 2, y: innerHeight / 2 };
    const pt = svg.createSVGPoint();
    pt.x = n.x; pt.y = n.y;
    const p = pt.matrixTransform(m);
    return { x: p.x, y: p.y };
  }

  function unlock(id) {
    const n = NODES[id];
    if (status(id) !== 'ready' || !S.answered[id]) return;
    const wasReady = centerReady();
    S.funken -= n.cost;
    S.solved[id] = true;
    S.level[id] = 0;
    sfx.unlock();
    const pos = nodeScreenPos(id);
    FX.burst(pos.x, pos.y, n.kind === 'bridge' ? [n.color, n.color2, '#E9C46A'] : [n.color, '#E9C46A'], 50);
    if (n.kind === 'bridge') {
      S.sp += E.bridgeSynergy;
      toast(`⬡ <b>Brücke ${esc(dn(n))}</b> gebaut! +${E.bridgeSynergy} ✦ Synergiepunkte`, 'gold');
    } else {
      toast(`✨ <b>${esc(dn(n))}</b> freigeschaltet – erzeugt jetzt Funken.`);
    }
    // Ring-Harmonie prüfen
    for (let r = 0; r < 5; r++) {
      if (S.harmony[r]) continue;
      if (RING_IDS[r].every((cid) => S.solved[cid])) {
        S.harmony[r] = true;
        S.sp += E.harmonySynergy;
        setTimeout(() => {
          sfx.harmony();
          toast(`🌀 <b>Ring „${esc(C.rings[r].name)}“ in Harmonie!</b> Alle drei Bereiche sind so weit. +${E.harmonySynergy} ✦`, 'gold');
          FX.ring(nodeScreenPos('center'), RING_R[r]);
        }, 500);
      }
    }
    if (!wasReady && centerReady() && !S.won) {
      setTimeout(() => toast('🌟 <b>Das Zentrum ist bereit!</b> Löse die letzte Aufgabe und erreiche die Synergie.', 'gold'), 1200);
    }
    hideModal($('#task-modal'));
    save();
    refreshAll();
  }

  function upgrade(id) {
    const cost = upgradeCost(id);
    if (S.funken < cost || (S.level[id] || 0) >= E.maxUpgrade) return;
    S.funken -= cost;
    S.level[id] = (S.level[id] || 0) + 1;
    sfx.right();
    const pos = nodeScreenPos(id);
    FX.burst(pos.x, pos.y, ['#E9C46A', NODES[id].color], 24);
    save();
    renderNodeBody(id);
    refreshAll();
  }

  function tap(ev) {
    const v = tapValue();
    S.funken += v;
    S.total += v;
    S.taps++;
    sfx.tap();
    const btn = $('#spark-btn');
    const r = btn.getBoundingClientRect();
    const x = ev && ev.clientX ? ev.clientX : r.left + r.width / 2;
    const y = ev && ev.clientY ? ev.clientY : r.top + r.height / 2;
    FX.burst(x, y, ['#E9C46A', '#fff3c4'], 8, 0.6);
    FX.text(x, y - 10, `+${fmt(v)}`);
    refreshPanel();
  }

  function onCenterClick() {
    if (S.won) { showWin(); return; }
    if (!centerReady()) {
      const missing = [];
      const c = counts();
      SECTORS.forEach((s) => { if (c[s] < PER_SECTOR) missing.push(`${sectorLabel(s)} (${c[s]}/${PER_SECTOR})`); });
      PAIRS.forEach((p) => { if (!bridgePairDone(p)) missing.push(`Brücke ${sectorLabel(p[0])} ∩ ${sectorLabel(p[1])}`); });
      toast(`✦ <b>Die Synergie ist noch verschlossen.</b> Es fehlt: ${esc(missing.join(', '))}.`);
      return;
    }
    openId = 'center';
    const pseudo = { icon: '✦', title: C.final.title, color: '#E9C46A', kind: 'core', blurb: 'Mehr als die Summe seiner Teile.' };
    setTaskHeader(pseudo, 'Zentrum · Letzte Aufgabe');
    const body = $('#task-body');
    body.innerHTML = '';
    body.dataset.state = 'final';
    renderTask(body, { ...C.final, type: 'final' }, () => {
      const foot = h('div', 'task-footer');
      const btn = h('button', 'btn btn-primary btn-wide', '✦ Synergie aktivieren');
      btn.addEventListener('click', () => { hideModal($('#task-modal')); celebrate(); });
      foot.appendChild(btn);
      body.appendChild(foot);
      btn.focus();
    });
    showModal('#task-modal');
  }

  /* ------------------------------------------------------------------ */
  /* Finale                                                              */
  /* ------------------------------------------------------------------ */
  function makeCode() {
    const abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let s = '';
    for (let i = 0; i < 8; i++) s += abc[Math.floor(Math.random() * abc.length)];
    return `S2S-${s.slice(0, 4)}-${s.slice(4)}`;
  }

  function celebrate() {
    S.won = true;
    S.wonAt = Date.now();
    nextRound();
    S.code = S.code || makeCode();
    save();
    document.body.classList.add('celebrate');
    updateTilt();
    sfx.win();

    // Ringe von außen nach innen golden aufleuchten lassen
    const order = ALL_IDS.slice().sort((a, b) => Math.hypot(NODES[b].x - CX, NODES[b].y - CY) - Math.hypot(NODES[a].x - CX, NODES[a].y - CY));
    order.forEach((id, i) => {
      setTimeout(() => {
        const pos = nodeScreenPos(id);
        FX.burst(pos.x, pos.y, ['#E9C46A', NODES[id].color], 14, 0.8);
        nodeEls[id].body.setAttribute('stroke', '#fff3c4');
        FX.stream(pos, nodeScreenPos('center'), NODES[id].color);
      }, i * 90);
    });
    const total = order.length * 90;
    setTimeout(() => {
      const c = nodeScreenPos('center');
      FX.ring(c, 60, '#fff3c4');
      FX.ring(c, 200, '#E9C46A');
      FX.burst(c.x, c.y, ['#E9C46A', '#E2A46F', '#6FBF73', '#4AA3D0', '#ffffff'], 220, 2.2);
      refreshAll();
    }, total + 500);
    setTimeout(() => FX.fireworks(6500), total + 900);
    setTimeout(() => { showWin(); document.body.classList.remove('celebrate'); }, total + 2300);
  }

  function showWin() {
    const secs = Math.round(((S.wonAt || Date.now()) - S.start) / 1000);
    const dur = secs >= 3600 ? `${Math.floor(secs / 3600)} Std. ${Math.floor((secs % 3600) / 60)} Min.` : `${Math.floor(secs / 60)} Min. ${secs % 60} Sek.`;
    $('#win-stats').innerHTML = `<span>⏱ ${dur}</span><span>✨ ${fmt(S.total)} Funken gesammelt</span><span>✦ ${S.sp} Synergiepunkte</span><span>🎯 ${S.mistakes} Fehlversuche</span>`;
    $('#win-code').textContent = S.code;
    $('#win-form-wrap').hidden = !!S.submitted;
    $('#win-done').hidden = !S.submitted;
    showModal('#win-modal');
  }

  $('#privacy-link').href = C.meta.privacyUrl;
  $('#win-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const name = $('#win-name').value.trim();
    const email = $('#win-email').value.trim();
    const err = $('#win-error');
    err.hidden = true;
    if (!name) { err.textContent = 'Bitte gib deinen Namen an.'; err.hidden = false; return; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { err.textContent = 'Bitte gib eine gültige E-Mail-Adresse an.'; err.hidden = false; return; }
    if (!$('#win-consent').checked) { err.textContent = 'Bitte bestätige die Einwilligung, damit wir dich im Gewinnfall erreichen dürfen.'; err.hidden = false; return; }
    const payload = {
      name, email, newsletter: $('#win-news').checked, code: S.code,
      durationSec: Math.round(((S.wonAt || Date.now()) - S.start) / 1000),
      mistakes: S.mistakes, completedAt: new Date(S.wonAt || Date.now()).toISOString()
    };
    const submit = $('#win-submit');
    submit.disabled = true;
    submit.textContent = 'Wird gesendet …';
    let ok = false;
    let dup = false;
    if (C.meta.submitUrl && location.protocol.startsWith('http')) {
      try {
        const res = await fetch(C.meta.submitUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        ok = res.ok;
        dup = res.status === 409;
      } catch (e2) { ok = false; }
    }
    if (!ok && !dup) {
      // Fallback ohne Server: vorbereitete E-Mail (Adresse und Code bleiben sichtbar, falls der Link nicht öffnet)
      const body = `Hallo variado-Team,\n\nich habe bei „Spark to Synergy“ die Synergie erreicht und möchte am Gewinnspiel teilnehmen.\n\nName: ${name}\nE-Mail: ${email}\nSynergie-Code: ${S.code}\nNewsletter: ${payload.newsletter ? 'ja' : 'nein'}\n\nViele Grüße`;
      const href = `mailto:${C.meta.contactEmail}?subject=${encodeURIComponent(`Spark to Synergy – Teilnahme ${S.code}`)}&body=${encodeURIComponent(body)}`;
      $('#win-done').innerHTML = `<p class="win-thanks">Fast geschafft! Schick uns bitte eine kurze E-Mail mit deinem Namen und deinem Synergie-Code an <b class="gold" style="user-select:all">${esc(C.meta.contactEmail)}</b>.</p>
        <a class="btn btn-primary" href="${esc(href)}">E-Mail vorbereiten ✉️</a>`;
    } else if (dup) {
      $('#win-done').innerHTML = '<p class="win-thanks">Du bist bereits im Lostopf – wir drücken dir die Daumen! 🍀</p>';
    }
    // Nur als „eingegangen“ merken, wenn der Server die Teilnahme bestätigt hat
    if (ok || dup) { S.submitted = true; save(); }
    $('#win-form-wrap').hidden = true;
    $('#win-done').hidden = false;
    submit.disabled = false;
    submit.textContent = 'Teilnehmen 🎁';
  });

  /* ------------------------------------------------------------------ */
  /* Effekte (Canvas)                                                    */
  /* ------------------------------------------------------------------ */
  const FX = (() => {
    const cv = $('#fx');
    const ctx = cv.getContext('2d');
    let parts = [];
    let rings = [];
    let texts = [];
    let running = false;
    let dpr = 1;
    const resize = () => {
      dpr = Math.min(window.devicePixelRatio || 1, 2);
      cv.width = innerWidth * dpr;
      cv.height = innerHeight * dpr;
    };
    resize();
    addEventListener('resize', resize);

    const loop = () => {
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      ctx.clearRect(0, 0, innerWidth, innerHeight);
      ctx.globalCompositeOperation = 'lighter';
      parts = parts.filter((p) => p.life > 0);
      parts.forEach((p) => {
        if (p.target) {
          p.t += 0.025;
          const e = p.t * p.t;
          p.x = p.sx + (p.target.x - p.sx) * e + Math.sin(p.t * 6 + p.seed) * 12 * (1 - p.t);
          p.y = p.sy + (p.target.y - p.sy) * e + Math.cos(p.t * 6 + p.seed) * 12 * (1 - p.t);
          if (p.t >= 1) p.life = 0;
        } else {
          p.vx *= 0.975; p.vy = p.vy * 0.975 + p.g;
          p.x += p.vx; p.y += p.vy;
          p.life -= p.decay;
        }
        ctx.globalAlpha = Math.max(0, Math.min(1, p.life));
        ctx.fillStyle = p.color;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
        ctx.fill();
      });
      rings = rings.filter((r) => r.life > 0);
      rings.forEach((r) => {
        r.r += r.speed; r.life -= 0.02;
        ctx.globalAlpha = Math.max(0, r.life);
        ctx.strokeStyle = r.color;
        ctx.lineWidth = 4 * r.life + 1;
        ctx.beginPath();
        ctx.arc(r.x, r.y, r.r, 0, Math.PI * 2);
        ctx.stroke();
      });
      ctx.globalCompositeOperation = 'source-over';
      texts = texts.filter((t) => t.life > 0);
      texts.forEach((t) => {
        t.y -= 0.8; t.life -= 0.02;
        ctx.globalAlpha = Math.max(0, t.life);
        ctx.fillStyle = '#E9C46A';
        ctx.font = '700 16px system-ui, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(t.s, t.x, t.y);
      });
      ctx.globalAlpha = 1;
      if (parts.length || rings.length || texts.length) requestAnimationFrame(loop);
      else { running = false; ctx.clearRect(0, 0, innerWidth, innerHeight); }
    };
    const kick = () => { if (!running) { running = true; requestAnimationFrame(loop); } };
    const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

    return {
      burst(x, y, colors, n = 30, power = 1) {
        if (reduced) n = Math.min(n, 12);
        for (let i = 0; i < n; i++) {
          const a = Math.random() * Math.PI * 2;
          const s = (Math.random() * 5 + 1.5) * power;
          parts.push({ x, y, vx: Math.cos(a) * s, vy: Math.sin(a) * s, g: 0.04, life: 1, decay: 0.012 + Math.random() * 0.02, size: Math.random() * 2.6 + 1, color: colors[i % colors.length] });
        }
        kick();
      },
      stream(from, to, color) {
        for (let i = 0; i < 10; i++) {
          parts.push({ sx: from.x, sy: from.y, x: from.x, y: from.y, target: to, t: -i * 0.03, seed: Math.random() * 10, life: 1, size: 2.4, color });
        }
        kick();
      },
      ring(c, r, color = '#E9C46A') {
        const scale = svg.getBoundingClientRect().width / 1000;
        rings.push({ x: c.x, y: c.y, r: r * scale, speed: 3 + r * scale / 60, life: 1, color });
        kick();
      },
      text(x, y, s) { texts.push({ x, y, s, life: 1 }); kick(); },
      fireworks(duration) {
        const colors = ['#E9C46A', '#E2A46F', '#6FBF73', '#4AA3D0', '#fff3c4', '#B084CC'];
        const end = Date.now() + duration;
        const shoot = () => {
          if (Date.now() > end) return;
          const x = innerWidth * (0.12 + Math.random() * 0.76);
          const y = innerHeight * (0.12 + Math.random() * 0.45);
          const c = colors[Math.floor(Math.random() * colors.length)];
          this.burst(x, y, [c, '#ffffff'], 70, 1.3);
          setTimeout(shoot, 180 + Math.random() * 260);
        };
        shoot();
      }
    };
  })();
  FX.fireworks = FX.fireworks.bind(FX);

  /* ------------------------------------------------------------------ */
  /* Geistesblitze (Bonus-Funken)                                        */
  /* ------------------------------------------------------------------ */
  function scheduleBonus() {
    setTimeout(() => {
      if (Object.keys(S.solved).length && !S.won && document.visibilityState === 'visible') spawnBonus();
      scheduleBonus();
    }, 35000 + Math.random() * 35000);
  }
  function spawnBonus() {
    const b = document.createElement('button');
    b.className = 'bonus-spark';
    b.setAttribute('aria-label', 'Geistesblitz einsammeln');
    const x = innerWidth * (0.1 + Math.random() * 0.6);
    const y = innerHeight * (0.2 + Math.random() * 0.5);
    b.style.left = `${x}px`;
    b.style.top = `${y}px`;
    b.style.setProperty('--dx', `${(Math.random() - 0.5) * 300}px`);
    b.style.setProperty('--dy', `${-120 - Math.random() * 160}px`);
    b.addEventListener('click', (e) => {
      const v = Math.max(20, perSec() * 25);
      S.funken += v;
      S.total += v;
      FX.burst(e.clientX, e.clientY, ['#E9C46A', '#ffffff'], 40, 1.2);
      FX.text(e.clientX, e.clientY - 20, `+${fmt(v)}`);
      toast(`💡 <b>Geistesblitz!</b> +${fmt(v)} Funken`, 'gold');
      sfx.unlock();
      b.remove();
      refreshPanel();
    });
    b.addEventListener('animationend', (e) => { if (e.animationName === 'drift') b.remove(); });
    document.body.appendChild(b);
  }

  /* ------------------------------------------------------------------ */
  /* Start                                                               */
  /* ------------------------------------------------------------------ */
  buildBoard();

  // Offline-Ertrag (wie in Cell to Singularity – nur etwas bescheidener)
  const away = Math.min((Date.now() - S.last) / 1000, 4 * 3600);
  if (away > 60 && Object.keys(S.solved).length && !S.won) {
    const gain = perSec() * away * 0.5;
    if (gain >= 1) {
      S.funken += gain;
      S.total += gain;
      setTimeout(() => toast(`🌙 <b>Willkommen zurück!</b> Während du weg warst, haben deine Knoten ${fmt(gain)} Funken erzeugt.`, 'gold'), 600);
    }
  }

  $('#spark-btn').addEventListener('click', tap);
  $('#spark-fab').addEventListener('click', tap);
  $('#btn-help').addEventListener('click', () => showModal('#help-modal'));
  $('#btn-sound').addEventListener('click', () => {
    S.sound = !S.sound;
    $('#btn-sound').textContent = S.sound ? '🔊' : '🔇';
    save();
  });
  $('#btn-sound').textContent = S.sound ? '🔊' : '🔇';
  $('#btn-reset').addEventListener('click', () => showModal('#reset-modal'));
  $('#reset-confirm').addEventListener('click', () => {
    try { localStorage.removeItem(SAVE_KEY); } catch (e) { /* ignorieren */ }
    if (!S.won) nextRound();
    S = fresh();
    S.seenHelp = true;
    save();
    location.reload();
  });

  refreshAll();
  if (!S.seenHelp) {
    S.seenHelp = true;
    save();
    showModal('#help-modal');
  }

  let lastT = performance.now();
  let acc = 0;
  let saveAcc = 0;
  let sideAcc = 0;
  const frame = (t) => {
    const dt = Math.min((t - lastT) / 1000, 1);
    lastT = t;
    applyTilt();
    const gain = perSec() * dt;
    S.funken += gain;
    S.total += gain;
    acc += dt;
    saveAcc += dt;
    if (acc > 0.25) {
      acc = 0;
      refreshPanel();
      refreshBoard();
      refreshOpenModal();
    }
    sideAcc += dt;
    if (sideAcc > 1) { sideAcc = 0; refreshSide(); }
    if (saveAcc > 5) { saveAcc = 0; save(); }
    requestAnimationFrame(frame);
  };
  requestAnimationFrame(frame);
  document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') save(); });
  addEventListener('pagehide', save);
  scheduleBonus();

  // Für Tests & Moderation (z. B. in Workshops): window.S2S.debug.solveAll()
  window.S2S = {
    state: () => S,
    taskFor,
    debug: {
      fastBreath: (on = true) => { DEBUG.fastBreath = on; },
      addFunken: (n) => { S.funken += n; refreshAll(); },
      solveAll: () => {
        ALL_IDS.forEach((id) => { S.solved[id] = true; S.answered[id] = true; S.level[id] = S.level[id] || 0; });
        S.harmony = [true, true, true, true, true];
        S.sp = 5 + BRIDGE_IDS.length * E.bridgeSynergy;
        save(); refreshAll();
      }
    }
  };
})();
