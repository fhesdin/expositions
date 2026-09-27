<?php /** Formulaire lieu (création/édition) */
$isEdit = $row !== null;
$ferr = fn(string $k) => !empty($errors[$k]) ? '<div class="field-error">' . e($errors[$k]) . '</div>' : '';
?>
<form method="post" class="form-card">
    <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
    <div class="form-grid cols-2">
        <div class="field" style="grid-column:1/-1">
            <label>Nom du lieu *</label>
            <input type="text" name="name" required value="<?= e($form['name'] ?? '') ?>">
            <?= $ferr('name') ?>
        </div>
        <div class="field">
            <label>Type de lieu</label>
            <select name="type_id">
                <option value="">—</option>
                <?php foreach ($placeTypes as $t): ?>
                    <option value="<?= (int) $t['id'] ?>" <?= (string)($form['type_id'] ?? '') === (string)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Commune *</label>
            <select name="commune_id" required>
                <option value="">— choisir —</option>
                <?php foreach ($communes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (string)($form['commune_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $ferr('commune_id') ?>
        </div>
        <div class="field" style="grid-column:1/-1">
            <label>Adresse</label>
            <input type="text" name="address" value="<?= e($form['address'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Latitude</label>
            <input type="number" step="0.000001" name="latitude" value="<?= e($form['latitude'] ?? '') ?>" placeholder="49.894">
        </div>
        <div class="field">
            <label>Longitude</label>
            <input type="number" step="0.000001" name="longitude" value="<?= e($form['longitude'] ?? '') ?>" placeholder="2.296">
        </div>
        <div class="field">
            <label>Téléphone</label>
            <input type="text" name="phone" value="<?= e($form['phone'] ?? '') ?>">
        </div>
        <div class="field">
            <label>E-mail</label>
            <input type="email" name="email" value="<?= e($form['email'] ?? '') ?>">
        </div>
        <div class="field" style="grid-column:1/-1">
            <label>Site web</label>
            <input type="url" name="website" value="<?= e($form['website'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Horaires</label>
            <input type="text" name="opening_hours" value="<?= e($form['opening_hours'] ?? '') ?>" placeholder="mer.–dim. 14h–18h">
        </div>
        <div class="field">
            <label>Jours de fermeture</label>
            <input type="text" name="access_info" value="<?= e($form['access_info'] ?? '') ?>" placeholder="lundi, mardi">
        </div>
        <div class="field" style="grid-column:1/-1">
            <label>Description</label>
            <textarea name="description"><?= e($form['description'] ?? '') ?></textarea>
        </div>
        <div class="field" style="grid-column:1/-1">
            <label>&nbsp;</label>
            <div class="check"><input type="checkbox" id="pmr" name="access_pmr" value="1" <?= !empty($form['access_pmr']) ? 'checked' : '' ?>><label for="pmr">Accessible PMR</label></div>
        </div>
    </div>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Enregistrer' : 'Créer le lieu' ?></button>
        <a class="btn btn-outline" href="<?= $isEdit ? '?r=/lieux/' . e($row['slug']) : '?r=/lieux' ?>">Annuler</a>
    </div>
</form>