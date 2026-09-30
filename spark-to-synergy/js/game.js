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
  const CORE_NODE_R = [36, 34, 32, 30, 28];
  const ZIG = [0, -15, 13, -13, 0];
  const CENTER_R = 60;
  const SAVE_KEY = 's2s-save-v1';
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
    const angle = sec.angle + ZIG[n.ring];
    const prev = C.nodes.find((m) => m.sector === n.sector && m.ring === n.ring - 1);
    NODES[n.id] = {
      ...n, kind: 'core', angle, ...polar(RING_R[n.ring], angle),
      r: CORE_NODE_R[n.ring], color: sec.color,
      cost: E.coreCost[n.ring], output: E.coreOutput[n.ring],
      requires: prev ? [prev.id] : []
    };
  });
  C.bridges.forEach((b) => {
    const angle = midAngle(C.sectors[b.sectors[0]].angle, C.sectors[b.sectors[1]].angle);
    const radius = RING_R[b.ring] - 30;
    NODES[b.id] = {
      ...b, kind: 'bridge', angle, ...polar(radius, angle),
      r: 27, color: C.sectors[b.sectors[0]].color, color2: C.sectors[b.sectors[1]].color,
      cost: E.bridgeCost[b.ring], output: E.bridgeOutput[b.ring]
    };
  });
  const CORE_IDS = C.nodes.map((n) => n.id);
  const BRIDGE_IDS = C.bridges.map((b) => b.id);
  const ALL_IDS = [...CORE_IDS, ...BRIDGE_IDS];

  /* ------------------------------------------------------------------ */
  /* Spielstand                                                          */
  /* ------------------------------------------------------------------ */
  const fresh = () => ({
    funken: 0, total: 0, solved: {}, answered: {}, level: {}, sp: 0,
    harmony: [false, false, false, false, false],
    mistakes: 0, taps: 0, start: Date.now(), last: Date.now(),
    won: false, wonAt: null, code: null, submitted: false, sound: true, seenHelp: false
  });
  let S = fresh();
  try {
    const raw = localStorage.getItem(SAVE_KEY);
    if (raw) S = Object.assign(fresh(), JSON.parse(raw));
  } catch (e) { /* kein Speicher verfügbar */ }
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
    const area = (a, b) => Math.max(0, Math.min(a.x1, b.x1) - Math.max(a.x0, b.x0)) * Math.max(0, Math.min(a.y1, b.y1) - Math.max(a.y0, b.y0));
    const order = [...CORE_IDS.slice().sort((a, b) => NODES[a].ring - NODES[b].ring), ...BRIDGE_IDS];
    order.forEach((id) => {
      const n = NODES[id];
      const parts = n.title.split('|');
      const lines = parts.length > 1 ? [parts[0].endsWith('-') ? parts[0] : `${parts[0]}-`, parts[1]] : [n.title];
      const w = Math.max(...lines.map((l) => l.length * 9.4), 64);
      const hgt = (lines.length + 1) * LH;
      const gap = n.r + 8;
      // Bevorzugte Richtung: zur Mittellinie des Bereichs bzw. bei Brücken nach außen
      const tangentX = -Math.sin((n.angle - 90) * Math.PI / 180) * (ZIG[n.ring] > 0 ? -1 : 1);
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
        obstacles.forEach((o) => { score += area(c.box, o); });
        [[c.box.x0, c.box.y0], [c.box.x1, c.box.y0], [c.box.x0, c.box.y1], [c.box.x1, c.box.y1]].forEach(([x, y]) => {
          const d = Math.hypot(x - CX, y - CY);
          if (d > 462) score += (d - 462) * 40;
        });
        if (!best || score < best.score) best = { ...c, score };
      });
      obstacles.push(best.box);
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
    const stars = el('g', { opacity: 0.5 }, svg);
    for (let i = 0; i < 70; i++) {
      el('circle', { cx: Math.random() * 1000, cy: Math.random() * 1000, r: Math.random() * 1.6 + 0.3, fill: '#cfe6dc', opacity: Math.random() * 0.6 + 0.2 }, stars);
    }

    // Sektoren (Bamboleo-Scheibe)
    const disc = el('g', {}, svg);
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

    // Ringe
    RING_R.forEach((r, i) => {
      ringEls[i] = el('circle', { cx: CX, cy: CY, r, fill: 'none', stroke: '#1f3a32', 'stroke-width': 2, 'stroke-dasharray': '4 10', class: 'ring' }, svg);
    });
    // Verbindungen
    const links = el('g', {}, svg);
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
    centerEl.addEventListener('click', onCenterClick);
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
      g.addEventListener('click', () => openNode(id));
      g.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openNode(id); } });
      nodeEls[id] = { g, body: g.querySelector('.body'), cost: g.querySelector('.cost'), pips: [...pips.children], lastState: '' };
    });
  }

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
    SECTORS.forEach((s) => {
      const a = C.sectors[s].angle * Math.PI / 180;
      vx += c[s] * Math.sin(a);
      vy += -c[s] * Math.cos(a);
    });
    const k = 4.2;
    let rx = -vy * k, ry = vx * k;
    const mag = Math.hypot(rx, ry);
    if (mag > 12) { rx *= 12 / mag; ry *= 12 / mag; }
    const tilt = $('#board-tilt');
    if (!document.body.classList.contains('celebrate')) {
      tilt.style.transform = `rotateX(${rx.toFixed(2)}deg) rotateY(${ry.toFixed(2)}deg)`;
    }
    tilt.classList.toggle('wobble', imbalance(c) >= 2);
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
        <div class="bal-track"><div class="bal-fill" style="width:${(c[s] / 5) * 100}%;background:${C.sectors[s].color}"></div></div>
        <span class="bal-num">${c[s]}/5</span>`;
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
    SECTORS.forEach((s) => goal(c[s] === 5, `${sectorLabel(s)}: alle 5 Ringe <span class="muted">(${c[s]}/5)</span>`));
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
      const learn = h('div', 'info-box', `<b>Das hast du gelernt:</b><br>${esc(n.task.explain)}`);
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
      body.appendChild(h('div', 'feedback ok', `<b>✓ Aufgabe gelöst</b>${esc(n.task.explain)}`));
      body.appendChild(unlockFooter(id));
      return;
    }

    renderTask(body, n.task, () => {
      S.answered[id] = true;
      save();
      body.appendChild(unlockFooter(id));
      refreshBoard();
    });
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
      if (!body.querySelector('.opts, .order-slots') || S.answered[openId]) renderNodeBody(openId);
      return;
    }
    body.querySelectorAll('.unlock-btn').forEach((b) => b._update && b._update());
    body.querySelectorAll('.task-footer .btn[data-cost]:not(.unlock-btn)').forEach((b) => { b.disabled = S.funken < Number(b.dataset.cost); });
  }

  /* ---------- Aufgaben ---------- */
  function renderTask(container, task, onSolved) {
    container.appendChild(h('p', 'task-q', esc(task.q)));
    if (task.type === 'order') renderOrder(container, task, onSolved);
    else renderChoice(container, task, onSolved);
  }

  function renderChoice(container, task, onSolved) {
    const reflect = task.type === 'reflect';
    const opts = h('div', 'opts');
    const order = reflect ? task.options.map((_, i) => i) : shuffle(task.options.map((_, i) => i));
    const fb = h('div', 'feedback');
    fb.hidden = true;
    order.forEach((idx, pos) => {
      const b = h('button', 'opt', `<span class="key">${'ABCD'[pos]}</span><span>${esc(task.options[idx])}</span>`);
      b.addEventListener('click', () => {
        if (reflect || idx === task.correct) {
          b.classList.add('right');
          opts.querySelectorAll('.opt').forEach((o) => { o.disabled = true; });
          fb.className = 'feedback ok';
          fb.innerHTML = `<b>${reflect ? '💡 Schön!' : '✓ Richtig! Aha …'}</b>${esc(task.explain)}`;
          fb.hidden = false;
          sfx.right();
          onSolved();
        } else {
          b.classList.add('wrong');
          b.disabled = true;
          S.mistakes++;
          fb.className = 'feedback no';
          fb.innerHTML = '<b>Nicht ganz.</b>Kein Problem – Fehler gehören zum Lernen. Versuch es noch einmal!';
          fb.hidden = false;
          sfx.wrong();
        }
      });
      opts.appendChild(b);
    });
    container.appendChild(opts);
    container.appendChild(fb);
  }

  function renderOrder(container, task, onSolved) {
    const n = task.items.length;
    const slots = new Array(n).fill(null);
    const fixed = new Array(n).fill(false);
    let pool = shuffle(task.items.map((_, i) => i));
    if (pool.every((v, i) => v === i)) pool = pool.reverse();
    const slotWrap = h('div', 'order-slots');
    const poolWrap = h('div', 'pool');
    const fb = h('div', 'feedback');
    fb.hidden = true;
    container.appendChild(h('p', 'small muted', 'Tippe die Schritte der Reihe nach an. Antippen eines Platzes legt den Schritt zurück.'));
    container.appendChild(slotWrap);
    container.appendChild(poolWrap);
    container.appendChild(fb);
    let done = false;

    const draw = () => {
      slotWrap.innerHTML = '';
      slots.forEach((v, i) => {
        const s = h('div', `slot ${v !== null ? 'filled' : ''} ${fixed[i] ? 'right' : ''}`, `<span class="n">${i + 1}</span><span>${v !== null ? esc(task.items[v]) : '…'}</span>`);
        if (v !== null && !fixed[i] && !done) {
          s.addEventListener('click', () => { pool.push(v); slots[i] = null; draw(); });
        }
        slotWrap.appendChild(s);
      });
      poolWrap.innerHTML = '';
      pool.forEach((v) => {
        const b = h('button', 'opt', `<span>${esc(task.items[v])}</span>`);
        b.addEventListener('click', () => {
          const free = slots.indexOf(null);
          if (free === -1) return;
          slots[free] = v;
          pool = pool.filter((p) => p !== v);
          draw();
          if (slots.indexOf(null) === -1) check();
        });
        poolWrap.appendChild(b);
      });
    };

    const check = () => {
      const correct = slots.map((v, i) => v === i);
      if (correct.every(Boolean)) {
        done = true;
        fixed.fill(true);
        draw();
        fb.className = 'feedback ok';
        fb.innerHTML = `<b>✓ Richtig! Aha …</b>${esc(task.explain)}`;
        fb.hidden = false;
        sfx.right();
        onSolved();
        return;
      }
      S.mistakes++;
      sfx.wrong();
      const right = correct.filter(Boolean).length;
      fb.className = 'feedback no';
      fb.innerHTML = `<b>${right} von ${n} stehen richtig.</b>Die richtigen bleiben liegen – ordne den Rest noch einmal.`;
      fb.hidden = false;
      slotWrap.querySelectorAll('.slot').forEach((s, i) => s.classList.add(correct[i] ? 'right' : 'wrong'));
      setTimeout(() => {
        slots.forEach((v, i) => {
          if (correct[i]) fixed[i] = true;
          else { pool.push(v); slots[i] = null; }
        });
        draw();
      }, 1100);
    };
    draw();
  }

  /* ------------------------------------------------------------------ */
  /* Aktionen                                                            */
  /* ------------------------------------------------------------------ */
  function nodeScreenPos(id) {
    const n = id === 'center' ? { x: CX, y: CY } : NODES[id];
    const m = svg.getScreenCTM();
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
      if (SECTORS.every((s) => CORE_IDS.some((cid) => NODES[cid].sector === s && NODES[cid].ring === r && S.solved[cid]))) {
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
      SECTORS.forEach((s) => { if (c[s] < 5) missing.push(`${sectorLabel(s)} (${c[s]}/5)`); });
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
    renderTask(body, { ...C.final, type: 'choice' }, () => {
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
    S.code = S.code || makeCode();
    save();
    document.body.classList.add('celebrate');
    $('#board-tilt').style.transform = 'rotateX(0deg) rotateY(0deg)';
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
      // Fallback ohne Server: vorausgefüllte E-Mail
      const body = `Hallo variado-Team,\n\nich habe bei „Spark to Synergy“ die Synergie erreicht und möchte am Gewinnspiel teilnehmen.\n\nName: ${name}\nE-Mail: ${email}\nSynergie-Code: ${S.code}\nNewsletter: ${payload.newsletter ? 'ja' : 'nein'}\n\nViele Grüße`;
      location.href = `mailto:${C.meta.contactEmail}?subject=${encodeURIComponent(`Spark to Synergy – Teilnahme ${S.code}`)}&body=${encodeURIComponent(body)}`;
      $('#win-done').innerHTML = '<p class="win-thanks">Fast geschafft! Dein E-Mail-Programm öffnet sich mit einer vorbereiteten Nachricht – bitte schicke sie ab. 🍀</p>';
    } else if (dup) {
      $('#win-done').innerHTML = '<p class="win-thanks">Du bist bereits im Lostopf – wir drücken dir die Daumen! 🍀</p>';
    }
    S.submitted = true;
    save();
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
  $('#btn-reset').addEventListener('click', () => {
    if (confirm('Wirklich von vorne beginnen? Dein Fortschritt geht verloren.')) {
      try { localStorage.removeItem(SAVE_KEY); } catch (e) { /* ignorieren */ }
      S = fresh();
      S.seenHelp = true;
      save();
      location.reload();
    }
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
    debug: {
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
