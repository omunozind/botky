<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================================
// CEREBRO DE PRECIOS (SISTEMA HÍBRIDO UNIFICADO)
// ==========================================

// 1. PRECIOS DE COMBOS EN PROMO (Y BASE DE ARRANQUE)
function i360_obtener_precios_combos($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();

    $precios_globales = [
        'business'        => 20,   // ESTE AHORA ES TU BASE PARA TODO
        'business_lite'   => 45,   
        'business_plus'   => 99, 
        'business_large'  => 149,
        'business_yearly' => 990
    ];

    $precios_finales = [];
    foreach ($precios_globales as $plan_id => $precio_default) {
        $precio_personalizado = get_user_meta($user_id, 'i360_precio_' . $plan_id, true);
        $precios_finales[$plan_id] = is_numeric($precio_personalizado) && $precio_personalizado !== '' ? floatval($precio_personalizado) : $precio_default;
    }

    return $precios_finales;
}

// 2. PRECIOS DE ADDONS Y EXTRAS (Todo unificado)
function i360_obtener_configuracion_venta($addon_id, $user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();

    $config_global = [
        'lists'           => 30, // Tickets
        'bot_user_large'  => 60, // 10k Users
        'bot'             => 10, // Bot
        'member'          => 10, // Member
        'bot_user'        => 10, // 1k Users
        'inbound_webhook' => 40, 
        'timeout'         => 20, 
        'custom_domain'   => 15  
    ];
    
    $default = isset($config_global[$addon_id]) ? $config_global[$addon_id] : 0;
    $precio_personalizado = get_user_meta($user_id, 'i360_addon_' . $addon_id, true);
    
    return is_numeric($precio_personalizado) && $precio_personalizado !== '' ? floatval($precio_personalizado) : $default;
}

// 3. CALCULADORA INTELIGENTE (LA MAGIA OCURRE AQUÍ)
function i360_calcular_precio_plan($plan_data) {
    if ($plan_data['id'] === 'free' || $plan_data['price'] == 0) {
        return 0;
    }

    $user_id = get_current_user_id();
    $combos = i360_obtener_precios_combos($user_id);

    // A. ¿ES UN COMBO EN PROMO? 
    if (array_key_exists($plan_data['id'], $combos)) {
        return $combos[$plan_data['id']]; 
    }

    // B. SI NO ES COMBO -> ES CUSTOM PLAN
    // Arranca usando el precio del plan 'business' como base universal
    $precio_final = $combos['business']; 

    // Límites base de UChat (Intocables)
    $limit_base_bots    = 1;
    $limit_base_users   = 1000;
    $limit_base_members = 5;

    // Sumar Extras leyendo los precios de los addons
    if ($plan_data['bots'] > $limit_base_bots) {
        $extra = $plan_data['bots'] - $limit_base_bots;
        $precio_final += ($extra * i360_obtener_configuracion_venta('bot', $user_id));
    }

    if ($plan_data['members'] > $limit_base_members) {
        $extra = $plan_data['members'] - $limit_base_members;
        $precio_final += ($extra * i360_obtener_configuracion_venta('member', $user_id));
    }

    if ($plan_data['bot_users'] > $limit_base_users) {
        $extra_users = $plan_data['bot_users'] - $limit_base_users;
        $bloques_extra = ceil($extra_users / 1000);
        $precio_final += ($bloques_extra * i360_obtener_configuracion_venta('bot_user', $user_id));
    }

    return $precio_final;
}

// ==========================================
// CONTROLADORES AJAX Y API
// ==========================================

function i360_api_request($endpoint, $method = 'GET', $body = null) {
    $user_id = get_current_user_id();
    $api_key = get_user_meta($user_id, 'i360_partner_api_key', true);

    if (!$api_key) {
        $textos = i360_get_dictionary();
        return new WP_Error('no_key', $textos['no_api_key']);
    }

    $url = 'https://www.uchat.com.au/api/' . ltrim($endpoint, '/');
    
    $args = [
        'method'  => $method,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json'
        ],
        'timeout' => 45
    ];

    if ($body && ($method === 'POST' || $method === 'PUT' || $method === 'DELETE')) {
        $args['body'] = json_encode($body);
    }

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
        $error_msg = $response->get_error_message();
        $error_msg = str_replace(
            ['ó', 'í', 'á', 'é', 'ú', 'ñ', 'Ó', 'Í', 'Á', 'É', 'Ú', 'Ñ'], 
            ['o', 'i', 'a', 'e', 'u', 'n', 'O', 'I', 'A', 'E', 'U', 'N'], 
            $error_msg
        );
        return new WP_Error('api_error', $error_msg);
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $code = wp_remote_retrieve_response_code($response);

    if ($code >= 400) {
        $msg = isset($data['message']) ? $data['message'] : 'Error API ' . $code;
        return new WP_Error('api_error', $msg);
    }

    return $data;
}

