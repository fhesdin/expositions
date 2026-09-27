<?php /** Désinscription newsletter */ ?>
<div class="page-head"><h1>Désinscription</h1></div>
<?php if ($ok): ?>
    <div class="flash flash-success">Vous avez été désinscrit(e) de la newsletter. À bientôt sur expositions.top !</div>
<?php else: ?>
    <div class="flash flash-error">Lien invalide.</div>
<?php endif; ?>
<p><a class="btn btn-primary" href="?r=/">← Retour à l'accueil</a></p>