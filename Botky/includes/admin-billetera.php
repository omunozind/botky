<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================================
// GESTION DE BILLETERA (ADMINISTRADOR)
// ==========================================

// 1. Agregar columna "Saldo" en la lista de Usuarios
add_filter('manage_users_columns', function($columns) {
    $columns['i360_wallet'] = 'Saldo Billetera (USD)';
    return $columns;
});

// 2. Mostrar el valor en la columna
add_filter('manage_users_custom_column', function($value, $column_name, $user_id) {
    if ($column_name == 'i360_wallet') {
        $saldo = (float) get_user_meta($user_id, 'i360_wallet_balance', true);
        
        // Colores: Verde si tiene dinero, Gris si es 0, Rojo si es negativo
        $color = ($saldo > 0) ? '#16a34a' : '#94a3b8'; 
        if ($saldo < 0) $color = '#dc2626';

        return "<b style='color:{$color};'>$ " . number_format($saldo, 2) . "</b>";
    }
    return $value;
}, 10, 3);

// 3. Campo para EDITAR saldo en el Perfil del Usuario
add_action('show_user_profile', 'i360_render_wallet_admin_field');
add_action('edit_user_profile', 'i360_render_wallet_admin_field');

function i360_render_wallet_admin_field($user) {
    if (!current_user_can('manage_options')) return; // Solo admins
    
    $saldo = (float) get_user_meta($user->ID, 'i360_wallet_balance', true);
    ?>
    <br>
    <h3>Gesti&oacute;n de Billetera (Botky)</h3>
    <table class="form-table">
        <tr>
            <th><label for="i360_wallet_balance">Saldo Disponible ($ USD)</label></th>
            <td>
                <input type="number" step="0.01" name="i360_wallet_balance" id="i360_wallet_balance" value="<?php echo esc_attr($saldo); ?>" class="regular-text">
                <p class="description">
                    Ingresa el monto manualmente para corregir saldos o agregar bonos.<br>
                    <strong style="color: #d63638;">IMPORTANTE: Este valor sobrescribir&aacute; el saldo actual del usuario.</strong>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

// 4. Guardar el cambio manual del Admin
add_action('personal_options_update', 'i360_save_wallet_admin_field');
add_action('edit_user_profile_update', 'i360_save_wallet_admin_field');

function i360_save_wallet_admin_field($user_id) {
    if (!current_user_can('manage_options')) return;
    
    if (isset($_POST['i360_wallet_balance'])) {
        update_user_meta($user_id, 'i360_wallet_balance', floatval($_POST['i360_wallet_balance']));
    }
}