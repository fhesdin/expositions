<?php /** Confirmation newsletter (double opt-in) */ ?>
<div class="page-head"><h1>Newsletter</h1></div>
<?php if ($ok): ?>
    <div class="flash flash-success">Inscription confirmée ! Vous recevrez chaque semaine les vernissages et dernières chances de la Somme.</div>
<?php else: ?>
    <div class="flash flash-error">Lien invalide ou déjà utilisé.</div>
<?php endif; ?>
<p><a class="btn btn-primary" href="?r=/">← Retour à l'accueil</a></p>