<?php /** Formulaire événement (vernissage…) rattaché à une expo */ ?>
<form method="post" class="form-card" action="?r=/expositions/<?= (int) $expo['id'] ?>/events">
    <input type="hidden" name="_csrf" value="<?= e(\Core\Csrf::token()) ?>">
    <div class="page-head"><h1>Nouvel événement — <?= e($expo['title']) ?></h1></div>
    <div class="form-grid cols-2">
        <div class="field" style="grid-column:1/-1">
            <label>Titre *</label>
            <input type="text" name="title" required placeholder="Vernissage, visite guidée, rencontre…">
        </div>
        <div class="field">
            <label>Début *</label>
            <input type="datetime-local" name="starts_at" required>
        </div>
        <div class="field">
            <label>Fin (optionnel)</label>
            <input type="datetime-local" name="ends_at">
        </div>
        <div class="field" style="grid-column:1/-1">
            <label>Description</label>
            <textarea name="description" placeholder="Détails pratiques…"></textarea>
        </div>
        <div class="field">
            <label>&nbsp;</label>
            <div class="check"><input type="checkbox" id="rr" name="requires_reservation" value="1"><label for="rr">Sur réservation</label></div>
        </div>
    </div>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Créer l'événement</button>
        <a class="btn btn-outline" href="?r=/expositions/<?= e($expo['slug']) ?>">Retour à la fiche</a>
    </div>
</form>