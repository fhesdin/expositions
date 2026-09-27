<?php ?>
<div class="page-head"><h1>Connexion</h1></div>
<form method="post" class="form-card" style="max-width:420px">
    <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
    <div class="form-grid">
        <div class="field">
            <label>E-mail ou pseudo</label>
            <input type="text" name="identifier" required autofocus value="<?= e($form['identifier'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Mot de passe</label>
            <input type="password" name="password" required>
        </div>
        <div class="check"><input type="checkbox" id="remember" name="remember" value="1"><label for="remember">Rester connecté(e) 30 jours</label></div>
    </div>
    <div class="form-actions">
        <button class="btn btn-primary btn-block" type="submit">Se connecter</button>
    </div>
    <p class="small muted mt">Pas de compte ? <a href="?r=/register">Créer un compte</a> · <a href="?r=/password/forgot">Mot de passe oublié ?</a></p>
</form>