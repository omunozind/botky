<?php
/**
 * Plugin Name: Innovación 360 Partner Connect
 * Plugin URI:  https://uchat.com.au
 * Description: Gestión completa de Workspaces y Clientes conectada a UChat API. (SaaS Edition - Bilingüe)
 * Version:     7.6.0 (Logo & Logout Fix)
 * Author:      Innovación 360
 * License:     GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'I360_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'I360_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once I360_PLUGIN_DIR . 'includes/funciones.php';
require_once I360_PLUGIN_DIR . 'includes/admin-billetera.php';

// ==========================================
// 0. SISTEMA DE IDIOMAS (DICCIONARIO BLINDADO)
// ==========================================
function i360_get_dictionary() {
    $locale = get_locale();
    $lang = (strpos($locale, 'en') === 0) ? 'en' : 'es';

    $dict = [
        'es' => [
            // Textos HTML
            'restricted_access' => 'Acceso Restringido',
            'login_required' => 'Necesitas iniciar sesi&oacute;n para acceder a tu panel.',
            'login_button' => 'Iniciar Sesi&oacute;n',
            'partner_admin' => 'Partner Beta',
            'connected' => 'Conectado',
            'no_api_key' => 'Sin API Key',
            'management' => 'Gesti&oacute;n',
            'workspaces' => 'Workspaces',
            'clients' => 'Clientes',
            'billing' => 'Facturaci&oacute;n',
            'topups' => 'Recargas',
            'settings' => 'Configuraci&oacute;n',
            'logout' => 'Cerrar Sesi&oacute;n',
            'spaces' => 'Espacios',
            'search_id' => 'Buscar ID...',
            'search_clients' => 'Buscar clientes...',
            'balance' => 'Saldo',
            'current_balance' => 'Saldo Actual',
            'new' => 'Nuevo',
            'id' => 'ID',
            'workspace' => 'Workspace',
            'plan' => 'Plan',
            'usage' => 'Uso',
            'pts' => 'Pts',
            'addons' => 'Addons',
            'client' => 'Cliente',
            'contact' => 'Contacto',
            'teams' => 'Equipos',
            'total' => 'Total',
            'no_data' => 'Sin datos',
            'no_workspaces' => 'No se encontraron workspaces.',
            'no_clients' => 'No se encontraron clientes.',
            'loading' => 'Cargando...',
            'manage_balance' => 'Gestiona el saldo de tu cuenta.',
            'how_topups_work' => '&iquest;C&oacute;mo funcionan las recargas?',
            'topups_desc' => 'El saldo recargado se usar&aacute; para pagar autom&aacute;ticamente tus suscripciones y addons. Ingresa el monto abajo.',
            'make_topup' => 'Realizar una Recarga',
            'secure_payment' => 'Pagos procesados de forma segura por Stripe.',
            'connection' => 'Conexi&oacute;n con tu espacio de trabajo',
            'api_credentials' => 'Credenciales de API',
            'enter_token' => 'Ingresa tu Token de Partner.',
            'save' => 'Guardar',
            'configure' => 'Configurar',
            'status' => 'Estado',
            'active' => 'Activo',
            'change_plan' => 'Cambiar Plan',
            'points_management' => 'Gesti&oacute;n de Puntos',
            'available' => 'Disponible',
            'adjust_balance' => 'Ajustar Saldo',
            'adjust_desc' => 'Positivo (+) agrega, Negativo (-) reduce',
            'available_addons' => 'Addons Disponibles',
            'cancel' => 'Cancelar',
            'save_changes' => 'Guardar Cambios',
            'confirm_changes' => 'Confirmar Cambios',
            'confirm_desc' => 'Est&aacute;s a punto de aplicar los siguientes ajustes:',
            'confirm_and_save' => 'Confirmar y Guardar',
            'insufficient_balance' => 'Saldo Insuficiente',
            'insufficient_desc_1' => 'Esta operaci&oacute;n tiene un costo de ',
            'insufficient_desc_2' => ', pero tu saldo disponible es de ',
            'topup_now' => 'Recargar Ahora',
            'new_workspace' => 'Nuevo Workspace',
            'admin_user' => 'Usuario Admin',
            'name' => 'Nombre',
            'email' => 'Email',
            'password' => 'Contrase&ntilde;a',
            'phone' => 'Tel&eacute;fono',
            'company_name' => 'Nombre de la empresa',
            'language' => 'Idioma',
            'spanish' => 'Espa&ntilde;ol',
            'english' => 'Ingl&eacute;s',
            'trial_days' => 'D&iacute;as Prueba',
            'optional' => 'Opcional',
            'verify_email' => 'Verificar Email',
            'create' => 'Crear',
            
            // Textos JS
            'prev' => 'Ant',
            'next' => 'Sig',
            'showing' => 'Mostrando',
            'of' => 'de',
            'loading_data' => 'Cargando datos...',
            'loading_addons' => 'Cargando addons...',
            'danger_zone' => 'Zona de Peligro',
            'delete_workspace' => 'Eliminar Workspace',
            'irreversible' => 'Irreversible',
            'free' => 'Gratis',
            'add' => 'Agregar',
            'remove' => 'Quitar',
            'topup' => 'Recargar',
            'deduct' => 'Deducir',
            'points_free' => 'puntos (Gratis)',
            'no_changes' => 'No hay cambios para guardar',
            'total_to_pay' => 'Total a Pagar:',
            'points_required' => 'Puntos requeridos:',
            'confirm' => 'Confirmar',
            'accept' => 'Aceptar',
            'error' => 'Error',
            'success_saved' => 'Cambios guardados correctamente',
            'success_created' => 'Workspace creado',
            'success_deleted' => 'Workspace eliminado correctamente',
            'success_settings' => 'Configuracion guardada',
            'confirm_delete_title' => 'Eliminar Workspace?',
            'confirm_delete_desc' => 'Esta accion no se puede deshacer. Se perderan todos los datos.',
            'error_addon' => 'Error en Addon',
            'error_process' => 'No se pudo procesar',
            'error_details' => 'Error cargando detalles',
            'error_settings' => 'No se pudo guardar la configuracion',
            'cost_of_operation' => 'Costo de la operacion',
            'available_balance' => 'Saldo disponible',
            'insufficient_points' => 'Puntos Insuficientes',
            'points_error_msg' => 'El workspace no tiene suficientes puntos para activar este addon.',
            'required_points' => 'Puntos requeridos',
            'current_points' => 'Puntos actuales',
            'understood' => 'Entendido',
        ],
        'en' => [
            'restricted_access' => 'Restricted Access',
            'login_required' => 'You need to log in to access your dashboard.',
            'login_button' => 'Log In',
            'partner_admin' => 'Partner Beta',
            'connected' => 'Connected',
            'no_api_key' => 'No API Key',
            'management' => 'Management',
            'workspaces' => 'Workspaces',
            'clients' => 'Clients',
            'billing' => 'Billing',
            'topups' => 'Top-ups',
            'settings' => 'Settings',
            'logout' => 'Log Out',
            'spaces' => 'Spaces',
            'search_id' => 'Search ID...',
            'search_clients' => 'Search clients...',
            'balance' => 'Balance',
            'current_balance' => 'Current Balance',
            'new' => 'New',
            'id' => 'ID',
            'workspace' => 'Workspace',
            'plan' => 'Plan',
            'usage' => 'Usage',
            'pts' => 'Pts',
            'addons' => 'Addons',
            'client' => 'Client',
            'contact' => 'Contact',
            'teams' => 'Teams',
            'total' => 'Total',
            'no_data' => 'No data',
            'no_workspaces' => 'No workspaces found.',
            'no_clients' => 'No clients found.',
            'loading' => 'Loading...',
            'manage_balance' => 'Manage your account balance.',
            'how_topups_work' => 'How do top-ups work?',
            'topups_desc' => 'The topped-up balance will be used to automatically pay for your subscriptions and addons. Enter the desired amount below.',
            'make_topup' => 'Make a Top-up',
            'secure_payment' => 'Payments processed securely by Stripe.',
            'connection' => 'Connection to your workspace',
            'api_credentials' => 'API Credentials',
            'enter_token' => 'Enter your Partner Token.',
            'save' => 'Save',
            'configure' => 'Configure',
            'status' => 'Status',
            'active' => 'Active',
            'change_plan' => 'Change Plan',
            'points_management' => 'Points Management',
            'available' => 'Available',
            'adjust_balance' => 'Adjust Balance',
            'adjust_desc' => 'Positive (+) adds, Negative (-) deducts',
            'available_addons' => 'Available Addons',
            'cancel' => 'Cancel',
            'save_changes' => 'Save Changes',
            'confirm_changes' => 'Confirm Changes',
            'confirm_desc' => 'You are about to apply the following changes:',
            'confirm_and_save' => 'Confirm and Save',
            'insufficient_balance' => 'Insufficient Balance',
            'insufficient_desc_1' => 'This operation costs ',
            'insufficient_desc_2' => ', but your available balance is ',
            'topup_now' => 'Top up Now',
            'new_workspace' => 'New Workspace',
            'admin_user' => 'Admin User',
            'name' => 'Name',
            'email' => 'Email',
            'password' => 'Password',
            'phone' => 'Phone',
            'company_name' => 'Company Name',
            'language' => 'Language',
            'spanish' => 'Spanish',
            'english' => 'English',
            'trial_days' => 'Trial Days',
            'optional' => 'Optional',
            'verify_email' => 'Verify Email',
            'create' => 'Create',
            // Para JS
            'prev' => 'Prev',
            'next' => 'Next',
            'showing' => 'Showing',
            'of' => 'of',
            'loading_data' => 'Loading data...',
            'loading_addons' => 'Loading addons...',
            'danger_zone' => 'Danger Zone',
            'delete_workspace' => 'Delete Workspace',
            'irreversible' => 'Irreversible',
            'free' => 'Free',
            'add' => 'Add',
            'remove' => 'Remove',
            'topup' => 'Top-up',
            'deduct' => 'Deduct',
            'points_free' => 'points (Free)',
            'no_changes' => 'No changes to save',
            'total_to_pay' => 'Total to Pay:',
            'points_required' => 'Points required:',
            'confirm' => 'Confirm',
            'accept' => 'Accept',
            'error' => 'Error',
            'success_saved' => 'Changes saved successfully',
            'success_created' => 'Workspace created',
            'success_deleted' => 'Workspace deleted successfully',
            'success_settings' => 'Settings saved',
            'confirm_delete_title' => 'Delete Workspace?',
            'confirm_delete_desc' => 'This action cannot be undone. All data will be lost.',
            'error_addon' => 'Addon Error',
            'error_process' => 'Could not process',
            'error_details' => 'Error loading details',
            'error_settings' => 'Could not save settings',
            'cost_of_operation' => 'Cost of operation',
            'available_balance' => 'Available balance',
            'insufficient_points' => 'Insufficient Points',
            'points_error_msg' => 'The workspace does not have enough points to activate this addon.',
            'required_points' => 'Required points',
            'current_points' => 'Current points',
            'understood' => 'Understood',
        ]
    ];

    return $dict[$lang];
}

// ==========================================
// 1. CARGA DE ASSETS (ADMINISTRADOR WP)
// ==========================================
function i360_enqueue_assets($hook) {
    if ($hook != 'toplevel_page_i360-partner-panel') {
        return;
    }

    wp_enqueue_style( 'i360-styles', I360_PLUGIN_URL . 'assets/css/style.css', array(), time() );
    wp_enqueue_script( 'i360-script', I360_PLUGIN_URL . 'assets/js/script.js', array('jquery'), time(), true );

    $current_user = wp_get_current_user();
    $saved_api_key = get_user_meta($current_user->ID, 'i360_partner_api_key', true);
    $balance = (float) get_user_meta($current_user->ID, 'i360_wallet_balance', true);
    $textos = i360_get_dictionary(); 

    wp_localize_script( 'i360-script', 'i360Settings', array(
        'ajaxUrl'        => admin_url('admin-ajax.php'),
        'nonce'          => wp_create_nonce('i360_nonce'),
        'hasApiKey'      => !empty($saved_api_key),
        'currentBalance' => $balance,
        'i18n'           => $textos 
    ));
}
add_action( 'admin_enqueue_scripts', 'i360_enqueue_assets' );

// ==========================================
// 2. MODO FRONTEND (SHORTCODE [botky_panel])
// ==========================================
function i360_cargar_assets_frontend() {
    wp_enqueue_style('i360-styles', I360_PLUGIN_URL . 'assets/css/style.css', array(), time());
    wp_enqueue_script('i360-script', I360_PLUGIN_URL . 'assets/js/script.js', array('jquery'), time(), true);

    $current_user = wp_get_current_user();
    $saved_api_key = get_user_meta($current_user->ID, 'i360_partner_api_key', true);
    $balance = (float) get_user_meta($current_user->ID, 'i360_wallet_balance', true);
    $textos = i360_get_dictionary(); 

    wp_localize_script('i360-script', 'i360Settings', array(
        'ajaxUrl'        => admin_url('admin-ajax.php'),
        'nonce'          => wp_create_nonce('i360_nonce'),
        'hasApiKey'      => !empty($saved_api_key),
        'currentBalance' => $balance,
        'i18n'           => $textos 
    ));
}

function i360_registrar_shortcode() {
    $textos = i360_get_dictionary(); 

    if (!is_user_logged_in()) {
        return '<div style="text-align:center; padding:80px 20px; font-family:sans-serif;">
                    <h2 style="margin-bottom:20px; color:#1e293b;">' . $textos['restricted_access'] . '</h2>
                    <p style="margin-bottom:30px; color:#64748b;">' . $textos['login_required'] . '</p>
                    <a href="' . wp_login_url(get_permalink()) . '" style="background:#0f172a; color:#fff; padding:12px 24px; text-decoration:none; border-radius:8px; font-weight:bold;">' . $textos['login_button'] . '</a>
                </div>';
    }

    i360_cargar_assets_frontend();

    $current_user = wp_get_current_user();
    $saved_api_key = get_user_meta($current_user->ID, 'i360_partner_api_key', true);
    $has_api_key = !empty($saved_api_key);
    $wallet_balance = (float) get_user_meta($current_user->ID, 'i360_wallet_balance', true);

    ob_start();
    include I360_PLUGIN_DIR . 'templates/vista-publica.php';
    return ob_get_clean();
}
add_shortcode('botky_panel', 'i360_registrar_shortcode');


// ==========================================
// 3. MENÚ DE ADMINISTRACIÓN
// ==========================================
function i360_register_menu() {
    add_menu_page('Partner Dashboard', 'Partner Connect', 'manage_options', 'i360-partner-panel', 'i360_render_view', 'dashicons-networking', 3);
}
add_action('admin_menu', 'i360_register_menu');

function i360_render_view() {
    $current_user = wp_get_current_user();
    $saved_api_key = get_user_meta($current_user->ID, 'i360_partner_api_key', true);
    $has_api_key = !empty($saved_api_key);
    $wallet_balance = (float) get_user_meta($current_user->ID, 'i360_wallet_balance', true);
    
    $textos = i360_get_dictionary(); 

    include I360_PLUGIN_DIR . 'templates/vista-publica.php';
}

// ==========================================
// 4. WEBHOOK DE STRIPE
// ==========================================
add_action('rest_api_init', function () {
    register_rest_route('i360/v1', '/stripe-webhook', array(
        'methods' => 'POST', 'callback' => 'i360_procesar_pago_stripe', 'permission_callback' => '__return_true',
    ));
});

function i360_procesar_pago_stripe($request) {
    $payload = $request->get_body();
    $event = json_decode($payload);
    if (isset($event->type) && $event->type == 'checkout.session.completed') {
        $session = $event->data->object;
        $user_id = isset($session->client_reference_id) ? intval($session->client_reference_id) : 0;
        $monto_pagado = isset($session->amount_total) ? ($session->amount_total / 100) : 0;
        if ($user_id > 0 && $monto_pagado > 0) {
            $saldo_actual = (float) get_user_meta($user_id, 'i360_wallet_balance', true);
            update_user_meta($user_id, 'i360_wallet_balance', $saldo_actual + $monto_pagado);
            return new WP_REST_Response(['mensaje' => 'Saldo actualizado'], 200);
        }
    }
    return new WP_REST_Response('Evento recibido', 200);
}