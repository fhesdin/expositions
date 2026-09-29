<?php
/** Formulaire fiche expo (création/édition, même vue) — contributeur connecté.
 * $form : valeurs ; $errors ; $row : null si création ; $places, $categories, $communes, $allArtists, $selectedArtists
 */
$isEdit = $row !== null;
$ferr = fn(string $k) => !empty($errors[$k]) ? '<div class="field-error">' . e($errors[$k]) . '</div>' : '';
?>
<form method="post" class="form-card" enctype="multipart/form-data" action="<?= $isEdit ? '?r=/expositions/' . (int) $row['id'] . '/edit' : '?r=/expositions/create' ?>">
    <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
    <?php if ($isEdit): ?><input type="hidden" name="current_slug" value="<?= e($row['slug']) ?>"><?php endif; ?>

    <fieldset class="form-section">
        <legend>Essentiel</legend>
        <div class="form-grid cols-2">
            <div class="field" style="grid-column:1/-1">
                <label for="title">Titre *</label>
                <input id="title" type="text" name="title" required value="<?= e($form['title'] ?? '') ?>">
                <?= $ferr('title') ?>
            </div>
            <div class="field">
                <label for="slug">Slug (URL)</label>
                <input id="slug" type="text" name="slug" value="<?= e($form['slug'] ?? '') ?>" placeholder="généré automatiquement si vide">
                <div class="hint">Lettres minuscules et tirets.</div>
                <?= $ferr('slug') ?>
            </div>
            <div class="field">
                <label for="category_id">Catégorie</label>
                <select id="category_id" name="category_id">
                    <option value="">—</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (string)($form['category_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="grid-column:1/-1">
                <label for="summary">Résumé *</label>
                <textarea id="summary" name="summary" required placeholder="2 à 5 phrases qui donnent envie…"><?= e($form['summary'] ?? '') ?></textarea>
                <?= $ferr('summary') ?>
            </div>
            <div class="field" style="grid-column:1/-1">
                <label for="description">Description longue</label>
                <textarea id="description" name="description" style="min-height:160px"><?= e($form['description'] ?? '') ?></textarea>
            </div>
            <div class="field">
                <label for="poster">Affiche (image)</label>
                <input id="poster" type="file" name="poster" accept="image/*">
                <?php if (!empty($form['poster_path'])): ?><div class="hint">Affiche actuelle : <?= e($form['poster_path']) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label for="website">Site / billetterie</label>
                <input id="website" type="url" name="website" value="<?= e($form['website'] ?? '') ?>" placeholder="https://…">
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Lieu</legend>
        <div class="form-grid cols-2">
            <div class="field">
                <label for="place_id">Lieu recensé</label>
                <select id="place_id" name="place_id">
                    <option value="">— autre / non recensé —</option>
                    <?php foreach ($places as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= (string)($form['place_id'] ?? '') === (string)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?> (<?= e($p['commune_name'] ?? '') ?>)</option>
                    <?php endforeach; ?>
                </select>
                <div class="hint">Le lieu n'existe pas ? <a href="?r=/lieux/create" target="_blank">Créez-le d'abord</a>.</div>
            </div>
            <div class="field">
                <label for="place_free_text">Lieu libre (si non recensé)</label>
                <input id="place_free_text" type="text" name="place_free_text" value="<?= e($form['place_free_text'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="structure_id">Organisée par (structure)</label>
                <select id="structure_id" name="structure_id">
                    <option value="">— aucune / indépendante —</option>
                    <?php foreach (($myStructures ?? []) as $st): ?>
                        <option value="<?= (int) $st['id'] ?>" <?= (string)($form['structure_id'] ?? '') === (string)$st['id'] ? 'selected' : '' ?>><?= e($st['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="hint">Seules vos structures (membre +) apparaissent. <a href="?r=/structures/create" target="_blank">Créer une structure</a>.</div>
            </div>
            <div class="field">
                <label for="commune_id">Commune *</label>
                <select id="commune_id" name="commune_id" required>
                    <option value="">— choisir —</option>
                    <?php foreach ($communes as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (string)($form['commune_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $ferr('commune_id') ?>
            </div>
            <div class="field">
                <label>&nbsp;</label>
                <div class="check"><input type="checkbox" id="is_permanent" name="is_permanent" value="1" <?= !empty($form['is_permanent']) ? 'checked' : '' ?>><label for="is_permanent">Exposition permanente</label></div>
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Dates</legend>
        <div class="form-grid cols-2">
            <div class="field">
                <label for="starts_at">Début</label>
                <input id="starts_at" type="date" name="starts_at" value="<?= e($form['starts_at'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="ends_at">Fin</label>
                <input id="ends_at" type="date" name="ends_at" value="<?= e($form['ends_at'] ?? '') ?>">
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Horaires &amp; accès</legend>
        <div class="form-grid cols-2">
            <div class="field">
                <label for="opening_hours">Horaires</label>
                <input id="opening_hours" type="text" name="opening_hours" value="<?= e($form['opening_hours'] ?? '') ?>" placeholder="mer.–dim. 14h–18h">
            </div>
            <div class="field">
                <label for="access_info">Jours de fermeture</label>
                <input id="access_info" type="text" name="access_info" value="<?= e($form['access_info'] ?? '') ?>" placeholder="lundi, mardi">
            </div>
            <div class="field">
                <label for="access_pmr">Accessibilité</label>
                <div class="check"><input type="checkbox" id="access_pmr" name="access_pmr" value="1" <?= !empty($form['access_pmr']) ? 'checked' : '' ?>><label for="access_pmr">Accessible PMR</label></div>
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Tarifs</legend>
        <div class="form-grid cols-3">
            <div class="field">
                <label for="price_kind">Type de tarification</label>
                <select id="price_kind" name="price_kind">
                    <?php foreach (['payant' => 'Payant', 'gratuit' => 'Gratuit', 'reservation' => 'Sur réservation', 'libre' => 'Prix libre'] as $k => $lbl): ?>
                        <option value="<?= e($k) ?>" <?= ($form['price_kind'] ?? 'payant') === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="price_detail">Tarifs</label>
                <input id="price_detail" type="text" name="price_detail" value="<?= e($form['price_detail'] ?? '') ?>" placeholder="Plein tarif 8 €, réduit 5 €…">
            </div>
            <div class="field">

            </div>
            <div class="field" style="grid-column:1/-1">

            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Disciplines</legend>
        <div style="display:flex;flex-wrap:wrap;gap:.35rem 1rem">
            <?php foreach (($disciplines ?? []) as $d): ?>
            <label class="check">
                <input type="checkbox" name="discipline_ids[]" value="<?= (int) $d['id'] ?>"
                    <?= in_array((int) $d['id'], array_map('intval', $selectedDisciplines ?? []), true) ? 'checked' : '' ?>>
                <?= e($d['name']) ?>
            </label>
            <?php endforeach; ?>
        </div>
        <div class="hint">Pratiques représentées dans l'exposition (liste établie par l'équipe du site).</div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Artistes exposé(e)s</legend>
        <?php if (!empty($allArtists)): ?>
        <div style="display:flex;gap:.5rem;max-width:520px">
            <select id="artist-select" style="flex:1">
                <option value="">— Choisir un artiste —</option>
                <?php foreach ($allArtists as $a): ?>
                <option value="<?= (int) $a['id'] ?>" data-name="<?= e($a['name']) ?>"><?= e($a['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary btn-sm" type="button" id="artist-add">＋ Ajouter</button>
        </div>
        <ul id="artist-list" style="list-style:none;padding:0;margin:.75rem 0 0;display:flex;flex-wrap:wrap;gap:.4rem"></ul>
        <div class="hint">L'artiste manque ? <a href="?r=/artistes/create" target="_blank">Créez sa page</a>.</div>
        <?php else: ?>
            <p class="muted small">Aucun artiste recensé pour l'instant — <a href="?r=/artistes/create">créez le premier</a>.</p>
        <?php endif; ?>
    </fieldset>

    <fieldset class="form-section">
        <legend>Tags</legend>
        <div class="field">
            <input type="text" name="tags" value="<?= e($form['tags'] ?? '') ?>" placeholder="photographie, baie de somme, art contemporain…">
            <div class="hint">Séparés par des virgules. Les nouveaux tags sont soumis à validation.</div>
        </div>
    </fieldset>

    <div class="form-actions">
        <?php if ($isEdit): ?>
            <button class="btn btn-primary" type="submit">Enregistrer</button>
            <a class="btn btn-outline" href="?r=/expositions/<?= e($row['slug']) ?>">Annuler</a>
        <?php else: ?>
            <button class="btn btn-primary" type="submit" name="action" value="submit">Envoyer à la modération</button>
            <?php if (\Core\Auth::isTrusted()): ?>
                <button class="btn btn-outline" type="submit" name="action" value="draft">Enregistrer en brouillon</button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</form>

<script>
(function () {
    var sel = document.getElementById('artist-select');
    if (!sel) return;
    var addBtn = document.getElementById('artist-add');
    var list = document.getElementById('artist-list');
    var chosen = {};

    function restore(id, name) {
        chosen[id] = name;
        render();
    }
    function render() {
        list.innerHTML = '';
        Object.keys(chosen).sort(function (a, b) { return chosen[a].localeCompare(chosen[b]); }).forEach(function (id) {
            var li = document.createElement('li');
            li.style.cssText = 'background:var(--chip-bg,#efe9e4);border-radius:999px;padding:.3rem .75rem;display:inline-flex;align-items:center;gap:.4rem;font-size:.9rem';
            li.innerHTML = '<span>' + chosen[id].replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }) + '</span>';
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = '×';
            btn.style.cssText = 'border:0;background:none;cursor:pointer;font-size:1rem;line-height:1;color:#7a3b2e';
            btn.onclick = function () { delete chosen[id]; render(); };
            li.appendChild(btn);
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'artist_ids[]';
            hidden.value = id;
            li.appendChild(hidden);
            list.appendChild(li);
        });
    }
    addBtn.addEventListener('click', function () {
        var id = sel.value;
        if (!id || chosen[id]) { sel.value = ''; return; }
        restore(id, sel.options[sel.selectedIndex].getAttribute('data-name') || sel.options[sel.selectedIndex].text);
        sel.value = '';
    });
    <?php foreach (($selectedArtists ?? []) as $pre): ?>
    (function () {
        var id = '<?= (int) $pre ?>';
        var opt = sel.querySelector('option[value="' + id + '"]');
        if (opt) { restore(id, opt.getAttribute('data-name') || opt.text); }
    })();
    <?php endforeach; ?>
})();
</script>