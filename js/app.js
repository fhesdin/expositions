/* expositions.top — interactions front */
(function () {
  'use strict';

  // fermeture des dropdowns au clic extérieur
  document.addEventListener('click', function (ev) {
    document.querySelectorAll('.main-nav details[open]').forEach(function (d) {
      if (!d.contains(ev.target)) { d.removeAttribute('open'); }
    });
  });

  // autocomplétion recherche
  var input = document.querySelector('[data-suggest]');
  if (input) {
    var box = null;
    var timer = null;
    var close = function () { if (box) { box.remove(); box = null; } };
    input.addEventListener('input', function () {
      clearTimeout(timer);
      var q = input.value.trim();
      if (q.length < 2) { close(); return; }
      timer = setTimeout(function () {
        fetch('?r=/recherche/suggest.json&q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            close();
            if (!data.results || !data.results.length) { return; }
            box = document.createElement('div');
            box.className = 'search-suggest';
            data.results.forEach(function (r) {
              var a = document.createElement('a');
              a.href = '?r=' + encodeURIComponent(r.url.replace(/^\//, ''));
              var t = document.createElement('span'); t.className = 'st'; t.textContent = r.type;
              var l = document.createElement('span'); l.textContent = r.label;
              a.appendChild(t); a.appendChild(l);
              box.appendChild(a);
            });
            input.parentNode.style.position = 'relative';
            input.parentNode.appendChild(box);
          }).catch(function () {});
      }, 180);
    });
    document.addEventListener('click', function (ev) {
      if (box && !box.contains(ev.target) && ev.target !== input) { close(); }
    });
  }

  // confirmation avant action destructive
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (ev) {
      if (!window.confirm(f.getAttribute('data-confirm'))) { ev.preventDefault(); }
    });
  });

  // favori / J'y vais en fetch (fallback POST normal si JS off)
  document.querySelectorAll('[data-ajax-toggle]').forEach(function (b) {
    b.addEventListener('click', function (ev) {
      ev.preventDefault();
      var form = b.closest('form');
      var fd = new FormData(form);
      fetch(form.getAttribute('action'), {
        method: 'POST', body: fd,
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      }).then(function (r) { return r.json(); }).then(function (d) {
        if (d && d.success) { window.location.reload(); }
      }).catch(function () { form.submit(); });
    });
  });

  // partage (copie du lien)
  document.querySelectorAll('[data-copy-link]').forEach(function (b) {
    b.addEventListener('click', function () {
      var url = window.location.href;
      (navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).catch(function () {
        var ta = document.createElement('textarea');
        ta.value = url; document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); } catch (e) {}
        ta.remove();
      });
      var old = b.textContent;
      b.textContent = 'Lien copié ✓';
      setTimeout(function () { b.textContent = old; }, 1600);
    });
  });
})();