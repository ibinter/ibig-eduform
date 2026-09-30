<?php
/* Bandeau Urgence & rareté — réutilisable (fiche simple + landing). Nécessite $f. */
if (!function_exists('h')) { function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
$dDebutTs   = !empty($f['date_debut']) ? strtotime((string)$f['date_debut'] . ' 09:00:00') : 0;
$joursAvant = $dDebutTs ? (int)floor(($dDebutTs - time()) / 86400) : null;
/* Places affichées : barème de rareté piloté par la date (core/promo.php) */
$placesLeft = function_exists('places_disponibles') ? places_disponibles($f) : null;
?>
<?php if ($dDebutTs && $joursAvant !== null && $joursAvant >= 0): ?>
<style>
  .urgency{display:flex;flex-wrap:wrap;gap:10px;margin:16px 0 6px}
  .u-pill{display:inline-flex;align-items:center;gap:8px;padding:9px 15px;border-radius:999px;font-size:13px;font-weight:700;line-height:1}
  .u-pill.live{background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.45)}
  .u-pill.live .dot{width:9px;height:9px;border-radius:50%;background:#34d399;animation:uPulse 1.6s infinite}
  @keyframes uPulse{0%{box-shadow:0 0 0 0 rgba(52,211,153,.6)}70%{box-shadow:0 0 0 9px rgba(52,211,153,0)}100%{box-shadow:0 0 0 0 rgba(52,211,153,0)}}
  .u-pill.count{background:rgba(245,158,11,.15);color:#fbbf24;border:1px solid rgba(245,158,11,.45)}
  .u-pill.count b{color:#fff;font-variant-numeric:tabular-nums;letter-spacing:.3px}
  .u-pill.places{background:rgba(232,36,44,.14);color:#fb7185;border:1px solid rgba(232,36,44,.45)}
</style>
<div class="urgency">
  <span class="u-pill live"><span class="dot"></span> Inscriptions ouvertes</span>
  <span class="u-pill count" data-deadline="<?= $dDebutTs * 1000; ?>">
    <i class="fa-regular fa-clock"></i> Démarre dans&nbsp;<b id="cd-formation">…</b>
  </span>
  <span class="u-pill places">
    <i class="fa-solid fa-fire"></i>
    <?= $placesLeft !== null ? ('Plus que ' . $placesLeft . ' place' . ($placesLeft > 1 ? 's' : '')) : 'Places limitées'; ?>
  </span>
</div>
<script>
  (function(){
    var el=document.getElementById('cd-formation'); if(!el) return;
    var box=el.parentElement, deadline=parseInt(box.getAttribute('data-deadline'),10);
    function p(n){return ('0'+n).slice(-2);}
    function tick(){
      var diff=deadline-Date.now();
      if(diff<=0){ el.textContent="aujourd'hui — dernières places !"; return; }
      var d=Math.floor(diff/86400000),h=Math.floor(diff/3600000)%24,m=Math.floor(diff/60000)%60,s=Math.floor(diff/1000)%60;
      el.textContent=(d>0?d+'j ':'')+p(h)+'h '+p(m)+'m '+p(s)+'s';
    }
    tick(); setInterval(tick,1000);
  })();
</script>
<?php endif; ?>
