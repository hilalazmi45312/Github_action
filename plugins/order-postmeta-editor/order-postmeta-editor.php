<?php
/**
 * Plugin Name: Order Postmeta Manager
 * Description: Search WooCommerce order by ID, list postmeta, edit or add meta safely.
 * Version: 1.0
 * Author: Ilias Cloone
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function () {
    add_submenu_page(
        'woocommerce',
        'Order Postmeta Manager',
        'Order Postmeta Manager',
        'manage_woocommerce',
        'order-postmeta-manager',
        'opm_render_page'
    );
});

function opm_render_page() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) return;

    $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;

    // Handle update
    if ( isset( $_POST['opm_update'] ) && check_admin_referer( 'opm_action', 'opm_nonce' ) ) {
        $oid = absint( $_POST['order_id'] );
        foreach ( (array) $_POST['meta'] as $key => $value ) {
            update_post_meta( $oid, sanitize_text_field( $key ), wp_unslash( $value ) );
        }
        echo '<div class="notice notice-success"><p>Meta updated.</p></div>';
        $order_id = $oid;
    }

    // Handle add
    if ( isset( $_POST['opm_add'] ) && check_admin_referer( 'opm_action', 'opm_nonce' ) ) {
        $oid = absint( $_POST['order_id'] );
        $key = sanitize_text_field( $_POST['new_meta_key'] );
        $val = wp_unslash( $_POST['new_meta_value'] );

        if ( $key ) {
            update_post_meta( $oid, $key, $val );
            echo '<div class="notice notice-success"><p>New meta added.</p></div>';
        }
        $order_id = $oid;
    }
    ?>

    <div class="wrap">
        <h1>Order Postmeta Manager</h1>

        <!-- SEARCH -->
        <form method="get">
            <input type="hidden" name="page" value="order-postmeta-manager">
            <table class="form-table">
                <tr>
                    <th>Order ID</th>
                    <td>
                        <input type="number" name="order_id" value="<?php echo esc_attr( $order_id ); ?>" required>
                        <button class="button button-primary">Search</button>
                    </td>
                </tr>
            </table>
        </form>

        <?php if ( $order_id ) : ?>
            <?php $meta = get_post_meta( $order_id ); ?>

            <!-- EDIT TABLE -->
            <h2>Existing Postmeta</h2>
            <form method="post">
                <?php wp_nonce_field( 'opm_action', 'opm_nonce' ); ?>
                <input type="hidden" name="order_id" value="<?php echo esc_attr( $order_id ); ?>">

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Meta Key</th>
                            <th>Meta Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $meta as $key => $values ) : ?>
                            <tr>
                                <td><code><?php echo esc_html( $key ); ?></code></td>
                                <td>
                                    <textarea name="meta[<?php echo esc_attr( $key ); ?>]" rows="2" style="width:100%;"><?php
                                        echo esc_textarea( maybe_unserialize( $values[0] ) );
                                    ?></textarea>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p>
                    <button type="submit" name="opm_update" class="button button-primary">
                        Update Existing Meta
                    </button>
                </p>
            </form>

            <!-- ADD NEW -->
            <h2>Add New Meta</h2>
            <form method="post">
                <?php wp_nonce_field( 'opm_action', 'opm_nonce' ); ?>
                <input type="hidden" name="order_id" value="<?php echo esc_attr( $order_id ); ?>">

                <table class="form-table">
                    <tr>
                        <th>Meta Key</th>
                        <td><input type="text" name="new_meta_key" required></td>
                    </tr>
                    <tr>
                        <th>Meta Value</th>
                        <td><textarea name="new_meta_value" rows="3" style="width:100%;"></textarea></td>
                    </tr>
                </table>

                <p>
                    <button type="submit" name="opm_add" class="button">
                        Add Meta
                    </button>
                </p>
            </form>

        <?php elseif ( $order_id ) : ?>
            <div class="notice notice-error"><p>Invalid Order ID.</p></div>
        <?php endif; ?>
    </div>
<?php
}