// A. Save Settings
add_action('wp_ajax_i360_save_settings', function() {
    check_ajax_referer('i360_nonce', 'security');
    $user_id = get_current_user_id();
    update_user_meta($user_id, 'i360_partner_api_key', sanitize_text_field($_POST['api_key']));
    wp_send_json_success(['message' => 'Guardado']);
});

// B. Get Workspaces
add_action('wp_ajax_i360_get_workspaces', function() {
    check_ajax_referer('i360_nonce', 'security');
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    
    if (!empty($search) && is_numeric($search)) {
        $single_data = i360_api_request('partner/workspace/' . $search, 'GET');
        if (!is_wp_error($single_data) && isset($single_data['data'])) {
            $ws_item = $single_data['data'];
            $list = isset($ws_item['id']) ? [$ws_item] : [];
            wp_send_json_success(['data' => $list, 'meta' => ['current_page'=>1, 'last_page'=>1, 'total'=>count($list), 'from'=>1, 'to'=>count($list)]]);
            return;
        }
    }

    $endpoint = 'partner/workspaces?page=' . $page;
    if (!empty($search)) $endpoint .= '&search=' . urlencode($search);

    $data = i360_api_request($endpoint, 'GET');
    if (is_wp_error($data)) wp_send_json_error(['message' => $data->get_error_message()]);
    wp_send_json_success($data);
});

// C. Get Details
add_action('wp_ajax_i360_get_workspace_details', function() {
    check_ajax_referer('i360_nonce', 'security');
    $id = sanitize_text_field($_POST['id']);
    $data = i360_api_request('partner/workspace/' . $id, 'GET');
    if (is_wp_error($data)) wp_send_json_error(['message' => $data->get_error_message()]);
    wp_send_json_success($data);
});

// D. Get Plans
add_action('wp_ajax_i360_get_plans', function() {
    check_ajax_referer('i360_nonce', 'security');
    $data = i360_api_request('partner/plans', 'GET');
    if (is_wp_error($data)) wp_send_json_error(['message' => $data->get_error_message()]);
    
    if (isset($data['data']) && is_array($data['data'])) {
        foreach ($data['data'] as &$plan) {
            $plan['price'] = i360_calcular_precio_plan($plan);
        }
    }
    wp_send_json_success($data);
});

// E. Obtener Addons
add_action('wp_ajax_i360_get_addons', function() {
    check_ajax_referer('i360_nonce', 'security');
    $user_id = get_current_user_id(); // Necesitamos al usuario para precios custom
    $data = i360_api_request('partner/addons', 'GET');
    if (is_wp_error($data)) wp_send_json_error(['message' => $data->get_error_message()]);

    if (isset($data['data']) && is_array($data['data'])) {
        foreach ($data['data'] as &$addon) {
            $costo_api = floatval($addon['price']);
            $tu_precio_venta = i360_obtener_configuracion_venta($addon['id'], $user_id);
            if ($tu_precio_venta == 0) $tu_precio_venta = $costo_api * 2;
            $addon['price'] = $tu_precio_venta;
            $puntos_api = floatval($addon['points']);
            $addon['points'] = max($tu_precio_venta, $puntos_api);
        }
    }
    wp_send_json_success($data);
});

