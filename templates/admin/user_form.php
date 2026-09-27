<?php /** Admin : édition utilisateur */ ?>
<div class="page-head"><h1><?= e($row['username']) ?></h1><p class="sub"><?= e($row['email']) ?></p></div>
<form method="post" class="form-card" style="max-width:560px">
    <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
    <div class="form-grid">
        <div class="field">
            <label>Rôle</label>
            <select name="role_id">
                <?php foreach ($roles as $r): ?>
                    <option value="<?= (int) $r['id'] ?>" <?= (int) $row['role_id'] === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?> (rang <?= (int) $r['rank'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Niveau de confiance</label>
            <select name="trusted_level">
                <?php foreach ([0 => 'Nouveau (0)', 1 => 'Standard (1)', 2 => 'De confiance (2)'] as $lvl => $lbl): ?>
                    <option value="<?= $lvl ?>" <?= (int) $row['trusted_level'] === $lvl ? 'selected' : '' ?>><?= e($lbl) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>&nbsp;</label>
            <div class="check"><input type="checkbox" id="ia" name="is_active" value="1" <?= $row['is_active'] ? 'checked' : '' ?>><label for="ia">Compte actif</label></div>
        </div>
        <div class="field">
            <label>&nbsp;</label>
            <div class="check"><input type="checkbox" id="av" name="artist_verified" value="1" <?= $row['artist_verified'] ? 'checked' : '' ?>><label for="av">Artiste vérifié</label></div>
        </div>
    </div>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Enregistrer</button>
        <a class="btn btn-outline" href="?r=/admin/users">Retour</a>
    </div>
</form>