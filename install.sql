-- =====================================================
-- UserFlow DEMO - Installation complète
-- Domaine de démonstration : demo.test
-- Compte par défaut : admin@demo.test / admin1234
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  id INT PRIMARY KEY,
  company_name VARCHAR(190) NOT NULL DEFAULT 'UserFlow DEMO',
  mail_domain VARCHAR(190) NOT NULL DEFAULT 'demo.test',
  default_login_mode ENUM('flast','first.last','firstlast','email') NOT NULL DEFAULT 'first.last',
  password_length INT NOT NULL DEFAULT 12,
  logo_url VARCHAR(500) DEFAULT '',
  primary_color VARCHAR(20) DEFAULT '#2563eb'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  login_mode ENUM('','flast','first.last','firstlast','email') NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 100,
  notes TEXT NULL,
  url VARCHAR(500) DEFAULT '',
  script_template MEDIUMTEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Administrateur de démonstration : admin@demo.test / admin1234
INSERT INTO users (name,email,password_hash,role,active)
VALUES ('Administrateur Démo','admin@demo.test','$2y$12$VcV6gD26EP6qSlYrQVdsluX64h/cxndf3woI8CJbBKQvE6d7j/2ZW','admin',1);

INSERT INTO settings (id,company_name,mail_domain,default_login_mode,password_length,logo_url,primary_color)
VALUES (1,'UserFlow DEMO','demo.test','first.last',12,'','#2563eb');

INSERT INTO applications (name,active,login_mode,url,sort_order,notes,script_template) VALUES
('Office 365',1,'email','https://portal.office.com',10,'Application conservée pour montrer l’export CSV Outlook / Microsoft 365.',''),
('Session Windows',1,'first.last','',20,'Exemple de création de session locale Windows.','net user "{{identifiant}}" "{{mot_de_passe}}" /add\r\nnet localgroup "Utilisateurs" "{{identifiant}}" /add'),
('Dossier partagé utilisateur',1,'first.last','\\\\srv-fichiers\\Utilisateurs',30,'Exemple de script PowerShell pour préparer un dossier utilisateur.','New-Item -ItemType Directory -Path "D:\\Utilisateurs\\{{identifiant}}" -Force\r\nicacls "D:\\Utilisateurs\\{{identifiant}}" /grant "ENTREPRISE\\{{identifiant}}:(OI)(CI)M"'),
('VPN entreprise',1,'first.last','https://vpn.demo.test',40,'Exemple d’accès VPN.','Add-VpnUser -UserName "{{identifiant}}" -DisplayName "{{nom_complet}}"'),
('CRM Entreprise',1,'email','https://crm.demo.test',50,'Application métier fictive.',''),
('Nextcloud',1,'email','https://cloud.demo.test',60,'Espace cloud fictif.','occ user:add --display-name="{{nom_complet}}" --group="Collaborateurs" "{{identifiant}}"'),
('ERP Gestion',1,'first.last','https://erp.demo.test',70,'Application fictive désactivée pour montrer la gestion actif/inactif.',''),
('3CX Web Client',1,'email','https://3cx.demo.test/webclient',80,'Téléphonie fictive.','Extension interne prévue : {{telephone_interne}}');
