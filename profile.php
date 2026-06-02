<?php
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/layout.php';
$u=require_login(); $msg=null; $err=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    $name=trim($_POST['name']); $email=trim($_POST['email']);
    $st=db()->prepare('UPDATE users SET name=?,email=? WHERE id=?'); $st->execute([$name,$email,$u['id']]);
    if(!empty($_POST['new_password'])){
        if(strlen($_POST['new_password'])<8) $err='Le nouveau mot de passe doit faire au moins 8 caractères.';
        elseif($_POST['new_password']!==($_POST['confirm_password']??'')) $err='La confirmation ne correspond pas.';
        else {$st=db()->prepare('UPDATE users SET password_hash=? WHERE id=?'); $st->execute([password_hash($_POST['new_password'],PASSWORD_DEFAULT),$u['id']]); $msg='Profil et mot de passe modifiés.';}
    } else $msg='Profil modifié.';
    $u=current_user();
}
render_header('Mon compte','profile');
?>
<div class="page-head"><div><h1>Mon compte</h1><p class="subtitle">Modifier tes informations et ton mot de passe.</p></div></div>
<?php if($msg): ?><div class="alert alert-ok"><?=h($msg)?></div><?php endif; ?><?php if($err): ?><div class="alert alert-danger"><?=h($err)?></div><?php endif; ?>
<div class="card login-card"><form method="post"><div class="form-row"><label>Nom</label><input name="name" value="<?=h($u['name'])?>" required></div><div class="form-row"><label>Email</label><input type="email" name="email" value="<?=h($u['email'])?>" required></div><h2 class="section-title">Changer le mot de passe</h2><div class="form-row"><label>Nouveau mot de passe</label><input type="password" name="new_password" minlength="8" placeholder="laisser vide pour ne pas changer"></div><div class="form-row"><label>Confirmer</label><input type="password" name="confirm_password"></div><button class="btn">Enregistrer</button></form></div>
<?php render_footer(); ?>
