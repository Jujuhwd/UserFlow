<?php
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/layout.php';
$u=require_login();
$settings=get_settings();
// Mise à jour douce si update_v6.sql n'a pas encore été importé.
try { db()->exec("ALTER TABLE settings ADD COLUMN IF NOT EXISTS logo_url VARCHAR(500) DEFAULT ''"); } catch(Throwable $e) {}
try { db()->exec("ALTER TABLE settings ADD COLUMN IF NOT EXISTS primary_color VARCHAR(20) DEFAULT '#2563eb'"); } catch(Throwable $e) {}
try { db()->exec("ALTER TABLE applications ADD COLUMN IF NOT EXISTS url VARCHAR(500) DEFAULT ''"); } catch(Throwable $e) {}
try { db()->exec("ALTER TABLE applications ADD COLUMN IF NOT EXISTS script_template MEDIUMTEXT NULL"); } catch(Throwable $e) {}
$settings=get_settings();
$apps=db()->query('SELECT * FROM applications WHERE active=1 ORDER BY sort_order,name')->fetchAll();
$generated=null; $error=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    $first=trim($_POST['first_name']??'');
    $last=trim($_POST['last_name']??'');
    $phoneMobile=trim($_POST['phone_mobile']??'');
    $phoneInternal=trim($_POST['phone_internal']??'');
    $phoneExternal=trim($_POST['phone_external']??'');
    $ids=array_map('intval',$_POST['apps']??[]);
    if(!$first || !$last) $error='Nom et prénom obligatoires.';
    elseif(!$ids) $error='Sélectionne au moins une application.';
    else{
        $in=implode(',',array_fill(0,count($ids),'?'));
        $st=db()->prepare("SELECT * FROM applications WHERE active=1 AND id IN ($in) ORDER BY sort_order,name");
        $st->execute($ids);
        $selApps=$st->fetchAll();
        $items=[]; $scripts=[]; $hasOutlook=false;
        $defaultLogin = make_login($first,$last,$settings['default_login_mode'],$settings['mail_domain']);
        $emailLogin = make_login($first,$last,'email',$settings['mail_domain']);
        foreach($selApps as $app){
            if(is_outlook_app($app)) $hasOutlook=true;
            $mode=$app['login_mode']?:$settings['default_login_mode'];
            $login=make_login($first,$last,$mode,$settings['mail_domain']);
            $password=random_password((int)$settings['password_length']);
            $url = trim($app['url'] ?? '');
            $items[]=[
                'application'=>$app['name'],
                'login'=>$login,
                'password'=>$password,
                'url'=>$url,
                'notes'=>$app['notes'] ?? '',
            ];
            if(trim((string)($app['script_template'] ?? '')) !== ''){
                $vars=[
                    'prenom'=>$first,'nom'=>$last,'nom_complet'=>$first.' '.$last,
                    'identifiant'=>$login,'identifiant_defaut'=>$defaultLogin,'email'=>$emailLogin,
                    'mot_de_passe'=>$password,'telephone_mobile'=>$phoneMobile,
                    'telephone_interne'=>$phoneInternal,'telephone_externe'=>$phoneExternal,
                    'url'=>$url,'domaine_mail'=>$settings['mail_domain'],'application'=>$app['name']
                ];
                $scripts[]=['application'=>$app['name'],'script'=>render_template_vars($app['script_template'],$vars)];
            }
        }
        $_SESSION['last_generation']=[
            'first'=>$first,'last'=>$last,'full_name'=>trim($first.' '.$last),
            'email'=>$emailLogin,'phone_mobile'=>$phoneMobile,'phone_internal'=>$phoneInternal,'phone_external'=>$phoneExternal,
            'company_name'=>$settings['company_name'] ?? 'UserFlow DEMO','logo_url'=>$settings['logo_url'] ?? '',
            'primary_color'=>sanitize_color($settings['primary_color'] ?? '#2563eb'),'has_outlook'=>$hasOutlook,
            'items'=>$items,'scripts'=>$scripts
        ];
        $generated=$_SESSION['last_generation'];
    }
}
render_header('Génération','generate');
?>
<div class="page-head"><div><h1>Générer un compte</h1><p class="subtitle">Sélectionne uniquement les applications à générer pour l’utilisateur.</p></div><a class="btn secondary" href="admin.php">Configurer les applications</a></div>
<?php if($error): ?><div class="alert alert-danger"><?=h($error)?></div><?php endif; ?>
<div class="card"><form method="post"><h2 class="section-title">👤 Utilisateur</h2><div class="grid-3"><div class="form-row"><label>Prénom</label><input name="first_name" required placeholder="Camille" value="<?=h($_POST['first_name']??'')?>"></div><div class="form-row"><label>Nom</label><input name="last_name" required placeholder="MARTIN" value="<?=h($_POST['last_name']??'')?>"></div><div class="form-row"><label>Téléphone mobile</label><input name="phone_mobile" placeholder="06 00 00 00 00" value="<?=h($_POST['phone_mobile']??'')?>"></div><div class="form-row"><label>Téléphone interne</label><input name="phone_internal" placeholder="2049" value="<?=h($_POST['phone_internal']??'')?>"></div><div class="form-row"><label>Téléphone externe</label><input name="phone_external" placeholder="02 99 00 00 00" value="<?=h($_POST['phone_external']??'')?>"></div></div><h2 class="section-title">🧩 Applications à générer</h2><div class="app-select"><?php foreach($apps as $app): ?><label class="check-card"><input type="checkbox" name="apps[]" value="<?= (int)$app['id'] ?>" checked><div><strong><?=h($app['name'])?></strong><br><span class="muted mini">Identifiant : <?=h($app['login_mode'] ?: 'par défaut')?><?php if(!empty($app['url'])): ?> · URL : <?=h($app['url'])?><?php endif; ?><?php if(!empty($app['script_template'])): ?> · script prévu<?php endif; ?></span></div></label><?php endforeach; ?></div><div class="actions" style="margin-top:16px"><button class="btn">Générer</button></div></form></div>
<?php if($generated): ?>
<div class="result-sheet card">
  <div class="sheet-header">
    <div class="sheet-logo <?= !empty($generated['logo_url']) ? 'has-image' : '' ?>"><?php if(!empty($generated['logo_url'])): ?><img src="<?=h($generated['logo_url'])?>" alt="Logo"><?php else: ?><?=h(initials_from_company($generated['company_name']))?><?php endif; ?></div>
    <div><h1>Fiche d’arrivée utilisateur</h1><p class="subtitle">Identifiants générés pour <strong><?=h($generated['full_name'])?></strong></p></div>
  </div>
  <div class="info-grid">
    <div><span>Nom</span><strong><?=h($generated['full_name'])?></strong></div>
    <div><span>Email</span><strong><?=h($generated['email'])?></strong><button type="button" class="copy-icon" onclick="copyValue('<?=h($generated['email'])?>', this)" title="Copier">📋</button></div>
    <div><span>Mobile</span><strong><?=h($generated['phone_mobile'] ?: '—')?></strong></div>
    <div><span>Interne</span><strong><?=h($generated['phone_internal'] ?: '—')?></strong></div>
    <div><span>Externe</span><strong><?=h($generated['phone_external'] ?: '—')?></strong></div>
  </div>
  <div class="table-wrap"><table class="table result-table"><thead><tr><th>Application</th><th>URL</th><th>Identifiant</th><th>Mot de passe</th></tr></thead><tbody><?php foreach($generated['items'] as $idx=>$it): ?><tr><td><?=h($it['application'])?></td><td><?php if(!empty($it['url'])): ?><a href="<?=h($it['url'])?>" target="_blank"><?=h($it['url'])?></a><?php else: ?>—<?php endif; ?></td><td><span id="login_<?=$idx?>"><?=h($it['login'])?></span><button type="button" class="copy-icon" onclick="copyText('login_<?=$idx?>', this)" title="Copier l’identifiant">📋</button></td><td><span id="pwd_<?=$idx?>"><?=h($it['password'])?></span><button type="button" class="copy-icon" onclick="copyText('pwd_<?=$idx?>', this)" title="Copier le mot de passe">📋</button></td></tr><?php endforeach; ?></tbody></table></div>
  <div class="actions sheet-actions"><a class="btn" href="pdf.php" target="_blank">Générer le PDF</a><?php if(!empty($generated['has_outlook'])): ?><a class="btn secondary" href="outlook.php">Fichier Excel / Outlook</a><?php endif; ?></div>
</div>
<?php if(!empty($generated['scripts'])): ?><div class="card"><h2 class="section-title">🧾 Scripts générés</h2><p class="subtitle">Ces scripts sont affichés ici pour l’administrateur, mais ils ne sont pas dans le PDF.</p><?php foreach($generated['scripts'] as $i=>$sc): $sid='script_'.$i; ?><div class="script-box"><div class="script-head"><strong><?=h($sc['application'])?></strong><button class="btn secondary mini" type="button" onclick="copyText('<?=h($sid)?>', this)">📋 Copier</button></div><textarea id="<?=h($sid)?>" readonly><?=h($sc['script'])?></textarea></div><?php endforeach; ?></div><?php else: ?><div class="card"><h2 class="section-title">🧾 Scripts générés</h2><p class="muted">Aucun script configuré pour les applications sélectionnées.</p></div><?php endif; ?>
<?php endif; ?>
<?php render_footer(); ?>
