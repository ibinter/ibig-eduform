<?php
$u    = auth_user();
$role = $u['role'] ?? 'user';

/*
|--------------------------------------------------------------------------
| MENUS PAR RÔLE
|--------------------------------------------------------------------------
*/
$menus = [

  'user' => [
    ['key'=>'dashboard','label'=>'Tableau de bord','url'=>'/admin/dashboard.php'],
    ['key'=>'calendrier','label'=>'Formations','url'=>'/admin/calendrier/index.php'],
  ],

  'commercial' => [
    ['key'=>'dashboard','label'=>'Dashboard','url'=>'/admin/dashboard.php'],
    ['key'=>'calendrier','label'=>'Formations','url'=>'/admin/calendrier/index.php'],
    ['key'=>'preinscriptions','label'=>'Préinscriptions','url'=>'/admin/preinscriptions/index.php'],
  ],

  'rh' => [
    ['key'=>'dashboard','label'=>'Dashboard RH','url'=>'/admin/dashboard.php'],
    ['key'=>'users','label'=>'Utilisateurs','url'=>'/admin/users/index.php'],
    ['key'=>'formateurs','label'=>'Formateurs','url'=>'/admin/users/index.php?role=formateur'],
  ],

  'admin' => [
    ['key'=>'dashboard','label'=>'Dashboard','url'=>'/admin/dashboard.php'],
    ['key'=>'formations','label'=>'Formations','url'=>'/admin/formations/index.php'],
    ['key'=>'calendrier','label'=>'Calendrier','url'=>'/admin/calendrier/index.php'],
    ['key'=>'preinscriptions','label'=>'Préinscriptions','url'=>'/admin/preinscriptions/index.php'],
    ['key'=>'hero','label'=>'Carrousel accueil','url'=>'/admin/hero/index.php'],
    ['key'=>'pages','label'=>'Pages du site','url'=>'/admin/pages/index.php'],
    ['key'=>'users','label'=>'Utilisateurs','url'=>'/admin/users/index.php'],
  ],

  'super_admin' => [
    ['key'=>'dashboard','label'=>'Super Dashboard','url'=>'/admin/dashboard.php'],
    ['key'=>'users','label'=>'Utilisateurs','url'=>'/admin/users/index.php'],
    ['key'=>'formations','label'=>'Formations','url'=>'/admin/formations/index.php'],
    ['key'=>'calendrier','label'=>'Calendrier','url'=>'/admin/calendrier/index.php'],
    ['key'=>'preinscriptions','label'=>'Préinscriptions','url'=>'/admin/preinscriptions/index.php'],
    ['key'=>'hero','label'=>'Carrousel accueil','url'=>'/admin/hero/index.php'],
    ['key'=>'pages','label'=>'Pages du site','url'=>'/admin/pages/index.php'],
    ['key'=>'audit','label'=>'Audit','url'=>'/admin/audit/index.php'],
  ],
];

$currentMenu = $menus[$role] ?? $menus['user'];
?>

<nav class="sidebar-menu">
  <ul>
    <?php foreach ($currentMenu as $item): ?>
      <li>
        <a href="<?= $item['url']; ?>"
           class="<?= ($activeMenu === $item['key']) ? 'active' : ''; ?>">
          <?= htmlspecialchars($item['label']); ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>