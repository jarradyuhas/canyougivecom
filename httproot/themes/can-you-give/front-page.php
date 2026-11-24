<?php get_header(); ?>

<?php
$colors = tmt_get_theme_data()['colors'] ?? array();
$style  = '';
foreach ( $colors as $property => $value ) {
    $style .= '--' . esc_attr( $property ) . ':' . esc_attr( $value ) . ';';
}
?>
<style>:root { <?php echo $style; ?> }</style>
<div id="tmt-app" style="<?php echo $style; ?>"></div>

<?php get_footer(); ?>
