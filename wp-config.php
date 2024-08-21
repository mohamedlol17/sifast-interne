<?php
// ** Paramètres de la base de données - Vous pouvez obtenir ces informations de votre web hébergeur ** //
/** Nom de la base de données de WordPress */
define('DB_NAME', 'mp_data');

/** Utilisateur de la base de données MySQL */
define('DB_USER', 'user');

/** Mot de passe de la base de données MySQL */
define('DB_PASSWORD', 'midou500');

/** Adresse de l'hébergement MySQL */
define('DB_HOST', 'localhost');

/** Jeu de caractères de la base de données à utiliser lors de la création des tables. */
define('DB_CHARSET', 'utf8');

/** Type de collation de la base de données. */
define('DB_COLLATE', '');

// Clés uniques d'authentification et salage.
define('AUTH_KEY',         'votre clé unique ici');
define('SECURE_AUTH_KEY',  'votre clé unique ici');
define('LOGGED_IN_KEY',    'votre clé unique ici');
define('NONCE_KEY',        'votre clé unique ici');
define('AUTH_SALT',        'votre clé unique ici');
define('SECURE_AUTH_SALT', 'votre clé unique ici');
define('LOGGED_IN_SALT',   'votre clé unique ici');
define('NONCE_SALT',       'votre clé unique ici');

// Préfixe de table pour WordPress.
$table_prefix  = 'mp_data';

// Mode de débogage pour les développeurs.
define('WP_DEBUG', true);

// C'est tout, ne touchez pas à ça ! Bon blogging !
?>