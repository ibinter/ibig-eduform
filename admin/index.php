<?php
declare(strict_types=1);

require_once __DIR__ . '/auth/guard.php';
require_permission('view_preinscriptions');

require_once __DIR__ . '/../_init.php';

Middleware::requireAuth();

$u = auth_user();

$pageTitle  = "Préinscriptions";
$activeMenu = "preinscriptions";

$pdo = Database::connect();

/* =========================
   DATA
========================= */
$sql = "
  SELECT
    p.id,
    p.nom,
    p.prenoms,
    p.email,
    p.telephone,
    p.ville,
    p.niveau,
    p.statut,
    p.created_at,
    f.titre AS formation
  FROM preinscriptions p
  LEFT JOIN formations f ON f.id = p.formation_id
  ORDER BY p.created_at DESC
";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   HELPERS LOCAUX
========================= */
/* Numéro WhatsApp international (Côte d'Ivoire par défaut : préfixe 225). */
if (!function_exists('pre_wa_number')) {
    function pre_wa_number(?string $phone): string {
        $d = preg_replace('/\D+/', '', (string)$phone);
        if ($d === '') return '';
        // déjà international (225...) ou autre indicatif long : on garde
        if (strpos($d, '225') === 0) return $d;
        // numéro local ivoirien -> on préfixe 225
        if (strlen($d) <= 10) return '225' . $d;
        return $d;
    }
}

/* =========================
   COMPTEURS
========================= */
$total     = count($rows);
$nbConf    = 0;
$nbWait    = 0;
$nbToday   = 0;
$today     = date('Y-m-d');
foreach ($rows as $r) {
    $st = strtoupper((string)($r['statut'] ?? ''));
    if ($st === 'CONFIRME' || $st === 'CONFIRMEE' || $st === 'VALIDE') { $nbConf++; } else { $nbWait++; }
    if (!empty($r['created_at']) && date('Y-m-d', strtotime((string)$r['created_at'])) === $today) { $nbToday++; }
}

ob_start();
?>

