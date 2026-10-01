/* Stagepool – kleine Helfer ohne Abhängigkeiten. Alles funktioniert auch ohne JS. */
(function () {
  'use strict';

  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

  /* ---------- Datumshelfer ---------- */
  function parse(d) { var p = d.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
  function fmt(date) {
    var m = date.getMonth() + 1, d = date.getDate();
    return date.getFullYear() + '-' + (m < 10 ? '0' : '') + m + '-' + (d < 10 ? '0' : '') + d;
  }
  function days(a, b) { return Math.round((parse(b) - parse(a)) / 86400000) + 1; }
  function de(d) { var x = parse(d); return ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'][x.getDay()] + ' ' + d.slice(8, 10) + '.' + d.slice(5, 7) + '.'; }
  function euro(n) { return n.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'; }

  /* ---------- Toast ---------- */
  var toastTimer;
  function toast(html) {
    var t = $('.toast');
    if (!t) { t = document.createElement('div'); t.className = 'toast'; t.setAttribute('role', 'status'); document.body.appendChild(t); }
    t.innerHTML = html;
    requestAnimationFrame(function () { t.classList.add('is-visible'); });
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { t.classList.remove('is-visible'); }, 4200);
  }
  function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

  /* ---------- Navigation ---------- */
  var toggle = $('.nav-toggle'), nav = $('#main-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  /* ---------- Auto-Submit ---------- */
  $$('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { if (el.form) { el.form.submit(); } });
  });

  /* ---------- Von/Bis koppeln ---------- */
  $$('[data-range-start]').forEach(function (start) {
    var form = start.form || start.closest('form') || document;
    var end = $('[data-range-end]', form);
    if (!end) { return; }
    function sync() {
      if (start.value) {
        end.min = start.value;
        if (!end.value || end.value < start.value) { end.value = start.value; }
      }
      form.dispatchEvent(new Event('rangechange'));
    }
    start.addEventListener('change', sync);
    end.addEventListener('change', function () { form.dispatchEvent(new Event('rangechange')); });
  });

  /* ---------- Bestätigungen ---------- */
  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    var btn = ev.submitter;
    var msg = (btn && btn.getAttribute('data-confirm')) || f.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { ev.preventDefault(); }
  });

  /* ---------- Klickbare Tabellenzeilen ---------- */
  $$('tr[data-href]').forEach(function (tr) {
    tr.addEventListener('click', function (ev) {
      if (ev.target.closest('a, button, input, select, label, form')) { return; }
      window.location.href = tr.getAttribute('data-href');
    });
  });

  /* ---------- Sichtbarkeit umschalten ---------- */
  $$('[data-toggle]').forEach(function (cb) {
    var target = $(cb.getAttribute('data-toggle'));
    if (!target) { return; }
    cb.addEventListener('change', function () { target.hidden = !cb.checked; });
  });
  $$('input[name="handover"]').forEach(function (r) {
    r.addEventListener('change', function () {
      var f = $('[data-delivery-field]');
      if (f) { f.hidden = r.value !== 'delivery' || !r.checked; }
    });
  });
  $$('[data-scope]').forEach(function (group) {
    var root = group.closest('form') || document;
    function apply() {
      var checked = $('input:checked', group);
      $$('[data-scope-field]', root).forEach(function (el) {
        el.hidden = !checked || el.getAttribute('data-scope-field') !== checked.value;
      });
    }
    group.addEventListener('change', apply);
    apply();
  });
  $$('[data-select-all]').forEach(function (i) { i.addEventListener('focus', function () { i.select(); }); });
  $$('[data-color-select]').forEach(function (s) {
    s.addEventListener('change', function () { var row = s.closest('[style]'); if (row) { row.style.setProperty('--accent', s.value); } });
  });

  /* ---------- Gerät: Endkundenpreis automatisch aus internem Preis ---------- */
  var pInt = $('[data-price-internal]'), pCust = $('[data-price-customer]'), pAuto = $('[data-price-auto]');
  if (pInt && pCust && pAuto) {
    var markup = parseFloat(pCust.getAttribute('data-markup')) || 0;
    var num = function (v) { return parseFloat(String(v).replace(/\./g, '').replace(',', '.')) || 0; };
    var syncPrice = function () {
      pCust.readOnly = pAuto.checked && num(pInt.value) > 0;
      if (pCust.readOnly) {
        pCust.value = (Math.round(num(pInt.value) * (1 + markup / 100) * 2) / 2).toFixed(2).replace('.', ',');
      }
    };
    pInt.addEventListener('input', syncPrice);
    pAuto.addEventListener('change', syncPrice);
    syncPrice();
  }

  /* ---------- Buchhaltung: Kategorien passend zur Art ---------- */
  $$('[data-tx-type]').forEach(function (group) {
    var form = group.closest('form');
    var select = $('select[name="category"]', form);
    function apply(initial) {
      var checked = $('input:checked', group);
      var type = checked ? checked.value : 'expense';
      $$('optgroup', select).forEach(function (og) {
        var on = og.getAttribute('data-for') === type;
        og.hidden = !on;
        $$('option', og).forEach(function (o) { o.disabled = !on; });
      });
      if (!initial || select.selectedOptions[0].disabled) {
        var first = $('optgroup[data-for="' + type + '"] option', select);
        if (first) { first.selected = true; }
      }
    }
    group.addEventListener('change', function () { apply(false); });
    apply(true);
  });

  /* ---------- In die Anfrage legen (ohne Neuladen) ---------- */
  $$('form[data-add-form]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!window.fetch || !window.FormData) { return; }
      ev.preventDefault();
      var btn = $('button', form);
      btn.disabled = true;
      fetch(form.getAttribute('action'), { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          btn.disabled = false;
          if (!data.ok) { toast(esc(data.message || 'Das hat nicht geklappt.')); return; }
          btn.classList.add('is-added');
          var label = $('span', btn);
          if (label) { label.textContent = 'Drin'; }
          var count = $('[data-cart-count]');
          if (count) {
            count.textContent = data.count;
            count.classList.add('has-items');
            count.classList.remove('bump'); void count.offsetWidth; count.classList.add('bump');
          }
          var cartLink = $('.cart-btn');
          toast(esc(data.message) + (cartLink ? ' <a href="' + cartLink.getAttribute('href') + '">Zur Anfrage →</a>' : ''));
        })
        .catch(function () { btn.disabled = false; HTMLFormElement.prototype.submit.call(form); });
    });
  });

  /* ---------- Produktseite: Preis live berechnen ---------- */
  var box = $('[data-book-box]');
  if (box) {
    var price = parseFloat(box.getAttribute('data-price')) || 0;
    var extra = box.hasAttribute('data-extra') ? parseFloat(box.getAttribute('data-extra')) / 100 : 1;
    var sum = $('[data-sum]', box);
    var bStart = $('[data-range-start]', box), bEnd = $('[data-range-end]', box), qty = $('[data-qty]', box);
    var update = function () {
      if (!bStart.value || !bEnd.value || bEnd.value < bStart.value) { return; }
      var n = days(bStart.value, bEnd.value), q = parseInt(qty.value, 10) || 1;
      sum.innerHTML = '<span>' + n + (n === 1 ? ' Tag' : ' Tage') + (q > 1 ? ' × ' + q : '') + '</span><b>' + euro(Math.round(price * (1 + (n - 1) * extra) * q * 100) / 100) + '</b>';
      markRange(bStart.value, bEnd.value);
    };
    box.addEventListener('rangechange', update);
    if (qty) { qty.addEventListener('change', update); }

    // Kalender: zwei Klicks = Zeitraum
    var cal = $('[data-range-calendar]');
    var pickStart = null;
    var markRange = function (a, b) {
      $$('.day[data-date]', cal).forEach(function (d) {
        var v = d.getAttribute('data-date');
        d.classList.toggle('is-sel', v === a || v === b);
        d.classList.toggle('is-range', !!b && v > a && v < b);
      });
    };
    if (cal) {
      cal.addEventListener('click', function (ev) {
        var day = ev.target.closest('button.day');
        if (!day) { return; }
        var v = day.getAttribute('data-date');
        if (!pickStart || v < pickStart) {
          pickStart = v;
          markRange(v, null);
          bStart.value = v; bEnd.value = v;
          bEnd.min = v;
          update();
          toast('Start: <b>' + de(v) + '</b> – jetzt den letzten Miettag anklicken.');
          return;
        }
        // Prüfen, ob dazwischen ein belegter Tag liegt
        var blocked = $$('.day[data-date]', cal).some(function (d) {
          var x = d.getAttribute('data-date');
          return x > pickStart && x < v && d.tagName !== 'BUTTON';
        });
        if (blocked) {
          toast('Dazwischen ist das Gerät belegt. Bitte einen kürzeren Zeitraum wählen.');
          pickStart = v; markRange(v, null); bStart.value = v; bEnd.value = v; update();
          return;
        }
        bStart.value = pickStart; bEnd.value = v;
        markRange(pickStart, v);
        update();
        toast('Zeitraum: <b>' + de(pickStart) + ' – ' + de(v) + '</b> (' + days(pickStart, v) + ' Tage)');
        pickStart = null;
      });
      if (bStart.value && bEnd.value) { markRange(bStart.value, bEnd.value); }
    }
  }

  /* ---------- Belegungskalender: Zeitraum wählen ---------- */
  var tl = $('[data-timeline]:not([data-admin])');
  var bar = $('[data-range-bar]');
  if (tl && bar && window.SP_URLS) {
    var sel = null;
    var label = $('[data-range-label]', bar);
    var go = $('[data-range-go]', bar), prod = $('[data-range-product]', bar);
    var clear = function () {
      sel = null;
      $$('.tl-cell.is-sel, .tl-cell.is-range', tl).forEach(function (c) { c.classList.remove('is-sel', 'is-range'); });
      bar.hidden = true;
    };
    $('[data-range-clear]', bar).addEventListener('click', clear);
    tl.addEventListener('click', function (ev) {
      var cell = ev.target.closest('.tl-cell[data-date]');
      if (!cell || !(cell.classList.contains('st-free') || cell.classList.contains('st-partial'))) { return; }
      var row = cell.closest('[data-product]');
      var d = cell.getAttribute('data-date');
      if (d < window.SP_URLS.earliest) { toast('Dieser Tag ist leider zu kurzfristig.'); return; }
      if (!sel || sel.end || d < sel.start) {
        clear();
        sel = { start: d, end: null, row: row };
        cell.classList.add('is-sel');
        bar.hidden = false;
        label.textContent = de(d) + ' – … (Endtag anklicken)';
        go.href = window.SP_URLS.catalog + '?von=' + d + '&bis=' + d + '#katalog';
        prod.hidden = true;
        return;
      }
      sel.end = d;
      var sameRow = sel.row === row;
      var cells = $$('.tl-cell[data-date]', row);
      var blocked = false;
      cells.forEach(function (c) {
        var x = c.getAttribute('data-date');
        if (x >= sel.start && x <= d) {
          c.classList.add(x === sel.start || x === d ? 'is-sel' : 'is-range');
          if (sameRow && !(c.classList.contains('st-free') || c.classList.contains('st-partial'))) { blocked = true; }
        }
      });
      var n = days(sel.start, d);
      label.textContent = de(sel.start) + ' – ' + de(d) + ' · ' + n + (n === 1 ? ' Tag' : ' Tage') + (blocked ? ' (Gerät zwischendurch belegt)' : '');
      go.href = window.SP_URLS.catalog + '?von=' + sel.start + '&bis=' + d + '#katalog';
      if (sameRow && !blocked) {
        prod.hidden = false;
        prod.href = window.SP_URLS.product + '?id=' + row.getAttribute('data-product') + '&von=' + sel.start + '&bis=' + d;
      }
    });
  }
})();