// F. Manage Addon 
add_action('wp_ajax_i360_manage_addon', function() {
    check_ajax_referer('i360_nonce', 'security');
    $user_id = get_current_user_id();
    $textos = i360_get_dictionary(); 
    
    $ws_id = sanitize_text_field($_POST['workspace_id']);
    $action = sanitize_text_field($_POST['type']); 
    $raw_payload = json_decode(stripslashes($_POST['payload']), true);
    $price_id = sanitize_text_field($raw_payload['price_id']);
    $qty = intval($raw_payload['quantity']);

    $precio_unitario = i360_obtener_configuracion_venta($price_id, $user_id);
    if ($precio_unitario == 0) $precio_unitario = 10; 

    $total_cobrar = $precio_unitario * $qty;
    $cobro_realizado = false;

    if ($action === 'add' && $total_cobrar > 0) {
        $saldo = (float) get_user_meta($user_id, 'i360_wallet_balance', true);
        if ($saldo < $total_cobrar) {
            $error_msg = $textos['insufficient_balance'] . ". Requerido: $" . $total_cobrar . " USD.";
            wp_send_json_error(['message' => $error_msg]); return;
        }
        update_user_meta($user_id, 'i360_wallet_balance', $saldo - $total_cobrar);
        $cobro_realizado = true;
    }

    $payload = ['addon' => $price_id, 'qty' => $qty];
    $endpoint = "partner/workspace/{$ws_id}/{$action}-addon";
    $method = ($action === 'remove') ? 'DELETE' : 'POST';
    $data = i360_api_request($endpoint, $method, $payload);
    
    if (is_wp_error($data) || (isset($data['status']) && $data['status'] === 'error')) {
        if ($cobro_realizado) update_user_meta($user_id, 'i360_wallet_balance', (float) get_user_meta($user_id, 'i360_wallet_balance', true) + $total_cobrar);
        
        $msg = is_wp_error($data) ? $data->get_error_message() : ($data['message'] ?? 'Error UChat');
        if (strpos(strtolower($msg), 'point') !== false) {
            $msg = $textos['points_error_msg']; 
        }
        wp_send_json_error(['message' => $msg]); return;
    }
    wp_send_json_success($data);
});

// G. Change Plan
add_action('wp_ajax_i360_change_plan', function() {
    check_ajax_referer('i360_nonce', 'security');
    $user_id = get_current_user_id();
    $textos = i360_get_dictionary(); 

    $id = sanitize_text_field($_POST['id']);
    $plan_id = sanitize_text_field($_POST['plan']); 
    
    $planes_api = i360_api_request('partner/plans', 'GET');
    $costo = 20; 

    if (!is_wp_error($planes_api) && isset($planes_api['data'])) {
        foreach ($planes_api['data'] as $p) {
            if ($p['id'] === $plan_id) {
                $costo = i360_calcular_precio_plan($p);
                break;
            }
        }
    }

    if ($costo > 0) {
        $saldo = (float) get_user_meta($user_id, 'i360_wallet_balance', true);
        if ($saldo < $costo) {
            $error_msg = $textos['insufficient_balance'] . ". Requerido: $" . $costo . " USD.";
            wp_send_json_error(['message' => $error_msg]); return;
        }
        update_user_meta($user_id, 'i360_wallet_balance', $saldo - $costo);
    }

    $payload = ['plan' => $plan_id, 'auto_renew' => 'yes'];
    $data = i360_api_request("partner/workspace/{$id}/change-plan", 'POST', $payload);
    
    if (is_wp_error($data) || (isset($data['status']) && $data['status'] === 'error')) {
        if ($costo > 0) update_user_meta($user_id, 'i360_wallet_balance', (float) get_user_meta($user_id, 'i360_wallet_balance', true) + $costo);
        wp_send_json_error(['message' => $data['message'] ?? 'Error']); return;
    }
    wp_send_json_success($data);
});

// H. Crear Workspace 
add_action('wp_ajax_i360_create_workspace', function() { 
    check_ajax_referer('i360_nonce', 'security'); 
    $payload = json_decode(stripslashes($_POST['payload']), true); 
    $data = i360_api_request('partner/workspace/create', 'POST', $payload); 
    if (is_wp_error($data) || (isset($data['status']) && $data['status'] === 'error')) { 
        wp_send_json_error(['message' => $data['message'] ?? 'Error']); 
        return; 
    } 
    wp_send_json_success($data); 
});

