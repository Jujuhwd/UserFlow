<?php
require_once __DIR__.'/functions.php';
function render_header(string $title='', string $active='') {
    security_headers();
    $u=current_user();
    $settings = [];
    try { $settings = get_settings(); } catch(Throwable $e) {}
    $company = $settings['company_name'] ?? 'UserFlow DEMO';
    $logo = $settings['logo_url'] ?? '';
    $color = sanitize_color($settings['primary_color'] ?? '#2563eb');
?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=h($title ? $title.' - UserFlow DEMO' : 'UserFlow DEMO')?></title><link rel="stylesheet" href="assets/style.css"><style>:root{--primary:<?=h($color)?>;--primary2:<?=h($color)?>}</style></head>
<body><header class="topbar"><div class="brand"><span class="logo <?= $logo ? 'has-image' : '' ?>"><?php if($logo): ?><img src="<?=h($logo)?>" alt="Logo"><?php else: ?><?=h(initials_from_company($company))?><?php endif; ?></span><div class="brand-text"><strong><?=h($company)?></strong><span>Création automatisée des accès</span></div></div>
<?php if($u): ?><nav class="nav"><a class="<?= $active==='generate'?'active':'' ?>" href="index.php">Générer</a><?php if($u['role']==='admin'): ?><a class="<?= $active==='config'?'active':'' ?>" href="admin.php">Configuration</a><a class="<?= $active==='users'?'active':'' ?>" href="users.php">Administrateurs</a><?php endif; ?><a class="<?= $active==='profile'?'active':'' ?>" href="profile.php">Mon compte</a><a href="logout.php">Déconnexion</a></nav><?php endif; ?></header><button type="button" class="theme-bubble" onclick="toggleTheme()" title="Mode clair / sombre"><span id="themeIcon">🌙</span></button><main class="container">
<?php }
function render_footer(){ ?>
</main><footer class="footer"><span>Application interne - <?=date('Y')?></span></footer><script src="assets/app.js"></script></body></html><?php }
