<?php
/*
Plugin Name: Mon Plugin
Description: Un plugin qui affiche un formulaire et enregistre les données dans la base de données.
Version: 1.0
Author: Mohamed zribi
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Create shortcode to display the form
function mp_form_shortcode($is_admin = false) {
    ob_start();
    ?>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" style="background-color: #f9f9f9; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); max-width: 400px; margin: 0 auto;">
        <input type="hidden" name="action" value="<?php echo $is_admin ? 'mp_save_admin_form' : 'mp_save_form'; ?>">
        <label for="nom" style="font-weight: bold; display: block; margin-bottom: 5px;">Nom:</label>
        <input type="text" id="nom" name="nom" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
        <label for="prenom" style="font-weight: bold; display: block; margin-bottom: 5px;">Prénom:</label>
        <input type="text" id="prenom" name="prenom" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
        <label for="adresse" style="font-weight: bold; display: block; margin-bottom: 5px;">Adresse:</label>
        <input type="text" id="adresse" name="adresse" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
        <label for="email" style="font-weight: bold; display: block; margin-bottom: 5px;">Email:</label>
        <input type="email" id="email" name="email" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
        <input type="submit" value="Ajouter" style="background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('mp_form', 'mp_form_shortcode');

// Handle form submission
function mp_save_form() {
    if (isset($_POST['nom'], $_POST['prenom'], $_POST['adresse'], $_POST['email'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mp_data';

        $nom = sanitize_text_field($_POST['nom']);
        $prenom = sanitize_text_field($_POST['prenom']);
        $adresse = sanitize_text_field($_POST['adresse']);
        $email = sanitize_email($_POST['email']);

        $wpdb->insert(
            $table_name,
            [
                'nom' => $nom,
                'prenom' => $prenom,
                'adresse' => $adresse,
                'email' => $email,
            ]
        );

        wp_redirect(home_url());
        exit;
    }
}
add_action('admin_post_nopriv_mp_save_form', 'mp_save_form');
add_action('admin_post_mp_save_form', 'mp_save_form');

// Handle admin form submission
function mp_save_admin_form() {
    if (isset($_POST['nom'], $_POST['prenom'], $_POST['adresse'], $_POST['email'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mp_data';

        $nom = sanitize_text_field($_POST['nom']);
        $prenom = sanitize_text_field($_POST['prenom']);
        $adresse = sanitize_text_field($_POST['adresse']);
        $email = sanitize_email($_POST['email']);

        $wpdb->insert(
            $table_name,
            [
                'nom' => $nom,
                'prenom' => $prenom,
                'adresse' => $adresse,
                'email' => $email,
            ]
        );

        wp_redirect(admin_url('admin.php?page=mon-plugin'));
        exit;
    }
}
add_action('admin_post_mp_save_admin_form', 'mp_save_admin_form');

// Create the table to store form data upon plugin activation
function mp_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mp_data';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nom tinytext NOT NULL,
        prenom tinytext NOT NULL,
        adresse text NOT NULL,
        email varchar(100) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'mp_create_table');

// Add admin menu item
function mp_add_admin_menu() {
    add_menu_page(
        'Mon Plugin',
        'Mon Plugin',
        'manage_options',
        'mon-plugin',
        'mp_admin_page',
        'dashicons-admin-generic',
        6
    );
    add_submenu_page(
        null,
        'Modifier Enregistrement',
        'Modifier Enregistrement',
        'manage_options',
        'mon-plugin-edit',
        'mp_edit_page'
    );
    add_submenu_page(
        null,
        'Ajouter Utilisateur',
        'Ajouter Utilisateur',
        'manage_options',
        'mon-plugin-add-user',
        'mp_add_user_page'
    );
}
add_action('admin_menu', 'mp_add_admin_menu');

// Display admin page
function mp_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mp_data';
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['delete'])) {
            $wpdb->delete($table_name, ['id' => intval($_POST['id'])]);
        }
    }

    $results = $wpdb->get_results("SELECT * FROM $table_name");
    ?>
    <div class="wrap">
        <h1>Formulaires Enregistrés</h1>
        <a href="<?php echo admin_url('admin.php?page=mon-plugin-add-user'); ?>" class="button button-primary" style="margin-bottom: 20px; background-color:green;">Ajouter Utilisateur</a>
        <table class="widefat fixed" cellspacing="0" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th class="manage-column column-columnname" scope="col">ID</th>
                    <th class="manage-column column-columnname" scope="col">Nom</th>
                    <th class="manage-column column-columnname" scope="col">Prénom</th>
                    <th class="manage-column column-columnname" scope="col">Adresse</th>
                    <th class="manage-column column-columnname" scope="col">Email</th>
                    <th class="manage-column column-columnname" scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($results) {
                    foreach ($results as $row) {
                        echo "<tr>";
                        echo "<td>{$row->id}</td>";
                        echo "<td>{$row->nom}</td>";
                        echo "<td>{$row->prenom}</td>";
                        echo "<td>{$row->adresse}</td>";
                        echo "<td>{$row->email}</td>";
                        echo "<td style='display: flex; gap: 5px;'>
                                <a href='" . admin_url('admin.php?page=mon-plugin-edit&id=' . $row->id) . "' class='button button-primary' style='padding: 5px 10px; background-color:green;'>Modifier</a>
                                <form method='POST' style='margin: 0;'>
                                    <input type='hidden' name='id' value='{$row->id}'>
                                    <button type='submit' name='delete' class='button button-secondary' style='padding: 5px 10px; background-color:red; color:white;'>Supprimer</button>
                                </form> 
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>Aucune donnée trouvée.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Display edit page
function mp_edit_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mp_data';

    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

        if ($row) {
            ?>
            <div class="wrap">
                <h1>Modifier Enregistrement</h1>
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" style="background-color: #f9f9f9; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); max-width: 400px;">
                    <input type="hidden" name="action" value="mp_update_form">
                    <input type="hidden" name="id" value="<?php echo $row->id; ?>">
                    <label for="nom" style="font-weight: bold; display: block; margin-bottom: 5px;">Nom:</label>
                    <input type="text" id="nom" name="nom" value="<?php echo esc_attr($row->nom); ?>" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <label for="prenom" style="font-weight: bold; display: block; margin-bottom: 5px;">Prénom:</label>
                    <input type="text" id="prenom" name="prenom" value="<?php echo esc_attr($row->prenom); ?>" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <label for="adresse" style="font-weight: bold; display: block; margin-bottom: 5px;">Adresse:</label>
                    <input type="text" id="adresse" name="adresse" value="<?php echo esc_attr($row->adresse); ?>" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <label for="email" style="font-weight: bold; display: block; margin-bottom: 5px;">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo esc_attr($row->email); ?>" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="submit" value="Mettre à jour" style="background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">
                </form>
            </div>
            <?php
        } else {
            echo "<div class='wrap'><h1>Enregistrement non trouvé</h1></div>";
        }
    } else {
        echo "<div class='wrap'><h1>ID invalide</h1></div>";
    }
}

// Display add user page
function mp_add_user_page() {
    ?>
    <div class="wrap">
        <h1>Ajouter Utilisateur</h1>
        <?php echo mp_form_shortcode(true); ?>
    </div>
    <?php
}

// Handle form update
function mp_update_form() {
    if (isset($_POST['id'], $_POST['nom'], $_POST['prenom'], $_POST['adresse'], $_POST['email'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mp_data';

        $id = intval($_POST['id']);
        $nom = sanitize_text_field($_POST['nom']);
        $prenom = sanitize_text_field($_POST['prenom']);
        $adresse = sanitize_text_field($_POST['adresse']);
        $email = sanitize_email($_POST['email']);

        $wpdb->update(
            $table_name,
            [
                'nom' => $nom,
                'prenom' => $prenom,
                'adresse' => $adresse,
                'email' => $email
            ],
            ['id' => $id]
        );

        wp_redirect(admin_url('admin.php?page=mon-plugin'));
        exit;
    }
}
add_action('admin_post_mp_update_form', 'mp_update_form');
