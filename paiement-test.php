<?php
declare(strict_types=1);

/* =====================================================
   PAGE DE TEST PAIEMENT MONEROO (à SUPPRIMER après test)
   Montant fixe faible (défaut 100 FCFA) pour valider un
   vrai encaissement de bout en bout.
   Accès : /paiement-test.php   (ou ?montant=200)
===================================================== */

require_once __DIR__ . '/core/bootstrap.php';

$pdo = Database::connect();

/* Montant de test borné (sécurité : 100 à 2000 FCFA) */
$montant = (int)($_GET['montant'] ?? 100);
if ($montant < 100)  $montant = 100;
if ($montant > 2000) $montant = 2000;

$pageTitle = "Test paiement – IBIG EDUFORM";
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $prenom = trim((string)($_POST['prenom'] ?? 'Test'));
  $nom    = trim((string)($_POST['nom'] ?? 'IBIG'));
  $email  = trim((string)($_POST['email'] ?? ''));
  $tel    = trim((string)($_POST['telephone'] ?? ''));

  if ($email === '' || $tel === '') {
    $error = "Email et téléphone requis pour le test.";
  } else {
    $reference = strtoupper('TEST-' . bin2hex(random_bytes(4)));

    /* Trace en base (tolérant : ignoré si contrainte/colonnes) */
    try {
      $pdo->prepare("INSERT INTO paiements_inscription (formation_id, montant, reference, statut)
                     VALUES (NULL, ?, ?, 'en_attente')")
          ->execute([$montant, $reference]);
    } catch (Throwable $e) { /* on continue : le test peut se faire sans trace */ }

    $returnUrl = rtrim(APP_URL, '/') . '/paiement-retour.php?ref=' . urlencode($reference);
    $pay = moneroo_initialize(
      $montant,
      "TEST encaissement IBIG EDUFORM",
      ['email' => $email, 'first_name' => $prenom, 'last_name' => $nom, 'phone' => $tel],
      $returnUrl,
      ['reference' => $reference, 'test' => '1']
    );

    if ($pay && !empty($pay['checkout_url'])) {
      try {
        $pdo->prepare("UPDATE paiements_inscription SET provider='moneroo', provider_payment_id=? WHERE reference=?")
            ->execute([$pay['id'] ?? '', $reference]);
      } catch (Throwable $e) {}
      header('Location: ' . $pay['checkout_url']);
      exit;
    }
    $error = "Échec d'initialisation Moneroo. Vérifiez la clé et le journal [MONEROO].";
  }
}

include __DIR__ . '/partials/header.php';
?>
<div style="max-width:520px;margin:70px auto 90px;padding:0 18px">
  <div style="background:#0b1220;color:#e5e7eb;border-radius:18px;padding:30px;border:1px solid rgba(255,255,255,.08)">
    <h1 style="margin:0 0 6px;color:#f5a623;font-size:1.4rem">🧪 Test de paiement</h1>
    <p style="opacity:.85;margin:0 0 4px">Montant de test :</p>
    <div style="font-size:1.6rem;font-weight:900;color:#22c55e"><?= number_format($montant,0,',',' '); ?> FCFA</div>

    <?php if ($error): ?>
      <div style="margin-top:14px;padding:12px;border-radius:10px;background:rgba(239,68,68,.14);border:1px solid #ef4444;color:#fecaca;font-size:14px"><?= e($error); ?></div>
    <?php endif; ?>

    <form method="post" style="margin-top:10px">
      <?= csrf_field(); ?>
      <label style="display:block;margin-top:12px;font-size:14px">Prénom</label>
      <input name="prenom" value="Test" style="width:100%;margin-top:6px;padding:11px;border-radius:10px;border:1px solid rgba(255,255,255,.18);background:#020617;color:#e5e7eb">
      <label style="display:block;margin-top:12px;font-size:14px">Nom</label>
      <input name="nom" value="IBIG" style="width:100%;margin-top:6px;padding:11px;border-radius:10px;border:1px solid rgba(255,255,255,.18);background:#020617;color:#e5e7eb">
      <label style="display:block;margin-top:12px;font-size:14px">Email *</label>
      <input type="email" name="email" required style="width:100%;margin-top:6px;padding:11px;border-radius:10px;border:1px solid rgba(255,255,255,.18);background:#020617;color:#e5e7eb">
      <label style="display:block;margin-top:12px;font-size:14px">Téléphone *</label>
      <input name="telephone" required placeholder="+225 0X XX XX XX XX" style="width:100%;margin-top:6px;padding:11px;border-radius:10px;border:1px solid rgba(255,255,255,.18);background:#020617;color:#e5e7eb">
      <button type="submit" style="width:100%;margin-top:20px;padding:15px;border:0;border-radius:12px;background:#22c55e;color:#04210f;font-weight:900;font-size:15px;cursor:pointer">Payer <?= number_format($montant,0,',',' '); ?> FCFA →</button>
    </form>
    <p style="margin-top:12px;font-size:12px;color:#94a3b8">⚠️ Page de test — à supprimer après validation.</p>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
