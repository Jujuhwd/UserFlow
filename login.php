<?php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/layout.php';
if (current_user()) { header('Location: index.php'); exit; }
$error = null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'] ?? [], fn($t) => $t > time()-900);
    if (count($_SESSION['login_attempts']) >= 8) {
        $error = 'Trop de tentatives. Réessaie dans quelques minutes.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        $st = db()->prepare('SELECT * FROM users WHERE email=? AND active=1 LIMIT 1');
        $st->execute([$email]);
        $user = $st->fetch();
        if ($user && password_verify($pass, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['login_attempts'] = [];
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php'); exit;
        }
        $_SESSION['login_attempts'][] = time();
        $error = 'Identifiants incorrects.';
    }
}
render_header('Connexion');
?>
<div class="card login-card">
<h1>Connexion</h1>
<?php if($error): ?><div class="alert alert-danger"><?=h($error)?></div><?php endif; ?>
<form method="post" autocomplete="off">
  <div class="form-row"><label>Adresse mail</label><input name="email" type="email" placeholder="admin@demo.test" required></div>
  <div class="form-row"><label>Mot de passe</label><input name="password" type="password" required></div>
  <button class="btn">Se connecter</button>
</form>
<p class="muted">Compte par défaut après installation : admin@demo.test / admin1234</p>
</div>
<?php render_footer(); ?>