// Otros Endpoints
add_action('wp_ajax_i360_delete_workspace', function() { check_ajax_referer('i360_nonce', 'security'); $id = sanitize_text_field($_POST['id']); $data = i360_api_request('partner/workspace/' . $id, 'DELETE'); if (is_wp_error($data)) wp_send_json_error(['message' => $data->get_error_message()]); wp_send_json_success($data); });
add_action('wp_ajax_i360_change_status', function() { check_ajax_referer('i360_nonce', 'security'); $id = sanitize_text_field($_POST['id']); $action = sanitize_text_field($_POST['status_action']); $data = i360_api_request("partner/workspace/{$id}/{$action}", 'POST'); if (is_wp_error($data)) wp_send_json_error(['message' => $data->get_error_message()]); wp_send_json_success($data); });
add_action('wp_ajax_i360_manage_points', function() { check_ajax_referer('i360_nonce', 'security'); $ws_id = sanitize_text_field($_POST['workspace_id']); $action = sanitize_text_field($_POST['type']); $points = floatval($_POST['points']); $note = isset($_POST['note']) ? sanitize_text_field($_POST['note']) : ''; $endpoint_action = ($action === 'topup') ? 'topup-points' : 'deduct-points'; $endpoint = "partner/workspace/{$ws_id}/{$endpoint_action}"; $method = ($action === 'deduct') ? 'DELETE' : 'POST'; $payload = ['points' => $points, 'note' => $note]; $data = i360_api_request($endpoint, $method, $payload); if (is_wp_error($data)) wp_send_json_error(['message' => $data->get_error_message()]); if (isset($data['status']) && $data['status'] === 'error') wp_send_json_error(['message' => $data['message']]); wp_send_json_success($data); });
add_action('wp_ajax_i360_get_clients', function() { check_ajax_referer('i360_nonce', 'security'); $page = isset($_POST['page']) ? intval($_POST['page']) : 1; $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : ''; $endpoint = 'partner/clients?page=' . $page . (!empty($search) ? '&search=' . urlencode($search) : ''); $data = i360_api_request($endpoint, 'GET'); if (is_wp_error($data)) wp_send_json_error(['message' => $data->get_error_message()]); wp_send_json_success($data); });


// ==========================================
// INTERFAZ EN PERFIL DE WORDPRESS (NUEVO)
// ==========================================

