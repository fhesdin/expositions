<?php ?>
<div class="page-head"><h1>Nouveau mot de passe</h1></div>
<form method="post" class="form-card" style="max-width:420px">
    <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
    <div class="form-grid">
        <div class="field"><label>Nouveau mot de passe (min. 10)</label><input type="password" name="password" required minlength="10"></div>
        <div class="field"><label>Confirmer</label><input type="password" name="password_confirm" required minlength="10"></div>
    </div>
    <div class="form-actions"><button class="btn btn-primary btn-block" type="submit">Enregistrer</button></div>
</form>