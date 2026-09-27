<?php /** Formulaire artiste (création/édition) */
$isEdit = $row !== null;
$ferr = fn(string $k) => !empty($errors[$k]) ? '<div class="field-error">' . e($errors[$k]) . '</div>' : '';
?>
<form method="post" class="form-card">
    <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
    <div class="form-grid cols-2">
        <div class="field" style="grid-column:1/-1">
            <label>Nom / nom d'artiste *</label>
            <input type="text" name="name" required value="<?= e($form['name'] ?? '') ?>">
            <?= $ferr('name') ?>
        </div>
        <div class="field">
            <label>Disciplines</label>
            <input type="text" name="disciplines" value="<?= e($form['disciplines'] ?? '') ?>" placeholder="peinture, photographie…">
        </div>
        <div class="field">
            <label>Commune d'atelier</label>
            <select name="commune_id">
                <option value="">—</option>
                <?php foreach ($communes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (string)($form['commune_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field" style="grid-column:1/-1">
            <label>Biographie</label>
            <textarea name="bio"><?= e($form['bio'] ?? '') ?></textarea>
        </div>
        <div class="field" style="grid-column:1/-1">
            <label>Démarche artistique</label>
            <textarea name="statement"><?= e($form['statement'] ?? '') ?></textarea>
        </div>
        <div class="field" style="grid-column:1/-1">
            <label>Site / portfolio</label>
            <input type="url" name="website" value="<?= e($form['website'] ?? '') ?>">
        </div>
        <div class="field">
            <label>&nbsp;</label>
            <div class="check"><input type="checkbox" id="wo" name="workshop_open" value="1" <?= !empty($form['workshop_open']) ? 'checked' : '' ?>><label for="wo">Atelier ouvert au public</label></div>
        </div>
        <div class="field">
            <label>Infos atelier (horaires, adresse…)</label>
            <input type="text" name="workshop_info" value="<?= e($form['workshop_info'] ?? '') ?>">
        </div>
    </div>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Enregistrer' : 'Créer ma page' ?></button>
        <a class="btn btn-outline" href="<?= $isEdit ? '?r=/artistes/' . e($row['slug']) : '?r=/artistes' ?>">Annuler</a>
    </div>
</form>