<?php /** Admin : paramètres du site */ ?>
<div class="page-head"><h1>Paramètres</h1></div>
<form method="post" class="form-card">
    <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
    <div class="form-grid">
        <?php foreach ($settings as $s): ?>
            <div class="field">
                <label><?= e($s['key']) ?> <span class="muted small"><?= e($s['description'] ?? '') ?></span></label>
                <input type="text" name="settings[<?= e($s['key']) ?>]" value="<?= e($s['value']) ?>">
            </div>
        <?php endforeach; ?>
    </div>
    <div class="form-actions"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
</form>