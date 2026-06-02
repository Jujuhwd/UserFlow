<?php
require_once __DIR__.'/auth.php';
function random_password(int $len=12): string {
    $chars='ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    $out=''; for($i=0;$i<$len;$i++) $out.=$chars[random_int(0,strlen($chars)-1)]; return $out;
}
function normalize_login_part(string $s): string {
    $s = trim(mb_strtolower($s));
    $from=['à','á','â','ä','ã','å','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','ö','õ','ù','ú','û','ü','ý','ÿ','œ','æ'];
    $to  =['a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','u','y','y','oe','ae'];
    $s=str_replace($from,$to,$s);
    $s=preg_replace('/[^a-z0-9]+/u','.', $s);
    return trim($s,'.');
}
function make_login(string $first,string $last,string $mode,string $domain): string {
    $f=normalize_login_part($first); $l=normalize_login_part($last); $domain=trim($domain);
    if($mode==='email') return $f.'.'.$l.'@'.$domain;
    if($mode==='first.last') return $f.'.'.$l;
    if($mode==='firstlast') return $f.$l;
    return mb_substr($f,0,1).'.'.$l;
}
function get_settings(): array { return db()->query('SELECT * FROM settings WHERE id=1')->fetch() ?: []; }
function render_template_vars(string $template, array $vars): string {
    foreach ($vars as $k=>$v) $template = str_replace('{{'.$k.'}}', (string)$v, $template);
    return $template;
}
function safe_filename(string $s): string {
    $s = normalize_login_part($s);
    return preg_replace('/[^a-z0-9._-]+/','-', $s) ?: 'export';
}
function initials_from_company(string $name): string {
    $name = trim($name ?: 'UserFlow DEMO');
    $parts = preg_split('/\s+/', $name);
    $letters='';
    foreach($parts as $p){ if($p!=='') $letters .= mb_substr($p,0,1); if(mb_strlen($letters)>=2) break; }
    return mb_strtoupper($letters ?: 'UF');
}

function sanitize_color(string $color): string {
    $color = trim($color);
    if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) return $color;
    return '#2563eb';
}
function is_outlook_app(array $app): bool {
    // Export CSV Outlook/Microsoft 365 uniquement pour l'application Office 365.
    // On accepte quelques variantes de nommage pour éviter les blocages.
    $name = mb_strtolower(trim((string)($app['name'] ?? '')));
    $name = str_replace(['-', '_'], ' ', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    return in_array($name, ['office 365', 'microsoft 365', 'm365', 'o365'], true);
}
function available_script_tags(): array {
    return [
        '{{prenom}}' => 'Prénom de l’utilisateur',
        '{{nom}}' => 'Nom de l’utilisateur',
        '{{nom_complet}}' => 'Prénom + nom',
        '{{identifiant}}' => 'Identifiant généré pour cette application',
        '{{identifiant_defaut}}' => 'Identifiant selon le modèle général',
        '{{email}}' => 'Adresse mail générée',
        '{{mot_de_passe}}' => 'Mot de passe généré',
        '{{telephone_mobile}}' => 'Téléphone mobile',
        '{{telephone_interne}}' => 'Téléphone interne',
        '{{telephone_externe}}' => 'Téléphone externe',
        '{{url}}' => 'URL de l’application',
        '{{domaine_mail}}' => 'Domaine mail configuré',
        '{{application}}' => 'Nom de l’application',
    ];
}
