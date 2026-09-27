<?php ?>
<div class="page-head"><h1>Mot de passe oublié</h1></div>
<form method="post" class="form-card" style="max-width:420px">
    <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
    <div class="field">
        <label>E-mail du compte</label>
        <input type="email" name="email" required value="<?= e($form['email'] ?? '') ?>">
    </div>
    <div class="form-actions"><button class="btn btn-primary btn-block" type="submit">Recevoir le lien de réinitialisation</button></div>
    <p class="small muted mt"><a href="?r=/login">← Retour à la connexion</a></p>
</form>