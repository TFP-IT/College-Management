/*
 * Lists in the school portals: adds a search box, filters for short columns such as Status,
 * sortable headings, a row count and paging to every read-only table on the page.
 * Tables that collect input, live in a dialog, or carry data-plain are left alone.
 */
(function () {
  'use strict';

  var SKIP_HEAD = /^(#|sl|no\.?|action|actions|option|options|photo|image|)$/i;
  var uid = 0;

  function ready(fn) {
    if (document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn);
  }

  function text(cell) {
    return (cell ? cell.textContent : '').replace(/\s+/g, ' ').trim();
  }

  /** A comparable value: numbers (taka, commas, %), dates, then plain text. */
  function key(value) {
    var n = value.replace(/[৳,%\s]|BDT|Tk\.?/gi, '');
    if (/^-?\d+(\.\d+)?$/.test(n)) return { t: 0, v: parseFloat(n) };
    if (/\d{1,2}[\s-][A-Za-z]{3}[\s-,]+\d{4}|\d{4}-\d{2}-\d{2}|[A-Za-z]{3,9}\s\d{1,2},?\s\d{4}/.test(value)) {
      var d = Date.parse(value.replace(/(\d{1,2})-([A-Za-z]{3})-(\d{4})/, '$1 $2 $3'));
      if (!isNaN(d)) return { t: 1, v: d };
    }
    return { t: 2, v: value.toLowerCase() };
  }

  function el(tag, attrs, children) {
    var node = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      if (k === 'text') node.textContent = attrs[k];
      else if (k === 'html') node.innerHTML = attrs[k];
      else node.setAttribute(k, attrs[k]);
    });
    (children || []).forEach(function (c) { if (c) node.appendChild(c); });
    return node;
  }

  function enhance(table) {
    if (table.dataset.enhanced || table.hasAttribute('data-plain')) return;
    if (table.closest('.modal, dialog, .fc, .mcc-dash, .sheet, .offcanvas, .dropdown-menu, [data-plain]')) return;
    if (!table.tHead || !table.tHead.rows.length || !table.tBodies.length) return;
    // Hidden copies (kept for printing or export) and tables that collect input are left alone.
    if (table.offsetParent === null) return;
    var editable = Array.prototype.some.call(table.querySelectorAll('tbody input:not([type=checkbox]):not([type=hidden]):not([type=submit]), tbody select, tbody textarea'), function (f) {
      return !f.closest('dialog, .modal, .dropdown-menu');
    });
    if (editable) return;

    var body = table.tBodies[0];
    var rows = Array.prototype.filter.call(body.rows, function (r) {
      return r.cells.length > 1 && !(r.cells.length === 1 || r.querySelector('td[colspan]') && r.cells.length < 3);
    });
    if (rows.length < 3) return;
    table.dataset.enhanced = '1';
    table.classList.add('mcc-list');

    var heads = Array.prototype.slice.call(table.tHead.rows[table.tHead.rows.length - 1].cells);
    var section = table.closest('.eSection-wrap, .eSection-wrap-2, .panel, .card, .eCard, .table-responsive') || table.parentNode;
    var holder = section.closest('.eSection-wrap, .eSection-wrap-2, .panel') || section;
    var serverPaged = !!(holder.querySelector('.pagination, .pager'));
    var serverSearch = !!holder.querySelector('input[type=search], input[name=search], .search-input input, form[role=search] input');
    var id = 'mcc-list-' + (++uid);
    var state = { q: '', filters: {}, sort: null, page: 1, size: serverPaged ? 0 : (rows.length > 25 ? 25 : 0) };

    rows.forEach(function (r, i) {
      r.dataset.order = i;
      r.dataset.search = text(r).toLowerCase();
    });

    // Filters: short columns with only a few different values, such as Status or Gender.
    var filters = [];
    heads.forEach(function (th, col) {
      var name = text(th);
      if (SKIP_HEAD.test(name) || filters.length >= 3) return;
      var values = {};
      var badges = 0, filled = 0, plain = 0;
      rows.forEach(function (r) {
        var cell = r.cells[col];
        var v = text(cell);
        if (!v) return;
        filled++;
        if (v.length > 28) return;
        values[v] = (values[v] || 0) + 1;
        if (key(v).t === 2) plain++;
        if (cell && cell.querySelector('.badge, .eBadge, [class*="badge"], .mcc-badge')) badges++;
      });
      var distinct = Object.keys(values);
      // Worth a filter: a short, mostly filled column of words (not amounts or dates) with a few values.
      if (distinct.length < 2 || distinct.length > 6 || filled < rows.length * 0.7 || plain < filled * 0.8) return;
      if (!badges && !/status|state|type|gender|class|section|method|category|role|group|kind|level|paid|subject|department|blood/i.test(name)) return;
      filters.push({ col: col, name: name, values: distinct.sort() });
    });

    // Toolbar
    var search = serverSearch ? null : el('label', { class: 'mcc-list-search' }, [
      el('span', { class: 'mcc-sr', text: 'Search this list' }),
      el('input', { type: 'search', placeholder: serverPaged ? 'Search this page' : 'Search this list', 'aria-controls': id }),
    ]);
    var selects = filters.map(function (f) {
      var select = el('select', { 'aria-label': 'Filter by ' + f.name, 'aria-controls': id });
      select.appendChild(el('option', { value: '', text: 'All ' + f.name.toLowerCase() }));
      f.values.forEach(function (v) { select.appendChild(el('option', { value: v, text: v })); });
      select.addEventListener('change', function () { state.filters[f.col] = select.value; state.page = 1; render(); });
      return select;
    });
    var clear = el('button', { type: 'button', class: 'mcc-list-clear', text: 'Clear filters', hidden: '' });
    var count = el('span', { class: 'mcc-list-count', 'aria-live': 'polite' });
    var size = serverPaged || rows.length <= 10 ? null : el('select', { 'aria-label': 'Rows per page' });
    if (size) {
      [10, 25, 50, 0].forEach(function (n) { size.appendChild(el('option', { value: n, text: n ? n + ' per page' : 'Show all' })); });
      size.value = String(state.size);
      size.addEventListener('change', function () { state.size = parseInt(size.value, 10); state.page = 1; render(); });
    }
    var bar = el('div', { class: 'mcc-list-bar' }, [search].concat(selects, [clear, el('span', { class: 'mcc-list-gap' }), count, size]));
    // With nothing to search or choose, the bar only carries the count and stays out of the way.
    if (!search && !selects.length && !size) bar.classList.add('quiet');
    var wrap = table.closest('.table-responsive, .table-wrap, .mcc-table-wrap') || table;
    wrap.parentNode.insertBefore(bar, wrap);
    table.id = table.id || id;
    if (table.id !== id) { bar.querySelectorAll('[aria-controls]').forEach(function (n) { n.setAttribute('aria-controls', table.id); }); }

    var pager = el('div', { class: 'mcc-list-pager', hidden: '' });
    wrap.parentNode.insertBefore(pager, wrap.nextSibling);

    var empty = el('tr', { class: 'mcc-list-empty', hidden: '' }, [
      el('td', { colspan: String(heads.length) }, [
        el('span', { text: 'No rows match these filters.' }),
      ]),
    ]);
    var emptyClear = el('button', { type: 'button', text: 'Clear filters' });
    empty.firstChild.appendChild(emptyClear);
    body.appendChild(empty);

    if (search) {
      var input = search.querySelector('input');
      var timer;
      input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () { state.q = input.value.trim().toLowerCase(); state.page = 1; render(); }, 120);
      });
    }

    function reset() {
      state.q = ''; state.filters = {}; state.page = 1;
      if (search) search.querySelector('input').value = '';
      selects.forEach(function (s) { s.value = ''; });
      render();
    }
    clear.addEventListener('click', reset);
    emptyClear.addEventListener('click', reset);

    // Sorting
    heads.forEach(function (th, col) {
      var name = text(th);
      if (SKIP_HEAD.test(name) || th.querySelector('input, button, a')) return;
      var button = el('button', { type: 'button', class: 'mcc-sort' });
      while (th.firstChild) button.appendChild(th.firstChild);
      button.appendChild(el('span', { class: 'mcc-sort-mark', 'aria-hidden': 'true' }));
      th.appendChild(button);
      th.setAttribute('aria-sort', 'none');
      button.addEventListener('click', function () {
        var dir = state.sort && state.sort.col === col && state.sort.dir === 'ascending' ? 'descending' : 'ascending';
        state.sort = { col: col, dir: dir };
        heads.forEach(function (h) { if (h.hasAttribute('aria-sort')) h.setAttribute('aria-sort', 'none'); });
        th.setAttribute('aria-sort', dir);
        render();
      });
    });

    function render() {
      var shown = rows.filter(function (r) {
        if (state.q && r.dataset.search.indexOf(state.q) === -1) return false;
        return Object.keys(state.filters).every(function (col) {
          return !state.filters[col] || text(r.cells[col]) === state.filters[col];
        });
      });
      var ordered = rows.slice();
      if (state.sort) {
        var c = state.sort.col, sign = state.sort.dir === 'ascending' ? 1 : -1;
        ordered.sort(function (a, b) {
          var x = key(text(a.cells[c])), y = key(text(b.cells[c]));
          if (x.t !== y.t) return (x.t - y.t) * sign;
          return (x.v < y.v ? -1 : x.v > y.v ? 1 : 0) * sign || a.dataset.order - b.dataset.order;
        });
      }
      ordered.forEach(function (r) { body.insertBefore(r, empty); });

      var pages = state.size ? Math.max(1, Math.ceil(shown.length / state.size)) : 1;
      state.page = Math.min(state.page, pages);
      var from = state.size ? (state.page - 1) * state.size : 0;
      var to = state.size ? from + state.size : shown.length;
      var visible = {};
      ordered.filter(function (r) { return shown.indexOf(r) !== -1; }).slice(from, to).forEach(function (r) { visible[r.dataset.order] = true; });
      rows.forEach(function (r) { r.hidden = !visible[r.dataset.order]; });

      var active = !!state.q || Object.keys(state.filters).some(function (k) { return state.filters[k]; });
      clear.hidden = !active;
      empty.hidden = shown.length > 0;
      count.textContent = shown.length === rows.length
        ? rows.length + (rows.length === 1 ? ' row' : ' rows') + (serverPaged ? ' on this page' : '')
        : 'Showing ' + shown.length + ' of ' + rows.length;

      pager.hidden = pages < 2;
      pager.innerHTML = '';
      if (pages > 1) {
        pager.appendChild(el('span', { text: (from + 1) + ' to ' + Math.min(to, shown.length) + ' of ' + shown.length }));
        var prev = el('button', { type: 'button', text: 'Previous' });
        var next = el('button', { type: 'button', text: 'Next' });
        prev.disabled = state.page === 1;
        next.disabled = state.page === pages;
        prev.addEventListener('click', function () { state.page--; render(); });
        next.addEventListener('click', function () { state.page++; render(); });
        pager.appendChild(el('span', { class: 'mcc-list-gap' }));
        pager.appendChild(prev);
        pager.appendChild(el('b', { text: state.page + ' / ' + pages }));
        pager.appendChild(next);
      }
    }

    render();
  }

  ready(function () {
    var root = document.querySelector('.main_content, main.main');
    if (!root) return;
    Array.prototype.forEach.call(root.querySelectorAll('table'), enhance);
  });
})();
