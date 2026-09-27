<?php
/** Proposition d'expo SANS compte (modérée avant publication)
 * Champs attendus par le contrôleur : title, place_id, commune (texte libre), d1, d2, summary, contact_email.
 */
$ferr = fn(string $k) => !empty($errors[$k]) ? '<div class="field-error">' . e($errors[$k]) . '</div>' : '';
?>
<div class="page-head">
    <h1>Proposer une exposition</h1>
    <p class="sub">Sans compte, en 2 minutes. Un modérateur vérifiera l'information avant publication — vous serez prévenu(e) par e-mail.</p>
</div>

<form method="post" class="form-card">
    <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
    <div class="form-grid cols-2">
        <div class="field" style="grid-column:1/-1">
            <label for="title">Titre de l'exposition *</label>
            <input id="title" type="text" name="title" required value="<?= e($form['title'] ?? '') ?>">
            <?= $ferr('title') ?>
        </div>
        <div class="field">
            <label for="place_id">Lieu recensé (si connu)</label>
            <select id="place_id" name="place_id">
                <option value="">— autre lieu —</option>
                <?php foreach (($places ?? []) as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= (string)($form['place_id'] ?? '') === (string)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?><?= !empty($p['commune_name']) ? ' (' . e($p['commune_name']) . ')' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="commune">Commune *</label>
            <input id="commune" type="text" name="commune" required value="<?= e($form['commune'] ?? '') ?>" placeholder="ex. Amiens" list="communes-list">
            <datalist id="communes-list">
                <?php foreach (($communes ?? []) as $c): ?><option value="<?= e($c['nom']) ?>"></option><?php endforeach; ?>
            </datalist>
            <?= $ferr('commune') ?>
        </div>
        <div class="field">
            <label for="d1">Date de début</label>
            <input id="d1" type="date" name="d1" value="<?= e($form['d1'] ?? '') ?>">
        </div>
        <div class="field">
            <label for="d2">Date de fin</label>
            <input id="d2" type="date" name="d2" value="<?= e($form['d2'] ?? '') ?>">
        </div>
        <div class="field" style="grid-column:1/-1">
            <label for="summary">Résumé *</label>
            <textarea id="summary" name="summary" required placeholder="De quoi s'agit-il ?"><?= e($form['summary'] ?? '') ?></textarea>
            <?= $ferr('summary') ?>
        </div>
        <div class="field">
            <label for="contact_email">Votre e-mail * <span class="muted small">(pour vous prévenir)</span></label>
            <input id="contact_email" type="email" name="contact_email" required value="<?= e($form['contact_email'] ?? '') ?>">
            <?= $ferr('contact_email') ?>
        </div>
    </div>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Envoyer la proposition</button>
        <a class="btn btn-outline" href="?r=/">Annuler</a>
    </div>
</form>