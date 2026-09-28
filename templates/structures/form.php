<?php /** Formulaire structure (création/édition) */ ?>
<div class="page-head"><h1><?= e($title) ?></h1></div>

<?php if ($errors): ?>
<div class="flash flash-error">
    <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="post" class="form-card" action="<?= $isEdit ? '?r=/structures/' . (int) $row['id'] . '/edit' : '?r=/structures/create' ?>">
    <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">

    <div><label>Nom de la structure *</label>
        <input type="text" name="nom" maxlength="180" required value="<?= e($row['nom'] ?? '') ?>"></div>

    <div><label>Type *</label>
        <select name="type" required>
            <?php foreach ($types as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= ($row['type'] ?? '') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select></div>

    <div><label>Description</label>
        <textarea name="description" rows="4" maxlength="2000"><?= e($row['description'] ?? '') ?></textarea></div>

    <div><label>Commune</label>
        <select name="commune_id">
            <option value="">—</option>
            <?php foreach ($communes as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (int) ($row['commune_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
                <?= e($c['nom']) ?><?= $c['code_postal'] ? ' (' . e($c['code_postal']) . ')' : '' ?>
            </option>
            <?php endforeach; ?>
        </select></div>

    <div><label>Site web</label>
        <input type="url" name="website" maxlength="255" value="<?= e($row['website'] ?? '') ?>" placeholder="https://…"></div>

    <div><label>E-mail</label>
        <input type="email" name="email" maxlength="255" value="<?= e($row['email'] ?? '') ?>"></div>

    <div><label>Téléphone</label>
        <input type="tel" name="phone" maxlength="30" value="<?= e($row['phone'] ?? '') ?>"></div>

    <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Enregistrer' : 'Créer la structure' ?></button>
    <?php if (!$isEdit): ?>
    <p class="muted small">La structure sera vérifiée par l'équipe de modération avant publication. Vous en deviendrez le responsable.</p>
    <?php endif; ?>
</form>
