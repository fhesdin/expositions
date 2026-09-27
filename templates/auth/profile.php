<?php ?>
<div class="page-head"><h1>Mon profil</h1></div>
<div class="form-grid cols-2">
    <form method="post" class="form-card">
        <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
        <h3>Informations</h3>
        <div class="form-grid">
            <div class="field"><label>Pseudo</label><input type="text" value="<?= e($user['username']) ?>" disabled></div>
            <div class="field"><label>E-mail</label><input type="email" value="<?= e($user['email']) ?>" disabled></div>
            <div class="field">
                <label>Commune</label>
                <select name="commune_id">
                    <option value="">—</option>
                    <?php foreach ($communes as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (string)($user['commune_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Newsletter hebdo</label>
                <div class="check"><input type="checkbox" id="nl" name="notify_weekly" value="1" <?= (int)($user["notify_weekly"] ?? 0) === 1 ? "checked" : "" ?>><label for="nl">Recevoir les vernissages et dernières chances</label></div>
            </div>
        </div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
    </form>

    <form method="post" class="form-card" action="?r=/profile/password">
        <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
        <h3>Mot de passe</h3>
        <div class="form-grid">
            <div class="field"><label>Mot de passe actuel</label><input type="password" name="current_password" required></div>
            <div class="field"><label>Nouveau mot de passe (min. 10)</label><input type="password" name="new_password" required minlength="10"></div>
        </div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Changer le mot de passe</button></div>
        <hr style="border-color:var(--c-line)">
        <h3>Mon flux d'agenda (iCal)</h3>
        <p class="small muted">Copiez ce lien dans Google Agenda / Apple Calendrier → « À partir de l'URL » :</p>
        <div class="field"><input type="text" value="<?= e($icalUrl) ?>" readonly onclick="this.select()"></div>
    </form>
</div>