<?php /** Vue carte Leaflet plein écran */ ?>
<div class="page-head">
    <h1>Carte des expositions</h1>
    <p class="sub">Lieux ayant au moins une exposition en cours (marqueurs colorés par type).</p>
</div>
<div class="map-legend" id="map-legend"></div>
<div id="map" role="application" aria-label="Carte des lieux d'expositions"></div>
<p class="attribution-note">Fond de carte © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributeurs.</p>

<script>
(function () {
    var map = L.map('map').setView([49.95, 2.3], 9);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18, attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var COLORS = ['#b4552d', '#1d2b3a', '#2e7d32', '#7b3fb5', '#1a56b8', '#ef6c00', '#c62828', '#00838f'];
    var colorFor = function (t) {
        var i = 0, h = 0, s = String(t || '');
        for (i = 0; i < s.length; i++) { h = (h * 31 + s.charCodeAt(i)) >>> 0; }
        return COLORS[h % COLORS.length];
    };

    fetch('?r=/carte/data.json', { headers: { 'Accept': 'application/json' } })
        .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(function (data) {
            var legend = {};
            (data.places || []).forEach(function (p) {
                var color = colorFor(p.type);
                legend[p.type || 'Autre'] = color;
                var m = L.circleMarker([p.lat, p.lng], {
                    radius: 7 + Math.min(p.ongoing, 5), color: '#fff', weight: 2,
                    fillColor: color, fillOpacity: 0.95
                }).addTo(map);
                m.bindPopup('<b>' + p.name + '</b><span class="muted small">' + (p.type || '') + ' · ' + (p.commune || '') + '</span><br>' +
                    p.ongoing + ' expo(s) en cours<br><a href="?r=/lieux/' + encodeURIComponent(p.slug) + '">Voir le lieu →</a>');
            });
            var lg = document.getElementById('map-legend');
            Object.keys(legend).sort().forEach(function (t) {
                var s = document.createElement('span');
                s.innerHTML = '<span class="dot" style="display:inline-block;width:.8em;height:.8em;border-radius:50%;background:' + legend[t] + ';margin-right:.3em"></span>' + t;
                lg.appendChild(s);
            });
        })
        .catch(function (err) {
            var lg = document.getElementById('map-legend');
            if (lg) lg.textContent = "Impossible de charger les données de la carte (" + err.message + ").";
        });
})();</script>
