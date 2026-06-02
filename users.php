<?php
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/layout.php';
$me=require_admin(); $msg=null; $err=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    $form=$_POST['form']??'';
    if($form==='create'){
        $name=trim($_POST['name']); $email=trim($_POST['email']); $pass=$_POST['password']??'';
        if(!$name||!$email||strlen($pass)<8) $err='Nom, email et mot de passe de 8 caractères minimum obligatoires.';
        else { try{$st=db()->prepare('INSERT INTO users(name,email,password_hash,role,active) VALUES(?,?,?,?,1)');$st->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),'admin']);$msg='Administrateur ajouté.';}catch(Throwable $e){$err='Impossible d’ajouter cet administrateur. Email déjà utilisé ?';} }
    } elseif($form==='update'){
        $id=(int)$_POST['id']; $active=isset($_POST['active'])?1:0; $name=trim($_POST['name']); $email=trim($_POST['email']);
        if($id===(int)$me['id']) $active=1;
        $st=db()->prepare('UPDATE users SET name=?,email=?,active=? WHERE id=? AND role="admin"'); $st->execute([$name,$email,$active,$id]);
        if(!empty($_POST['password'])){ if(strlen($_POST['password'])<8) $err='Mot de passe trop court.'; else {$st=db()->prepare('UPDATE users SET password_hash=? WHERE id=?');$st->execute([password_hash($_POST['password'],PASSWORD_DEFAULT),$id]);} }
        if(!$err) $msg='Administrateur modifié.';
    } elseif($form==='delete'){
        $id=(int)$_POST['id']; if($id===(int)$me['id']) $err='Tu ne peux pas supprimer ton propre compte.'; else {$st=db()->prepare('DELETE FROM users WHERE id=? AND role="admin"');$st->execute([$id]);$msg='Administrateur supprimé.';}
    }
}
$users=db()->query('SELECT id,name,email,role,active,created_at FROM users WHERE role="admin" ORDER BY name,email')->fetchAll();
render_header('Administrateurs','users');
?>
<div class="page-head"><div><h1>Administrateurs</h1><p class="subtitle">Ajouter, désactiver ou modifier les accès administrateur.</p></div></div>
<?php if($msg): ?><div class="alert alert-ok"><?=h($msg)?></div><?php endif; ?><?php if($err): ?><div class="alert alert-danger"><?=h($err)?></div><?php endif; ?>
<div class="grid"><div class="card"><h2 class="section-title">➕ Ajouter un administrateur</h2><form method="post"><input type="hidden" name="form" value="create"><div class="form-row"><label>Nom</label><input name="name" required></div><div class="form-row"><label>Email</label><input type="email" name="email" required></div><div class="form-row"><label>Mot de passe temporaire</label><input type="password" name="password" minlength="8" required></div><button class="btn">Ajouter</button></form></div><div class="card"><h2 class="section-title">ℹ️ Rappel</h2><p class="muted">Un administrateur peut modifier la configuration, les applications et les autres administrateurs.</p><p class="muted">Pour changer ton propre mot de passe, va dans <strong>Mon compte</strong>.</p></div></div>
<div class="card"><h2 class="section-title">👥 Comptes administrateurs</h2><div class="table-wrap"><table class="table"><thead><tr><th>Nom</th><th>Email</th><th>Actif</th><th>Nouveau mot de passe</th><th>Actions</th></tr></thead><tbody><?php foreach($users as $u): ?><tr><form method="post"><input type="hidden" name="form" value="update"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><td><input name="name" value="<?=h($u['name'])?>"></td><td><input type="email" name="email" value="<?=h($u['email'])?>"></td><td><input style="width:auto" type="checkbox" name="active" <?= $u['active']?'checked':'' ?> <?= $u['id']==$me['id']?'disabled':'' ?>></td><td><input type="password" name="password" placeholder="laisser vide"></td><td><div class="actions"><button class="btn secondary">Modifier</button></form><?php if($u['id']!=$me['id']): ?><form method="post" onsubmit="return confirm('Supprimer cet administrateur ?')"><input type="hidden" name="form" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn danger">Supprimer</button></form><?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php render_footer(); ?>