function i360_campos_precios_usuario($user) {
    if (!current_user_can('manage_options')) return;
    ?>
    <hr>
    <h3>Precios Personalizados Botky (Por Usuario)</h3>
    <p><em>Si dejas estas casillas vacías, el sistema cobrará los precios globales por defecto que se muestran como ejemplo en cada campo.</em></p>
    
    <h4>1. Planes Combo & Base Custom</h4>
    <table class="form-table">
        <tr>
            <th><label for="i360_precio_business">Plan Business / BASE Custom</label></th>
            <td><input type="number" step="0.01" name="i360_precio_business" id="i360_precio_business" value="<?php echo esc_attr(get_user_meta($user->ID, 'i360_precio_business', true)); ?>" class="regular-text" placeholder="Global: $20">
            <p class="description">Este es el plan inicial y la base sobre la que se calculan los planes Custom.</p></td>
        </tr>
        <tr>
            <th><label for="i360_precio_business_lite">Combo: Business Lite</label></th>
            <td><input type="number" step="0.01" name="i360_precio_business_lite" id="i360_precio_business_lite" value="<?php echo esc_attr(get_user_meta($user->ID, 'i360_precio_business_lite', true)); ?>" class="regular-text" placeholder="Global: $45"></td>
        </tr>
        <tr>
            <th><label for="i360_precio_business_plus">Combo: Business Plus</label></th>
            <td><input type="number" step="0.01" name="i360_precio_business_plus" id="i360_precio_business_plus" value="<?php echo esc_attr(get_user_meta($user->ID, 'i360_precio_business_plus', true)); ?>" class="regular-text" placeholder="Global: $99"></td>
        </tr>
        <tr>
            <th><label for="i360_precio_business_large">Combo: Business Large</label></th>
            <td><input type="number" step="0.01" name="i360_precio_business_large" id="i360_precio_business_large" value="<?php echo esc_attr(get_user_meta($user->ID, 'i360_precio_business_large', true)); ?>" class="regular-text" placeholder="Global: $149"></td>
        </tr>
        <tr>
            <th><label for="i360_precio_business_yearly">Combo: Business Yearly</label></th>
            <td><input type="number" step="0.01" name="i360_precio_business_yearly" id="i360_precio_business_yearly" value="<?php echo esc_attr(get_user_meta($user->ID, 'i360_precio_business_yearly', true)); ?>" class="regular-text" placeholder="Global: $990"></td>
        </tr>
    </table>

    <hr>
    <h4>2. Complementos (Addons & Extras)</h4>
    <table class="form-table">
        <tr>
            <th><label for="i360_addon_bot">Bot / Bot Extra</label></th>
            <td><input type="number" step="0.01" name="i360_addon_bot" id="i360_addon_bot" value="<?php echo esc_attr(get_user_meta($user->ID, 'i360_addon_bot', true)); ?>" class="regular-text" placeholder="Global: $10"></td>
        </tr>
        <tr>
            <th><label for="i360_addon_member">Member / Miembro Extra</label></th>
            <td><input type="number" step="0.01" name="i360_addon_member" id="i360_addon_member" value="<?php echo esc_attr(get_user_meta($user->ID, 'i360_addon_member', true)); ?>" class="regular-text" placeholder="Global: $10"></td>
        </tr>
        <tr>
            <th><label for="i360_addon_bot_user">1k Users / Usuarios Extra</label></th>
            <td><input type="number" step="0.01" name="i360_addon_bot_user" id="i360_addon_bot_user" value="<?php echo esc_attr(get_user_meta($user->ID, 'i360_addon_bot_user', true)); ?>" class="regular-text" placeholder="Global: $10"></td>
        </tr>
        <tr>
            <th><label for="i360_addon_bot_user_large">10k Users</label></th>
            <td><input type="number" step="0.01" name="i360_addon_bot_user_large" id="i360_addon_bot_user_large" value="<?php echo esc_attr(get_user_meta($user->ID, 'i360_addon_bot_user_large', true)); ?>" class="regular-text" placeholder="Global: $60"></td>
        </tr>
        <tr>
            <th><label for="i360_addon_lists">Tickets / Lists</label></th>
            <td><input type="number" step="0.01" name="i360_addon_lists" id="i360_addon_lists" value="<?php echo esc_attr(get_user_meta($user->ID, 'i360_addon_lists', true)); ?>" class="regular-text" placeholder="Global: $30"></td>
        </tr>
        <tr>
            <th><label for="i360_addon_inbound_webhook">Inbound Webhook</label></th>
            <td><input type="number" step="0.01" name="i360_addon_inbound_webhook" id="i360_addon_inbound_webhook" value="<?php echo esc_attr(get_user_meta($user->ID, 'i360_addon_inbound_webhook', true)); ?>" class="regular-text" placeholder="Global: $40"></td>
        </tr>
        <tr>
            <th><label for="i360_addon_timeout">Timeout</label></th>
            <td><input type="number" step="0.01" name="i360_addon_timeout" id="i360_addon_timeout" value="<?php echo esc_attr(get_user_meta($user->ID, 'i360_addon_timeout', true)); ?>" class="regular-text" placeholder="Global: $20"></td>
        </tr>
        <tr>
            <th><label for="i360_addon_custom_domain">Custom Domain</label></th>
            <td><input type="number" step="0.01" name="i360_addon_custom_domain" id="i360_addon_custom_domain" value="<?php echo esc_attr(get_user_meta($user->ID, 'i360_addon_custom_domain', true)); ?>" class="regular-text" placeholder="Global: $15"></td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'i360_campos_precios_usuario');
add_action('edit_user_profile', 'i360_campos_precios_usuario');

// Guardar los campos
function i360_guardar_precios_usuario($user_id) {
    if (!current_user_can('edit_user', $user_id)) return false;

    $campos_a_guardar = [
        'i360_precio_business',
        'i360_precio_business_lite',
        'i360_precio_business_plus',
        'i360_precio_business_large',
        'i360_precio_business_yearly',
        'i360_addon_bot',
        'i360_addon_member',
        'i360_addon_bot_user',
        'i360_addon_bot_user_large',
        'i360_addon_lists',
        'i360_addon_inbound_webhook',
        'i360_addon_timeout',
        'i360_addon_custom_domain'
    ];

    foreach ($campos_a_guardar as $campo) {
        if (isset($_POST[$campo])) {
            update_user_meta($user_id, $campo, sanitize_text_field($_POST[$campo]));
        }
    }
}
add_action('personal_options_update', 'i360_guardar_precios_usuario');
add_action('edit_user_profile_update', 'i360_guardar_precios_usuario');