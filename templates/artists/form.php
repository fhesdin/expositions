<?php /** Formulaire artiste (création/édition) */
$isEdit = $row !== null;
$ferr = fn(string $k) => !empty($errors[$k]) ? '<div class="field-error">' . e($errors[$k]) . '</div>' : '';
?>
<form method="post" enctype="multipart/form-data" class="form-card" action="<?= $isEdit ? '?r=/artistes/' . (int) $row['id'] . '/edit' : '?r=/artistes/create' ?>">
    <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
    <input type="hidden" name="current_slug" value="<?= e($row['slug'] ?? '') ?>">
    <div class="form-grid cols-2">
        <div class="field" style="grid-column:1/-1">
            <label>Nom / nom d'artiste *</label>
            <input type="text" name="name" required value="<?= e($form['name'] ?? '') ?>">
            <?= $ferr('name') ?>
        </div>
        <div class="field">
            <label>Disciplines</label>
            <div style="display:flex;flex-wrap:wrap;gap:.35rem 1rem">
                <?php foreach (($disciplines ?? []) as $d): ?>
                <label class="check">
                    <input type="checkbox" name="discipline_ids[]" value="<?= (int) $d['id'] ?>"
                        <?= in_array((int) $d['id'], array_map('intval', array_column($selectedDisciplines ?? [], 'id')), true) ? 'checked' : '' ?>>
                    <?= e($d['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="hint">Liste établie par l'équipe du site.</div>
        </div>
        <div class="field">
            <label>Photo / portrait (PNG/JPG, 3 Mo max)</label>
            <input type="file" name="photo" accept="image/png,image/jpeg">
            <?php if (!empty($row['image_path'])): ?>
            <div class="hint"><img src="/uploads/artists/<?= e($row['image_path']) ?>" alt="Photo actuelle" style="width:64px;height:64px;object-fit:cover;border-radius:50%"> Remplacer par un nouveau fichier, ou laisser vide.</div>
            <?php endif; ?>
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
<?php if ($isEdit && (\Core\Auth::isModerator() || (int) ($row['user_id'] ?? 0) === \Core\Auth::id())): ?>
<form method="post" action="?r=/artistes/<?= (int) $row['id'] ?>/delete" onsubmit="return confirm('Supprimer définitivement cet artiste ?')">
    <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
    <button class="btn btn-danger" type="submit">🗑 Supprimer définitivement cet artiste</button>
</form>
<?php endif; ?>
