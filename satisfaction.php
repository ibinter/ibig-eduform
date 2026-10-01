<?php
declare(strict_types=1);
/**
 * IBIG EDUFORM — /satisfaction.php
 * Formulaire de satisfaction post-formation accessible via lien tokenisé.
 * URL : /satisfaction.php?t=TOKEN64
 */

require_once __DIR__ . '/core/bootstrap.php';

if (!function_exists('e')) {
    function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
}

$pdo   = Database::connect();
$token = trim((string)($_GET['t'] ?? ''));

/* ── Tables auto-create (idempotent) ── */
$pdo->exec("CREATE TABLE IF NOT EXISTS satisfaction_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  preinscription_id BIGINT UNSIGNED NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL,
  token CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token (token),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS satisfaction_reponses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  preinscription_id BIGINT UNSIGNED NOT NULL,
  email VARCHAR(255) NOT NULL,
  formation_titre VARCHAR(500) DEFAULT NULL,
  note_globale TINYINT UNSIGNED NOT NULL,
  note_contenu TINYINT UNSIGNED DEFAULT NULL,
  note_formateur TINYINT UNSIGNED DEFAULT NULL,
  note_logistique TINYINT UNSIGNED DEFAULT NULL,
  points_positifs TEXT DEFAULT NULL,
  points_ameliorer TEXT DEFAULT NULL,
  recommande TINYINT(1) DEFAULT NULL,
  commentaire TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_preinsc (preinscription_id),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ── Vérification token ── */
$tokenRow = null;
$errToken = '';
$dejaRempli = false;

if ($token === '') {
    $errToken = 'Lien invalide ou manquant.';
} else {
    $st = $pdo->prepare("SELECT * FROM satisfaction_tokens WHERE token = ? LIMIT 1");
    $st->execute([$token]);
    $tokenRow = $st->fetch(PDO::FETCH_ASSOC);

    if (!$tokenRow) {
        $errToken = 'Ce lien est invalide.';
    } elseif ($tokenRow['used_at'] !== null) {
        $dejaRempli = true;
    } elseif (strtotime((string)$tokenRow['expires_at']) < time()) {
        $errToken = 'Ce lien a expiré. Contactez-nous pour en obtenir un nouveau.';
    }
}

/* ── Données formation si token valide ── */
$preinsc = null;
if ($tokenRow && !$errToken) {
    $sp = $pdo->prepare("
        SELECT p.id, p.nom, p.prenoms, p.email,
               f.titre AS formation_titre
        FROM preinscriptions p
        LEFT JOIN formations f ON f.id = p.formation_id
        WHERE p.id = ? LIMIT 1
    ");
    $sp->execute([(int)$tokenRow['preinscription_id']]);
    $preinsc = $sp->fetch(PDO::FETCH_ASSOC);
}

/* ── Traitement POST ── */
$success = false;
$postErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenRow && !$errToken && !$dejaRempli) {
    $noteGlobale  = (int)($_POST['note_globale']   ?? 0);
    $noteContenu  = (int)($_POST['note_contenu']   ?? 0) ?: null;
    $noteFormateur = (int)($_POST['note_formateur'] ?? 0) ?: null;
    $noteLogistique = (int)($_POST['note_logistique'] ?? 0) ?: null;
    $positifs     = trim((string)($_POST['points_positifs']  ?? ''));
    $ameliorer    = trim((string)($_POST['points_ameliorer'] ?? ''));
    $recommande   = isset($_POST['recommande']) ? ((int)$_POST['recommande'] ? 1 : 0) : null;
    $commentaire  = trim((string)($_POST['commentaire'] ?? ''));

    if ($noteGlobale < 1 || $noteGlobale > 5) {
        $postErr = 'Veuillez attribuer une note globale.';
    } else {
        $pdo->prepare("INSERT INTO satisfaction_reponses
            (preinscription_id, email, formation_titre, note_globale, note_contenu, note_formateur, note_logistique, points_positifs, points_ameliorer, recommande, commentaire)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([
                (int)$tokenRow['preinscription_id'],
                (string)$tokenRow['email'],
                $preinsc['formation_titre'] ?? null,
                $noteGlobale, $noteContenu, $noteFormateur, $noteLogistique,
                $positifs ?: null, $ameliorer ?: null, $recommande,
                $commentaire ?: null,
            ]);

        /* Marque token comme utilisé */
        $pdo->prepare("UPDATE satisfaction_tokens SET used_at = NOW() WHERE token = ?")
            ->execute([$token]);

        $success = true;
    }
}

$pageTitle = 'Votre avis — IBIG EDUFORM';
include __DIR__ . '/partials/header.php';
?>
<style>
.sat-wrap{max-width:700px;margin:60px auto 100px;padding:0 20px}
.sat-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:36px 40px;box-shadow:0 4px 24px rgba(0,0,0,.06)}
.sat-title{font-size:1.6rem;font-weight:900;color:#0a1733;margin:0 0 6px}
.sat-sub{color:#6b7280;font-size:.95rem;margin:0 0 28px;line-height:1.6}
.sat-section{margin-bottom:22px}
.sat-label{font-size:13px;font-weight:700;color:#374151;display:block;margin-bottom:8px}
.sat-stars{display:flex;gap:6px}
.star-input{display:none}
.star-lbl{font-size:28px;cursor:pointer;color:#d1d5db;transition:color .15s;line-height:1}
.star-lbl:hover,.star-lbl.active{color:#f59e0b}
.sat-textarea{width:100%;border:1px solid #d1d5db;border-radius:8px;padding:10px 14px;font-size:13px;font-family:inherit;resize:vertical;min-height:80px;outline:none}
.sat-textarea:focus{border-color:#1d4ed8;box-shadow:0 0 0 2px rgba(29,78,216,.1)}
.sat-radio-row{display:flex;gap:14px;flex-wrap:wrap}
.sat-radio-row label{display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;cursor:pointer;padding:7px 16px;border:1.5px solid #d1d5db;border-radius:8px;transition:all .15s}
.sat-radio-row input[type=radio]:checked + span{color:#1d4ed8}
.sat-radio-row label:has(input:checked){border-color:#1d4ed8;background:#eff6ff}
.sat-btn{background:#0a1733;color:#fff;border:none;border-radius:10px;padding:13px 32px;font-size:15px;font-weight:700;cursor:pointer;transition:background .2s;width:100%}
.sat-btn:hover{background:#1d4ed8}
.sat-success{text-align:center;padding:40px 20px}
.sat-success-icon{font-size:4rem;margin-bottom:16px}
.sat-success h2{font-size:1.5rem;font-weight:900;color:#0a1733;margin:0 0 8px}
.sat-success p{color:#6b7280;font-size:1rem;line-height:1.7}
.sat-err{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:18px}
.stars-sub{display:flex;gap:6px;margin-bottom:6px}
.stars-sub label{font-size:22px;cursor:pointer;color:#d1d5db;transition:color .15s}
.stars-sub input{display:none}
.stars-sub label.active,.stars-sub label:hover{color:#f59e0b}
</style>

<main>
<div class="sat-wrap">

<?php if ($errToken): ?>
  <div class="sat-card" style="text-align:center;padding:48px 32px">
    <div style="font-size:3rem;margin-bottom:12px">⚠️</div>
    <h2 style="color:#0a1733;font-size:1.3rem;margin:0 0 10px">Lien invalide</h2>
    <p style="color:#6b7280"><?= e($errToken); ?></p>
    <a href="/" style="display:inline-block;margin-top:20px;color:#1d4ed8;font-weight:700">← Retour à l'accueil</a>
  </div>

<?php elseif ($dejaRempli || $success): ?>
  <div class="sat-card">
    <div class="sat-success">
      <div class="sat-success-icon">🎉</div>
      <h2>Merci pour votre retour !</h2>
      <p>Votre avis a bien été enregistré.<br>
      Il nous aide à améliorer continuellement la qualité de nos formations.<br><br>
      <strong>À bientôt chez IBIG EDUFORM !</strong></p>
      <a href="/" style="display:inline-block;margin-top:24px;background:#f59e0b;color:#0a1733;font-weight:700;padding:12px 28px;border-radius:10px;text-decoration:none">Découvrir nos formations →</a>
    </div>
  </div>

<?php else: ?>
  <div class="sat-card">
    <h1 class="sat-title">Votre avis compte ! 🌟</h1>
    <p class="sat-sub">
      <?php if ($preinsc): ?>
        Bonjour <strong><?= e($preinsc['prenoms'] ?: $preinsc['nom']); ?></strong>,
        merci d'avoir participé à la formation <strong><?= e($preinsc['formation_titre'] ?? ''); ?></strong>.<br>
      <?php endif; ?>
      Prenez 2 minutes pour nous donner votre retour — cela nous aide à progresser.
    </p>

    <?php if ($postErr): ?>
    <div class="sat-err">⚠️ <?= e($postErr); ?></div>
    <?php endif; ?>

    <form method="post">

      <!-- Note globale -->
      <div class="sat-section">
        <label class="sat-label">Note globale de la formation <span style="color:#ef4444">*</span></label>
        <div class="sat-stars" id="stars-globale" data-name="note_globale">
          <?php for ($i = 1; $i <= 5; $i++): ?>
          <input type="radio" name="note_globale" id="g<?=$i?>" value="<?=$i?>" class="star-input" <?= ((int)($_POST['note_globale']??0)===$i?'checked':''); ?> required>
          <label for="g<?=$i?>" class="star-lbl <?= ((int)($_POST['note_globale']??0)>=$i?'active':''); ?>">★</label>
          <?php endfor; ?>
        </div>
        <div style="font-size:11px;color:#94a3b8;margin-top:4px">1 étoile = insuffisant · 5 étoiles = excellent</div>
      </div>

      <!-- Notes par critère -->
      <div class="sat-section">
        <label class="sat-label">Qualité du contenu pédagogique</label>
        <div class="sat-stars" id="stars-contenu" data-name="note_contenu">
          <?php for ($i = 1; $i <= 5; $i++): ?>
          <input type="radio" name="note_contenu" id="c<?=$i?>" value="<?=$i?>" class="star-input" <?= ((int)($_POST['note_contenu']??0)===$i?'checked':''); ?>>
          <label for="c<?=$i?>" class="star-lbl <?= ((int)($_POST['note_contenu']??0)>=$i?'active':''); ?>">★</label>
          <?php endfor; ?>
        </div>
      </div>

      <div class="sat-section">
        <label class="sat-label">Compétence du formateur</label>
        <div class="sat-stars" id="stars-formateur" data-name="note_formateur">
          <?php for ($i = 1; $i <= 5; $i++): ?>
          <input type="radio" name="note_formateur" id="f<?=$i?>" value="<?=$i?>" class="star-input" <?= ((int)($_POST['note_formateur']??0)===$i?'checked':''); ?>>
          <label for="f<?=$i?>" class="star-lbl <?= ((int)($_POST['note_formateur']??0)>=$i?'active':''); ?>">★</label>
          <?php endfor; ?>
        </div>
      </div>

      <div class="sat-section">
        <label class="sat-label">Logistique & organisation</label>
        <div class="sat-stars" id="stars-logistique" data-name="note_logistique">
          <?php for ($i = 1; $i <= 5; $i++): ?>
          <input type="radio" name="note_logistique" id="l<?=$i?>" value="<?=$i?>" class="star-input" <?= ((int)($_POST['note_logistique']??0)===$i?'checked':''); ?>>
          <label for="l<?=$i?>" class="star-lbl <?= ((int)($_POST['note_logistique']??0)>=$i?'active':''); ?>">★</label>
          <?php endfor; ?>
        </div>
      </div>

      <!-- Points positifs -->
      <div class="sat-section">
        <label class="sat-label" for="points_positifs">Ce que vous avez le plus apprécié</label>
        <textarea class="sat-textarea" name="points_positifs" id="points_positifs" placeholder="Ex : Les exercices pratiques, la disponibilité du formateur..."><?= e($_POST['points_positifs'] ?? ''); ?></textarea>
      </div>

      <!-- Points à améliorer -->
      <div class="sat-section">
        <label class="sat-label" for="points_ameliorer">Ce qui pourrait être amélioré</label>
        <textarea class="sat-textarea" name="points_ameliorer" id="points_ameliorer" placeholder="Ex : Plus d'exemples concrets, horaires mieux adaptés..."><?= e($_POST['points_ameliorer'] ?? ''); ?></textarea>
      </div>

      <!-- Recommandation -->
      <div class="sat-section">
        <label class="sat-label">Recommanderiez-vous IBIG EDUFORM à un collègue ou ami ?</label>
        <div class="sat-radio-row">
          <label><input type="radio" name="recommande" value="1" <?= (isset($_POST['recommande']) && $_POST['recommande']==='1'?'checked':''); ?>><span>👍 Oui, absolument</span></label>
          <label><input type="radio" name="recommande" value="0" <?= (isset($_POST['recommande']) && $_POST['recommande']==='0'?'checked':''); ?>><span>👎 Non, pas pour l'instant</span></label>
        </div>
      </div>

      <!-- Commentaire libre -->
      <div class="sat-section">
        <label class="sat-label" for="commentaire">Commentaire libre (facultatif)</label>
        <textarea class="sat-textarea" name="commentaire" id="commentaire" rows="4" placeholder="Tout ce que vous souhaitez nous dire..."><?= e($_POST['commentaire'] ?? ''); ?></textarea>
      </div>

      <button class="sat-btn" type="submit">✅ Envoyer mon évaluation</button>
    </form>
  </div>
<?php endif; ?>

</div>
</main>

<script>
/* Interaction étoiles */
document.querySelectorAll('.sat-stars').forEach(function(group) {
  var labels = group.querySelectorAll('.star-lbl');
  var inputs = group.querySelectorAll('.star-input');
  labels.forEach(function(lbl, idx) {
    lbl.addEventListener('mouseover', function() {
      labels.forEach(function(l, i) { l.style.color = i <= idx ? '#f59e0b' : '#d1d5db'; });
    });
    lbl.addEventListener('mouseout', function() {
      var val = parseInt(group.querySelector('.star-input:checked')?.value || '0');
      labels.forEach(function(l, i) { l.style.color = i < val ? '#f59e0b' : '#d1d5db'; });
    });
    lbl.addEventListener('click', function() {
      labels.forEach(function(l, i) { l.classList.toggle('active', i <= idx); });
    });
  });
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
