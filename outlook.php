<?php
require_once __DIR__.'/includes/functions.php';
require_login();
$gen=$_SESSION['last_generation'] ?? null;
if(!$gen){ exit('Aucune génération disponible.'); }
if(empty($gen['has_outlook'])){ exit('Le fichier Excel / Outlook est disponible uniquement si l’application Office 365 est cochée lors de la génération.'); }
$filename='import_user_'.$gen['last'].'_'.$gen['first'].'.csv';
$filename=safe_filename($filename);
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Pragma: no-cache');
header('Expires: 0');
$out=fopen('php://output','w');
fwrite($out, "\xEF\xBB\xBF");
$headers=['Nom d’utilisateur','Prénom','Nom de famille','Afficher le nom','Poste','Département','Numéro du bureau','Téléphone (bureau)','Téléphone mobile','Fax','Adresse e-mail de secours','Adresse','Ville','Etat ou province','Code postal','Pays ou région'];
fputcsv($out, $headers, ',');
fputcsv($out, [
    $gen['email'] ?? '',
    $gen['first'] ?? '',
    $gen['last'] ?? '',
    $gen['full_name'] ?? '',
    '',
    '',
    $gen['phone_internal'] ?? '',
    $gen['phone_external'] ?? '',
    $gen['phone_mobile'] ?? '',
    '',
    '',
    '',
    '',
    '',
    '',
    ''
], ',');
fclose($out);
exit;
