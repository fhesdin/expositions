<?php ?>
<div class="page-head">
    <h1>Créer un compte</h1>
    <p class="sub">Pour suivre vos expositions, proposer des fiches et gérer un lieu ou votre page d'artiste.</p>
</div>
<form method="post" class="form-card" style="max-width:480px">
    <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
    <div class="form-grid">
        <div class="field">
            <label>Pseudo *</label>
            <input type="text" name="username" required minlength="3" maxlength="40" value="<?= e($form['username'] ?? '') ?>">
            <?= !empty($errors['username']) ? '<div class="field-error">' . e($errors['username']) . '</div>' : '' ?>
        </div>
        <div class="field">
            <label>E-mail *</label>
            <input type="email" name="email" required value="<?= e($form['email'] ?? '') ?>">
            <?= !empty($errors['email']) ? '<div class="field-error">' . e($errors['email']) . '</div>' : '' ?>
        </div>
        <div class="field">
            <label>Mot de passe * <span class="muted small">(min. 10 caractères)</span></label>
            <input type="password" name="password" required minlength="10">
            <?= !empty($errors['password']) ? '<div class="field-error">' . e($errors['password']) . '</div>' : '' ?>
        </div>
        <div class="field">
            <label>Confirmer le mot de passe *</label>
            <input type="password" name="password_confirm" required minlength="10">
        </div>
        <div class="field">
            <label>Commune (facultatif, pour « près de chez moi »)</label>
            <select name="commune_id">
                <option value="">—</option>
                <?php foreach ($communes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (string)($form['commune_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Je suis…</label>
            <select name="profile_kind">
                <option value="membre">Membre / visiteur</option>
                <option value="artiste">Artiste</option>
                <option value="lieu">Responsable de lieu (musée, galerie…)</option>
            </select>
        </div>
    </div>
    <div class="form-actions">
        <button class="btn btn-primary btn-block" type="submit">Créer mon compte</button>
    </div>
    <p class="small muted mt">Déjà inscrit(e) ? <a href="?r=/login">Se connecter</a></p>
</form>