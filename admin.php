<?php
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/layout.php';
require_admin();
$msg=null; $err=null;
try { db()->exec("ALTER TABLE settings ADD COLUMN IF NOT EXISTS logo_url VARCHAR(500) DEFAULT ''"); } catch(Throwable $e) {}
try { db()->exec("ALTER TABLE settings ADD COLUMN IF NOT EXISTS primary_color VARCHAR(20) DEFAULT '#2563eb'"); } catch(Throwable $e) {}
try { db()->exec("ALTER TABLE applications ADD COLUMN IF NOT EXISTS url VARCHAR(500) DEFAULT ''"); } catch(Throwable $e) {}
try { db()->exec("ALTER TABLE applications ADD COLUMN IF NOT EXISTS script_template MEDIUMTEXT NULL"); } catch(Throwable $e) {}

function handle_logo_upload(?string &$err): string {
    if (empty($_FILES['logo_file']) || ($_FILES['logo_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return trim($_POST['logo_url'] ?? '');
    if (($_FILES['logo_file']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) { $err = 'Erreur pendant l’upload du logo.'; return trim($_POST['logo_url'] ?? ''); }
    if (($_FILES['logo_file']['size'] ?? 0) > 1024*1024) { $err = 'Logo trop lourd : maximum 1 Mo.'; return trim($_POST['logo_url'] ?? ''); }
    $tmp = $_FILES['logo_file']['tmp_name'];
    $name = $_FILES['logo_file']['name'] ?? 'logo';
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['png','jpg','jpeg','webp','svg'];
    if (!in_array($ext, $allowed, true)) { $err = 'Format logo non accepté. Utilise PNG, JPG, WEBP ou SVG.'; return trim($_POST['logo_url'] ?? ''); }
    if ($ext !== 'svg') {
        $info = @getimagesize($tmp);
        if (!$info) { $err = 'Le fichier logo ne semble pas être une image valide.'; return trim($_POST['logo_url'] ?? ''); }
        if (($info[0] ?? 0) > 600 || ($info[1] ?? 0) > 200) { $err = 'Dimensions trop grandes : maximum conseillé et accepté 600 × 200 px.'; return trim($_POST['logo_url'] ?? ''); }
    }
    $dir = __DIR__.'/assets/uploads';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $target = $dir.'/logo.'.$ext;
    if (!move_uploaded_file($tmp, $target)) { $err = 'Impossible d’enregistrer le logo dans assets/uploads.'; return trim($_POST['logo_url'] ?? ''); }
    return 'assets/uploads/logo.'.$ext;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $form=$_POST['form']??'';
    if($form==='settings'){
        $logoUrl = handle_logo_upload($err);
        if(isset($_POST['remove_logo'])) $logoUrl='';
        $color = sanitize_color($_POST['primary_color'] ?? '#2563eb');
        $st=db()->prepare('UPDATE settings SET company_name=?, mail_domain=?, default_login_mode=?, password_length=?, logo_url=?, primary_color=? WHERE id=1');
        $st->execute([trim($_POST['company_name']),trim($_POST['mail_domain']),$_POST['default_login_mode'],max(6,(int)$_POST['password_length']),$logoUrl,$color]);
        if(!$err) $msg='Configuration générale enregistrée.';
    } elseif($form==='app'){
        $active=isset($_POST['active'])?1:0; $name=trim($_POST['name']);
        if($name){
            if(!empty($_POST['id'])){
                $st=db()->prepare('UPDATE applications SET name=?,active=?,login_mode=?,url=?,sort_order=?,notes=?,script_template=? WHERE id=?');
                $st->execute([$name,$active,$_POST['login_mode'],trim($_POST['url'] ?? ''),(int)$_POST['sort_order'],trim($_POST['notes']),trim($_POST['script_template']),(int)$_POST['id']]);
            } else {
                $st=db()->prepare('INSERT INTO applications(name,active,login_mode,url,sort_order,notes,script_template) VALUES(?,?,?,?,?,?,?)');
                $st->execute([$name,$active,$_POST['login_mode'],trim($_POST['url'] ?? ''),(int)$_POST['sort_order'],trim($_POST['notes']),trim($_POST['script_template'])]);
            }
            $msg='Application enregistrée.';
        }
    } elseif($form==='delete_app'){
        $st=db()->prepare('DELETE FROM applications WHERE id=?');$st->execute([(int)$_POST['id']]);$msg='Application supprimée.';
    }
}
$settings=get_settings(); $apps=db()->query('SELECT * FROM applications ORDER BY sort_order,name')->fetchAll();
$tags=available_script_tags();
render_header('Configuration','config');
?>
<div class="page-head"><div><h1>Configuration</h1><p class="subtitle">Paramètres de génération, identité visuelle, URL et scripts.</p></div><a class="btn secondary" href="users.php">Gestion des administrateurs</a></div>
<?php if($msg): ?><div class="alert alert-ok"><?=h($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger"><?=h($err)?></div><?php endif; ?>
<div class="grid">
  <div class="card"><h2 class="section-title">⚙️ Paramètres généraux</h2><form method="post" enctype="multipart/form-data"><input type="hidden" name="form" value="settings">
    <div class="form-row"><label>Nom affiché</label><input name="company_name" value="<?=h($settings['company_name']??'')?>"></div>
    <div class="form-row"><label>Logo entreprise</label><input type="file" name="logo_file" accept=".png,.jpg,.jpeg,.webp,.svg,image/png,image/jpeg,image/webp,image/svg+xml"><p class="muted mini">Prérequis : PNG, JPG, WEBP ou SVG. Maximum 600 × 200 px et 1 Mo. Si vide, les initiales de l’entreprise seront utilisées.</p><?php if(!empty($settings['logo_url'])): ?><div class="logo-preview"><img src="<?=h($settings['logo_url'])?>" alt="Logo actuel"><label><input type="checkbox" name="remove_logo" style="width:auto"> supprimer le logo actuel</label></div><?php endif; ?><input type="hidden" name="logo_url" value="<?=h($settings['logo_url']??'')?>"></div>
    <div class="form-row"><label>Couleur principale</label><div class="color-row"><input type="color" name="primary_color_picker" value="<?=h(sanitize_color($settings['primary_color']??'#2563eb'))?>" oninput="document.querySelector('[name=primary_color]').value=this.value"><input name="primary_color" value="<?=h(sanitize_color($settings['primary_color']??'#2563eb'))?>" placeholder="#2563eb"></div><p class="muted mini">Utilisée pour les boutons, le logo avec initiales, les titres et les traits de la fiche PDF.</p></div>
    <div class="form-row"><label>Domaine mail</label><input name="mail_domain" value="<?=h($settings['mail_domain']??'') ?>" placeholder="lodi.fr"></div>
    <div class="form-row"><label>Modèle d’identifiant par défaut</label><select name="default_login_mode"><option value="flast" <?=($settings['default_login_mode']==='flast'?'selected':'')?>>c.martin</option><option value="first.last" <?=($settings['default_login_mode']==='first.last'?'selected':'')?>>camille.martin</option><option value="firstlast" <?=($settings['default_login_mode']==='firstlast'?'selected':'')?>>cmartin</option><option value="email" <?=($settings['default_login_mode']==='email'?'selected':'')?>>adresse mail</option></select></div>
    <div class="form-row"><label>Longueur mot de passe</label><input type="number" name="password_length" value="<?=h($settings['password_length']??12)?>" min="6" max="64"></div><button class="btn">Enregistrer</button>
  </form></div>
  <div class="card"><h2 class="section-title">➕ Ajouter une application</h2><form method="post"><input type="hidden" name="form" value="app">
    <div class="form-row"><label>Nom</label><input name="name" required></div>
    <div class="form-row"><label>URL de l’application</label><input name="url" placeholder="https://exemple.fr"></div>
    <div class="grid"><div class="form-row"><label>Modèle identifiant</label><select name="login_mode"><option value="">Par défaut</option><option value="flast">c.martin</option><option value="first.last">camille.martin</option><option value="firstlast">cmartin</option><option value="email">adresse mail</option></select></div><div class="form-row"><label>Ordre</label><input type="number" name="sort_order" value="100"></div></div>
    <div class="form-row"><label><input style="width:auto" type="checkbox" name="active" checked> Application active</label></div>
    <details class="help-box"><summary>Voir les balises disponibles pour les scripts</summary><div class="tag-grid"><?php foreach($tags as $tag=>$desc): ?><button type="button" class="tag" onclick="copyValue('<?=h($tag)?>', this)"><?=h($tag)?></button><span><?=h($desc)?></span><?php endforeach; ?></div></details>
    <div class="form-row"><label>Script optionnel</label><textarea name="script_template" placeholder="Exemple : net user {{identifiant}} {{mot_de_passe}} /add"></textarea></div>
    <div class="form-row"><label>Notes internes</label><textarea name="notes"></textarea></div><button class="btn">Ajouter</button>
  </form></div>
</div>
<div class="card"><h2 class="section-title">🧩 Applications configurées</h2><div class="table-wrap"><table class="table"><thead><tr><th>Nom</th><th>Actif</th><th>Identifiant</th><th>URL</th><th>Script / notes</th><th>Ordre</th><th>Actions</th></tr></thead><tbody><?php foreach($apps as $a): ?><tr><form method="post"><input type="hidden" name="form" value="app"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><td><input name="name" value="<?=h($a['name'])?>"></td><td><input style="width:auto" type="checkbox" name="active" <?= $a['active']?'checked':'' ?>></td><td><select name="login_mode"><option value="" <?=($a['login_mode']===''?'selected':'')?>>Par défaut</option><option value="flast" <?=($a['login_mode']==='flast'?'selected':'')?>>c.martin</option><option value="first.last" <?=($a['login_mode']==='first.last'?'selected':'')?>>camille.martin</option><option value="firstlast" <?=($a['login_mode']==='firstlast'?'selected':'')?>>cmartin</option><option value="email" <?=($a['login_mode']==='email'?'selected':'')?>>adresse mail</option></select></td><td><input name="url" value="<?=h($a['url']??'')?>" placeholder="https://..."></td><td><details class="help-box compact"><summary>Balises</summary><div class="tag-grid compact-tags"><?php foreach($tags as $tag=>$desc): ?><button type="button" class="tag" onclick="copyValue('<?=h($tag)?>', this)"><?=h($tag)?></button><span><?=h($desc)?></span><?php endforeach; ?></div></details><textarea name="script_template" placeholder="Script optionnel" style="min-height:110px"><?=h($a['script_template']??'')?></textarea><textarea name="notes" placeholder="Notes internes" style="margin-top:8px;min-height:55px"><?=h($a['notes']??'')?></textarea></td><td><input type="number" name="sort_order" value="<?=h($a['sort_order'])?>"></td><td><div class="actions"><button class="btn secondary">OK</button></form><form method="post" onsubmit="return confirm('Supprimer cette application ?')"><input type="hidden" name="form" value="delete_app"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn danger">Supprimer</button></form></div></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php render_footer(); ?>