<style>
/* =========================================================
   PREINSCRIPTIONS — RÉCEPTION & CONTACT DIRECT
========================================================= */
.pre-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:4px 0 18px}
.pre-kpi{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px 16px;box-shadow:0 6px 18px rgba(15,23,42,.05)}
.pre-kpi b{display:block;font-size:24px;font-weight:800;line-height:1.1;color:#0f172a}
.pre-kpi span{font-size:12.5px;color:#6b7280;font-weight:600}
.pre-kpi.k-conf b{color:#16a34a}
.pre-kpi.k-wait b{color:#b45309}
.pre-kpi.k-today b{color:#1e40af}

.header-actions{display:flex;gap:8px;flex-wrap:wrap}
.btn-export{padding:7px 12px;font-size:12px;border-radius:10px;text-decoration:none;font-weight:700;display:inline-flex;align-items:center;gap:6px}
.btn-csv{background:#e0f2fe;color:#075985}
.btn-xlsx{background:#dcfce7;color:#166534}
.btn-pdf{background:#fee2e2;color:#991b1b}
.btn-print{background:#fef3c7;color:#92400e}

.pre-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:6px 0 12px}
.pre-search{flex:1;min-width:220px;max-width:420px;position:relative}
.pre-search input{width:100%;padding:11px 14px 11px 38px;border:1px solid #e5e7eb;border-radius:12px;font-size:14px;outline:none}
.pre-search input:focus{border-color:#1f3fe0;box-shadow:0 0 0 3px rgba(31,63,224,.12)}
.pre-search::before{content:"🔍";position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:14px}

table.pre-table{width:100%;border-collapse:collapse;margin-top:6px}
table.pre-table th{font-size:12px;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;text-align:left;padding:10px 12px;border-bottom:2px solid #eef2f7}
table.pre-table td{padding:12px;border-bottom:1px solid #f1f5f9;vertical-align:top;font-size:14px}
table.pre-table tr:hover td{background:#fafbff}

.pre-name strong{font-size:14.5px;color:#0f172a}
.contact-lines a{color:#1f3fe0;text-decoration:none}
.contact-lines a:hover{text-decoration:underline}

/* boutons de contact direct */
.contact-btns{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
.cbtn{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;border:1px solid transparent;white-space:nowrap}
.cbtn.wa{background:#dcfce7;color:#15803d;border-color:#bbf7d0}
.cbtn.wa:hover{background:#bbf7d0}
.cbtn.mail{background:#e0ecff;color:#1e40af;border-color:#bfdbfe}
.cbtn.mail:hover{background:#c7ddff}
.cbtn.tel{background:#f1f5f9;color:#334155;border-color:#e2e8f0}
.cbtn.tel:hover{background:#e2e8f0}
.cbtn.dis{opacity:.4;pointer-events:none}

.pill{padding:4px 10px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap}
.pill.ok{background:#dcfce7;color:#166534}
.pill.wait{background:#fff7ed;color:#9a3412}

.table-actions{display:flex;gap:6px}
.btn-action{width:34px;height:34px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-size:15px;border:1px solid transparent;cursor:pointer;transition:all .15s ease;text-decoration:none}
.btn-view{background:#e0ecff;color:#1e40af;border-color:#bfdbfe}
.btn-view:hover{background:#c7ddff}
.btn-edit{background:#ecfeff;color:#0f766e;border-color:#99f6e4}
.btn-edit:hover{background:#ccfbf1}
.btn-del{background:#fee2e2;color:#991b1b;border-color:#fecaca}
.btn-del:hover{background:#fecaca}

.muted{color:#6b7280}
.pre-empty{padding:26px;text-align:center;color:#6b7280}
@media(max-width:900px){
  .pre-kpis{grid-template-columns:1fr 1fr}
}
</style>

<div class="card">

  <!-- HEADER -->
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <h2 style="margin:0">&#128221; Préinscriptions reçues</h2>
    <div class="header-actions">
      <a class="btn-export btn-csv" href="export.php">CSV</a>
      <a class="btn-export btn-xlsx" href="export_preinscriptions.xlsx.php">XLSX</a>
      <a class="btn-export btn-pdf" href="export_preinscriptions.pdf.php">PDF</a>
      <a class="btn-export btn-print" href="print.php" target="_blank">Imprimer</a>
    </div>
  </div>

  <!-- KPIs -->
  <div class="pre-kpis">
    <div class="pre-kpi"><b><?= (int)$total; ?></b><span>Total préinscriptions</span></div>
    <div class="pre-kpi k-conf"><b><?= (int)$nbConf; ?></b><span>Confirmées</span></div>
    <div class="pre-kpi k-wait"><b><?= (int)$nbWait; ?></b><span>En attente</span></div>
    <div class="pre-kpi k-today"><b><?= (int)$nbToday; ?></b><span>Aujourd'hui</span></div>
  </div>

  <!-- TOOLBAR -->
  <div class="pre-toolbar">
    <div class="pre-search">
      <input type="text" id="preSearch" placeholder="Rechercher (nom, email, téléphone, formation, ville…)">
    </div>
    <div class="muted" style="font-size:13px">
      <span id="preCount"><?= (int)$total; ?></span> résultat(s)
    </div>
  </div>

  <!-- TABLE -->
  <table class="pre-table">
    <thead>
      <tr>
        <th>Candidat</th>
        <th>Contact &amp; actions rapides</th>
        <th>Formation</th>
        <th>Statut</th>
        <th>Date</th>
        <th>Gestion</th>
      </tr>
    </thead>

    <tbody id="preBody">

    <?php if (!$rows): ?>
      <tr><td colspan="6" class="pre-empty">Aucune préinscription pour le moment.</td></tr>
    <?php endif; ?>

    <?php foreach ($rows as $r):
        $nomComplet = trim((string)$r['nom'] . ' ' . (string)$r['prenoms']);
        $prenom     = trim((string)($r['prenoms'] ?? '')) ?: trim((string)($r['nom'] ?? '')) ?: 'cher candidat';
        $form       = trim((string)($r['formation'] ?? ''));
        $email      = trim((string)($r['email'] ?? ''));
        $tel        = trim((string)($r['telephone'] ?? ''));
        $waNum      = pre_wa_number($tel);
        $st         = strtoupper((string)($r['statut'] ?? ''));
        $isConf     = in_array($st, ['CONFIRME','CONFIRMEE','VALIDE'], true);

        /* Messages pré-remplis (humains, professionnels) */
        $waMsg = "Bonjour " . $prenom . ", ici l'équipe IBIG EDUFORM. "
               . ($form !== '' ? "Nous revenons vers vous au sujet de votre préinscription à la formation « " . $form . " ». " : "Nous revenons vers vous au sujet de votre préinscription. ")
               . "Comment pouvons-nous vous accompagner ?";
        $waHref = $waNum !== '' ? 'https://wa.me/' . $waNum . '?text=' . rawurlencode($waMsg) : '';

        $mailSubject = "IBIG EDUFORM — Votre préinscription" . ($form !== '' ? " : " . $form : "");
        $mailBody = "Bonjour " . $prenom . ",\n\n"
                  . "Nous vous remercions pour votre préinscription" . ($form !== '' ? " à la formation « " . $form . " »" : "") . " auprès d'IBIG EDUFORM.\n\n"
                  . "Nous revenons vers vous pour finaliser votre inscription et répondre à vos éventuelles questions.\n\n"
                  . "Bien cordialement,\nL'équipe IBIG EDUFORM";
        $mailHref = $email !== '' ? 'mailto:' . $email . '?subject=' . rawurlencode($mailSubject) . '&body=' . rawurlencode($mailBody) : '';
        $telHref  = $tel !== '' ? 'tel:' . preg_replace('/[^\d+]/', '', $tel) : '';

        /* texte recherchable */
        $search = strtolower(trim($nomComplet . ' ' . $email . ' ' . $tel . ' ' . $form . ' ' . (string)($r['ville'] ?? '') . ' ' . (string)($r['niveau'] ?? '')));
    ?>
      <tr data-search="<?= e($search); ?>">

        <td class="pre-name">
          <strong><?= e($nomComplet); ?></strong><br>
          <small class="muted">
            <?= e($r['ville'] ?? '—'); ?><?php if (!empty($r['niveau'])): ?> · <?= e($r['niveau']); ?><?php endif; ?>
          </small>
        </td>

        <td>
          <div class="contact-lines">
            <?php if ($tel !== ''): ?>📞 <a href="<?= e($telHref); ?>"><?= e($tel); ?></a><br><?php endif; ?>
            <?php if ($email !== ''): ?>✉️ <a href="mailto:<?= e($email); ?>"><?= e($email); ?></a><?php else: ?><small class="muted">Email non fourni</small><?php endif; ?>
          </div>
          <div class="contact-btns">
            <a class="cbtn wa <?= $waHref === '' ? 'dis' : ''; ?>" <?= $waHref !== '' ? 'href="'.e($waHref).'" target="_blank" rel="noopener"' : ''; ?>>💬 WhatsApp</a>
            <a class="cbtn mail <?= $mailHref === '' ? 'dis' : ''; ?>" <?= $mailHref !== '' ? 'href="'.e($mailHref).'"' : ''; ?>>✉️ Email</a>
          </div>
        </td>

        <td><?= e($form !== '' ? $form : '—'); ?></td>

        <td>
          <span class="pill <?= $isConf ? 'ok' : 'wait'; ?>"><?= e($r['statut'] ?: 'EN ATTENTE'); ?></span>
        </td>

        <td><small><?= !empty($r['created_at']) ? date('d/m/Y H:i', strtotime((string)$r['created_at'])) : '—'; ?></small></td>

        <!-- GESTION (RBAC) -->
        <td>
          <div class="table-actions">
            <a class="btn-action btn-view" title="Voir le détail" href="view.php?id=<?= (int)$r['id']; ?>">&#128065;</a>

            <?php if (in_array($u['role'], ['admin','super_admin'], true)): ?>
              <a class="btn-action btn-edit" title="Modifier" href="edit.php?id=<?= (int)$r['id']; ?>">&#9998;</a>
            <?php endif; ?>

            <?php if (($u['role'] ?? '') === 'super_admin'): ?>
              <form method="post" action="delete.php" onsubmit="return confirm('Supprimer cette préinscription ?');" style="display:inline">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                <button type="submit" class="btn-action btn-del" title="Supprimer">&#128465;</button>
              </form>
            <?php endif; ?>
          </div>
        </td>

      </tr>
    <?php endforeach; ?>

    </tbody>
  </table>

</div>

<script>
(function(){
  var input = document.getElementById('preSearch');
  var body  = document.getElementById('preBody');
  var count = document.getElementById('preCount');
  if(!input || !body) return;
  var rows = Array.prototype.slice.call(body.querySelectorAll('tr[data-search]'));
  input.addEventListener('input', function(){
    var q = input.value.toLowerCase().trim();
    var n = 0;
    rows.forEach(function(tr){
      var ok = q === '' || (tr.getAttribute('data-search') || '').indexOf(q) !== -1;
      tr.style.display = ok ? '' : 'none';
      if(ok) n++;
    });
    if(count) count.textContent = n;
  });
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/layout.php';
