<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function tmt_admin_menu() {
    add_options_page(
        __( 'Can You Give Item Capacity', 'tmt' ),
        __( 'Can You Give Item Capacity', 'tmt' ),
        'manage_options',
        'tmt-item-capacity',
        'tmt_render_settings_page'
    );
}
add_action( 'admin_menu', 'tmt_admin_menu' );

function tmt_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Can You Give Item Capacity', 'tmt' ); ?></h1>
        <p><?php esc_html_e( 'Controls how many extra rows can be added to structured sections beyond the original Can You Give design count. Items only appear if they contain content — increasing these numbers never adds empty slots.', 'tmt' ); ?></p>
        <form method="post" action="options.php">
            <?php settings_fields( 'tmt_capacity_group' ); ?>
            <?php do_settings_sections( 'tmt-item-capacity' ); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function tmt_register_capacity_settings() {
    $settings = array(
        'tmt_extra_grid_rows'       => array( 'label' => 'Additional Grid Rows (3-column sections)', 'default' => 1 ),
        'tmt_extra_column_rows'     => array( 'label' => 'Additional Single-Column Rows (2-column sections)', 'default' => 3 ),
        'tmt_extra_carousel_slides' => array( 'label' => 'Additional Carousel Slides', 'default' => 3 ),
        'tmt_extra_gallery_items'   => array( 'label' => 'Additional Gallery / Linear Items', 'default' => 3 ),
    );

    add_settings_section( 'tmt_capacity_section', '', '__return_false', 'tmt-item-capacity' );

    foreach ( $settings as $id => $config ) {
        register_setting( 'tmt_capacity_group', $id, array(
            'sanitize_callback' => 'tmt_sanitize_capacity',
            'default'           => $config['default'],
        ) );
        add_settings_field( $id, __( $config['label'], 'tmt' ), 'tmt_render_capacity_field', 'tmt-item-capacity', 'tmt_capacity_section', array( 'id' => $id ) );
    }
}
add_action( 'admin_init', 'tmt_register_capacity_settings' );

function tmt_render_capacity_field( $args ) {
    $value = (int) get_option( $args['id'], 1 );
    printf( '<input type="number" id="%1$s" name="%1$s" value="%2$d" min="0" max="6" />', esc_attr( $args['id'] ), $value );
}

function tmt_sanitize_capacity( $value ) {
    return min( max( absint( $value ), 0 ), 6 );
}
