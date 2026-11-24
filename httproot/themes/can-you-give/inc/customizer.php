<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function tmt_register_media_image( $wp_customize, $id, $label, $default_url, $section ) {
    $wp_customize->add_setting( $id, array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, $id, array(
        'label'     => __( $label, 'tmt' ),
        'section'   => $section,
        'mime_type' => 'image',
    ) ) );

    $url_id = $id . '_url';
    if ( str_ends_with( $id, '_image' ) ) {
        $url_id = str_replace( '_image', '_image_url', $id );
    } elseif ( $id === 'tmt_hero_poster' ) {
        $url_id = 'tmt_hero_poster_url';
    } elseif ( $id === 'tmt_logo_id' ) {
        $url_id = 'tmt_logo_url';
    }

    $wp_customize->add_setting( $url_id, array(
        'default'           => $default_url,
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ) );
}

function tmt_register_media_video( $wp_customize, $id, $label, $default_url, $section ) {
    $wp_customize->add_setting( $id, array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, $id, array(
        'label'     => __( $label, 'tmt' ),
        'section'   => $section,
        'mime_type' => 'video',
    ) ) );

    $url_id = $id . '_url';
    $wp_customize->add_setting( $url_id, array(
        'default'           => $default_url,
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ) );
}

function tmt_add_text_setting( $wp_customize, $id, $label, $default, $section, $type = 'text' ) {
    $wp_customize->add_setting( $id, array(
        'default'           => $default,
        'sanitize_callback' => $type === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( $id, array(
        'label'   => __( $label, 'tmt' ),
        'section' => $section,
        'type'    => $type,
    ) );
}

function tmt_add_number_setting( $wp_customize, $id, $label, $default, $section, $input_attrs = array() ) {
    $wp_customize->add_setting( $id, array(
        'default'           => $default,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( $id, array(
        'label'       => __( $label, 'tmt' ),
        'section'     => $section,
        'type'        => 'number',
        'input_attrs' => $input_attrs,
    ) );
}

function tmt_add_href_setting( $wp_customize, $id, $label, $default, $section ) {
    $wp_customize->add_setting( $id, array(
        'default'           => $default,
        'sanitize_callback' => 'tmt_sanitize_cta_href',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( $id, array(
        'label'       => __( $label, 'tmt' ),
        'description' => __( 'Use #section for on-page links, or a full web address (https://, mailto:, tel:).', 'tmt' ),
        'section'     => $section,
        'type'        => 'url',
    ) );
}

function tmt_register_color_settings( $wp_customize ) {
    $labels = array(
        'tmt_color_ink'    => 'Ink',
        'tmt_color_canvas' => 'Canvas',
        'tmt_color_sand'   => 'Sand',
        'tmt_color_clay'   => 'Clay',
        'tmt_color_give'   => 'Give',
        'tmt_color_lilac'  => 'Lilac',
        'tmt_color_rule'   => 'Rule',
    );

    foreach ( tmt_get_color_hex_defaults() as $id => $default ) {
        $wp_customize->add_setting( $id, array(
            'default'           => $default,
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, array(
            'label'   => __( $labels[ $id ], 'tmt' ),
            'section' => 'tmt_section_colors',
        ) ) );
    }
}

function tmt_register_global_settings( $wp_customize ) {
    tmt_add_text_setting( $wp_customize, 'tmt_site_name', 'Site Name', 'Can You Give', 'tmt_section_global' );

    $wp_customize->add_setting( 'tmt_logo_id', array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'tmt_logo_id', array(
        'label'     => __( 'Logo', 'tmt' ),
        'section'   => 'tmt_section_global',
        'mime_type' => 'image',
    ) ) );
    $wp_customize->add_setting( 'tmt_logo_url', array(
        'default'           => tmt_asset_uri( 'logo-canyougive.png' ),
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ) );

    tmt_add_number_setting(
        $wp_customize,
        'tmt_logo_height',
        'Logo Height (pixels)',
        76,
        'tmt_section_global',
        array(
            'min'  => 32,
            'max'  => 160,
            'step' => 1,
        )
    );

    tmt_add_text_setting( $wp_customize, 'tmt_header_tagline', 'Header Tagline', 'Shop. Give. Repeat.', 'tmt_section_global' );
    tmt_add_href_setting( $wp_customize, 'tmt_header_home_href', 'Home Link', '#top', 'tmt_section_global' );
    tmt_add_text_setting( $wp_customize, 'tmt_header_cta_text', 'Header Button Text', 'Browse Retailers', 'tmt_section_global' );
    tmt_add_href_setting( $wp_customize, 'tmt_header_cta_href', 'Header Button Link', '#retailers', 'tmt_section_global' );

    $nav_defaults = array(
        1 => array( 'label' => 'How it works', 'href' => '#how-it-works' ),
        2 => array( 'label' => 'Retailers', 'href' => '#retailers' ),
        3 => array( 'label' => 'Causes', 'href' => '#causes' ),
        4 => array( 'label' => 'FAQ', 'href' => '#faq' ),
        5 => array( 'label' => 'Apply', 'href' => '#apply' ),
    );
    foreach ( $nav_defaults as $i => $nav ) {
        tmt_add_text_setting( $wp_customize, "tmt_nav_{$i}_label", "Nav {$i} Label", $nav['label'], 'tmt_section_global' );
        tmt_add_href_setting( $wp_customize, "tmt_nav_{$i}_href", "Nav {$i} Link", $nav['href'], 'tmt_section_global' );
    }
}

function tmt_register_hero_settings( $wp_customize ) {
    tmt_add_text_setting( $wp_customize, 'tmt_hero_badge_text', 'Badge Text', 'Zero overhead giving', 'tmt_section_hero' );
    tmt_add_text_setting( $wp_customize, 'tmt_hero_heading', 'Heading', 'Help others, one shopping trip at a time!', 'tmt_section_hero' );
    tmt_add_text_setting( $wp_customize, 'tmt_hero_subheading', 'Subheading', 'Shopping through our portal directs a portion of your purchase to a charity, event, or foundation of your choosing.', 'tmt_section_hero', 'textarea' );

    tmt_register_media_video( $wp_customize, 'tmt_hero_video', 'Hero Background Video', tmt_asset_uri( 'hero-loop.mp4' ), 'tmt_section_hero' );

    $wp_customize->add_setting( 'tmt_hero_poster', array( 'default' => 0, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ) );
    $wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'tmt_hero_poster', array(
        'label' => __( 'Hero Video Poster', 'tmt' ), 'section' => 'tmt_section_hero', 'mime_type' => 'image',
    ) ) );
    $wp_customize->add_setting( 'tmt_hero_poster_url', array(
        'default' => tmt_asset_uri( 'hero-shopping.jpg' ), 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh',
    ) );

    tmt_add_text_setting( $wp_customize, 'tmt_hero_video_aria', 'Video Aria Label', 'Shoppers walking together with bags on a sunlit street', 'tmt_section_hero' );
    tmt_add_text_setting( $wp_customize, 'tmt_hero_cta_primary_text', 'Primary Button Text', 'Browse Retailers', 'tmt_section_hero' );
    tmt_add_href_setting( $wp_customize, 'tmt_hero_cta_primary_href', 'Primary Button Link', '#retailers', 'tmt_section_hero' );
    tmt_add_text_setting( $wp_customize, 'tmt_hero_cta_secondary_text', 'Secondary Button Text', 'Find a Cause', 'tmt_section_hero' );
    tmt_add_href_setting( $wp_customize, 'tmt_hero_cta_secondary_href', 'Secondary Button Link', '#causes', 'tmt_section_hero' );

    for ( $i = 1; $i <= 3; $i++ ) {
        $stat_defaults = array(
            1 => array( 'value' => '100%', 'label' => 'of our share passed on' ),
            2 => array( 'value' => '180+', 'label' => 'partner retailers' ),
            3 => array( 'value' => '$0', 'label' => 'cost to you, ever' ),
        );
        tmt_add_text_setting( $wp_customize, "tmt_hero_stat_{$i}_value", "Stat {$i} Value", $stat_defaults[ $i ]['value'], 'tmt_section_hero' );
        tmt_add_text_setting( $wp_customize, "tmt_hero_stat_{$i}_label", "Stat {$i} Label", $stat_defaults[ $i ]['label'], 'tmt_section_hero' );
    }
}

function tmt_register_how_it_works_settings( $wp_customize ) {
    tmt_add_text_setting( $wp_customize, 'tmt_hiw_label', 'Section Label', 'How it works', 'tmt_section_hiw' );
    tmt_add_text_setting( $wp_customize, 'tmt_hiw_heading', 'Heading', 'A transparent process, built by the community it serves.', 'tmt_section_hiw' );

    $step_defaults = array(
        1 => array( 'title' => 'Pick your cause', 'body' => 'Choose any charity, community chest, or local event listed in our directory. Switch it whenever you like.' ),
        2 => array( 'title' => 'Shop as usual', 'body' => 'Head to a partner retailer through our catalog. Same prices, same checkout, no extra step.' ),
        3 => array( 'title' => 'We pass it on', 'body' => 'The retailer pays us a commission and we direct our share to your chosen cause. Nothing added to your total.' ),
    );
    for ( $i = 1; $i <= 3; $i++ ) {
        tmt_add_text_setting( $wp_customize, "tmt_hiw_step_{$i}_title", "Step {$i} Title", $step_defaults[ $i ]['title'], 'tmt_section_hiw' );
        tmt_add_text_setting( $wp_customize, "tmt_hiw_step_{$i}_body", "Step {$i} Body", $step_defaults[ $i ]['body'], 'tmt_section_hiw', 'textarea' );
    }

    $wp_customize->add_setting( 'tmt_hiw_community_image', array( 'default' => 0, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ) );
    $wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'tmt_hiw_community_image', array(
        'label' => __( 'Community Image', 'tmt' ), 'section' => 'tmt_section_hiw', 'mime_type' => 'image',
    ) ) );
    $wp_customize->add_setting( 'tmt_hiw_community_image_url', array(
        'default' => tmt_asset_uri( 'community-volunteers.jpg' ), 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh',
    ) );
    tmt_add_text_setting( $wp_customize, 'tmt_hiw_community_image_alt', 'Community Image Alt', 'Volunteers packing donation boxes in a community hall', 'tmt_section_hiw' );
    tmt_add_text_setting( $wp_customize, 'tmt_hiw_community_heading', 'Community Card Heading', 'Every dollar routed stays in the community that earned it.', 'tmt_section_hiw' );
    tmt_add_text_setting( $wp_customize, 'tmt_hiw_community_body', 'Community Card Body', 'No administrative cut, no membership, no paperwork for shoppers.', 'tmt_section_hiw', 'textarea' );
    tmt_add_text_setting( $wp_customize, 'tmt_hiw_overhead_value', 'Overhead Value', '0%', 'tmt_section_hiw' );
    tmt_add_text_setting( $wp_customize, 'tmt_hiw_overhead_body', 'Overhead Body', 'Overhead taken from your chosen cause. Volunteer-run, community-governed, reported quarterly in the open.', 'tmt_section_hiw', 'textarea' );
}

function tmt_register_faq_settings( $wp_customize ) {
    tmt_add_text_setting( $wp_customize, 'tmt_faq_label', 'Section Label', 'FAQ', 'tmt_section_faq' );
    tmt_add_text_setting( $wp_customize, 'tmt_faq_heading', 'Heading', 'Questions, answered plainly.', 'tmt_section_faq' );
    tmt_add_text_setting( $wp_customize, 'tmt_faq_contact_prefix', 'Contact Prefix', 'Still unsure about something? Write to', 'tmt_section_faq' );
    tmt_add_text_setting( $wp_customize, 'tmt_faq_contact_email', 'Contact Email Text', 'info@canyougive.org', 'tmt_section_faq' );
    tmt_add_href_setting( $wp_customize, 'tmt_faq_contact_email_href', 'Contact Email Link', 'mailto:info@canyougive.org', 'tmt_section_faq' );
    tmt_add_text_setting( $wp_customize, 'tmt_faq_contact_suffix', 'Contact Suffix', 'and a real person replies.', 'tmt_section_faq' );

    $faq_defaults = tmt_get_faq_defaults();
    $faq_count    = 6;
    $extra_rows   = (int) get_option( 'tmt_extra_column_rows', 3 );
    $max_faqs     = $faq_count + ( $extra_rows * 2 );

    for ( $i = 1; $i <= $max_faqs; $i++ ) {
        $defaults = isset( $faq_defaults[ $i ] ) ? $faq_defaults[ $i ] : array( 'q' => '', 'a' => '' );
        tmt_add_text_setting( $wp_customize, "tmt_faq_{$i}_q", "FAQ {$i} Question", $defaults['q'], 'tmt_section_faq' );
        tmt_add_text_setting( $wp_customize, "tmt_faq_{$i}_a", "FAQ {$i} Answer", $defaults['a'], 'tmt_section_faq', 'textarea' );
    }
}

function tmt_register_retailer_settings( $wp_customize ) {
    tmt_add_text_setting( $wp_customize, 'tmt_retailers_label', 'Section Label', 'Featured retailers', 'tmt_section_retailers' );
    tmt_add_text_setting( $wp_customize, 'tmt_retailers_heading', 'Heading', 'The catalog. Shop it like any other store.', 'tmt_section_retailers' );
    tmt_add_text_setting( $wp_customize, 'tmt_retailers_intro', 'Intro', 'Each link is an affiliate link. Your price stays the same, and the commission goes to the cause that retailer supports.', 'tmt_section_retailers', 'textarea' );
    tmt_add_text_setting( $wp_customize, 'tmt_retailers_search_placeholder', 'Search Placeholder', 'Search by store, category, or cause', 'tmt_section_retailers' );
    tmt_add_text_setting( $wp_customize, 'tmt_retailers_shop_now_text', 'Shop Button Text', 'Shop Now', 'tmt_section_retailers' );
    tmt_add_text_setting( $wp_customize, 'tmt_retailers_empty_message', 'Empty Message', 'No retailers match that search.', 'tmt_section_retailers' );
    tmt_add_text_setting( $wp_customize, 'tmt_retailers_clear_filters_text', 'Clear Filters Text', 'Clear filters', 'tmt_section_retailers' );
    tmt_add_text_setting( $wp_customize, 'tmt_retailers_did_you_mean_prefix', 'Did You Mean Prefix', 'Did you mean:', 'tmt_section_retailers' );

    $defaults    = tmt_get_retailer_defaults();
    $count       = 8;
    $extra_rows  = (int) get_option( 'tmt_extra_grid_rows', 1 );
    $max_items   = $count + ( $extra_rows * 3 );

    for ( $i = 1; $i <= $max_items; $i++ ) {
        $d = isset( $defaults[ $i ] ) ? $defaults[ $i ] : array( 'name' => '', 'category' => '', 'logo_file' => '', 'give' => '', 'cause' => '', 'blurb' => '', 'shop_href' => '#retailers' );
        tmt_add_text_setting( $wp_customize, "tmt_retailer_{$i}_name", "Retailer {$i} Name", $d['name'], 'tmt_section_retailers' );
        tmt_add_text_setting( $wp_customize, "tmt_retailer_{$i}_category", "Retailer {$i} Category", $d['category'], 'tmt_section_retailers' );
        tmt_add_text_setting( $wp_customize, "tmt_retailer_{$i}_give", "Retailer {$i} Give", $d['give'], 'tmt_section_retailers' );
        tmt_add_text_setting( $wp_customize, "tmt_retailer_{$i}_cause", "Retailer {$i} Cause", $d['cause'], 'tmt_section_retailers' );
        tmt_add_text_setting( $wp_customize, "tmt_retailer_{$i}_blurb", "Retailer {$i} Blurb", $d['blurb'], 'tmt_section_retailers', 'textarea' );
        $logo_default = ! empty( $d['logo_file'] ) ? tmt_asset_uri( $d['logo_file'] ) : '';
        $wp_customize->add_setting( "tmt_retailer_{$i}_logo", array( 'default' => 0, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ) );
        $wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, "tmt_retailer_{$i}_logo", array(
            'label' => sprintf( __( 'Retailer %d Logo', 'tmt' ), $i ), 'section' => 'tmt_section_retailers', 'mime_type' => 'image',
        ) ) );
        $wp_customize->add_setting( "tmt_retailer_{$i}_logo_url", array(
            'default' => $logo_default, 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh',
        ) );
        tmt_add_href_setting( $wp_customize, "tmt_retailer_{$i}_shop_href", "Retailer {$i} Shop Button Link", $d['shop_href'], 'tmt_section_retailers' );
    }
}

function tmt_register_cause_settings( $wp_customize ) {
    tmt_add_text_setting( $wp_customize, 'tmt_causes_label', 'Section Label', 'Give directly', 'tmt_section_causes' );
    tmt_add_text_setting( $wp_customize, 'tmt_causes_heading', 'Heading', 'Feel like giving directly?', 'tmt_section_causes' );
    tmt_add_text_setting( $wp_customize, 'tmt_causes_intro', 'Intro', 'Search for your favorite cause or event to donate to directly, and 100% of your donation goes straight to them.', 'tmt_section_causes', 'textarea' );
    tmt_add_text_setting( $wp_customize, 'tmt_causes_search_placeholder', 'Search Placeholder', 'Search by name, place, or type', 'tmt_section_causes' );
    tmt_add_text_setting( $wp_customize, 'tmt_causes_donate_text', 'Donate Button Text (all causes)', 'Donate', 'tmt_section_causes' );
    tmt_add_text_setting( $wp_customize, 'tmt_causes_donate_note_template', 'Donate Note Template', '100% goes to {name}', 'tmt_section_causes' );
    tmt_add_text_setting( $wp_customize, 'tmt_causes_empty_message', 'Empty Message', 'No causes match that search.', 'tmt_section_causes' );
    tmt_add_text_setting( $wp_customize, 'tmt_causes_clear_filters_text', 'Clear Filters Text', 'Clear filters', 'tmt_section_causes' );
    tmt_add_text_setting( $wp_customize, 'tmt_causes_did_you_mean_prefix', 'Did You Mean Prefix', 'Did you mean:', 'tmt_section_causes' );

    $defaults   = tmt_get_cause_defaults();
    $count      = 4;
    $extra_rows = (int) get_option( 'tmt_extra_column_rows', 3 );
    $max_items  = $count + ( $extra_rows * 2 );

    for ( $i = 1; $i <= $max_items; $i++ ) {
        $d = isset( $defaults[ $i ] ) ? $defaults[ $i ] : array( 'name' => '', 'type' => '', 'location' => '', 'image_file' => '', 'blurb' => '', 'donate_href' => '' );
        tmt_add_text_setting( $wp_customize, "tmt_cause_{$i}_name", "Cause {$i} Name", $d['name'], 'tmt_section_causes' );
        tmt_add_text_setting( $wp_customize, "tmt_cause_{$i}_type", "Cause {$i} Type", $d['type'], 'tmt_section_causes' );
        tmt_add_text_setting( $wp_customize, "tmt_cause_{$i}_location", "Cause {$i} Location", $d['location'], 'tmt_section_causes' );
        tmt_add_text_setting( $wp_customize, "tmt_cause_{$i}_blurb", "Cause {$i} Blurb", $d['blurb'], 'tmt_section_causes', 'textarea' );
        tmt_add_href_setting( $wp_customize, "tmt_cause_{$i}_donate_href", "Cause {$i} Donate Button Link", $d['donate_href'], 'tmt_section_causes' );

        $image_default = ! empty( $d['image_file'] ) ? tmt_asset_uri( $d['image_file'] ) : '';
        $wp_customize->add_setting( "tmt_cause_{$i}_image", array( 'default' => 0, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ) );
        $wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, "tmt_cause_{$i}_image", array(
            'label' => sprintf( __( 'Cause %d Image', 'tmt' ), $i ), 'section' => 'tmt_section_causes', 'mime_type' => 'image',
        ) ) );
        $wp_customize->add_setting( "tmt_cause_{$i}_image_url", array(
            'default' => $image_default, 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh',
        ) );
    }
}

function tmt_register_marquee_settings( $wp_customize ) {
    $defaults    = tmt_get_marquee_defaults();
    $count       = 6;
    $extra       = (int) get_option( 'tmt_extra_gallery_items', 3 );
    $max_items   = $count + $extra;

    for ( $i = 1; $i <= $max_items; $i++ ) {
        $default = isset( $defaults[ $i ] ) ? $defaults[ $i ] : '';
        tmt_add_text_setting( $wp_customize, "tmt_marquee_{$i}_text", "Marquee Item {$i}", $default, 'tmt_section_marquee' );
    }
}

function tmt_register_divider_setting( $wp_customize, $index, $section ) {
    $defaults = tmt_get_divider_defaults();
    $d        = isset( $defaults[ $index ] ) ? $defaults[ $index ] : array( 'image_file' => '', 'alt' => '', 'caption' => '' );

    tmt_add_text_setting( $wp_customize, "tmt_divider_{$index}_caption", 'Caption', $d['caption'], $section );
    tmt_add_text_setting( $wp_customize, "tmt_divider_{$index}_alt", 'Image Description (for accessibility)', $d['alt'], $section );

    $image_default = ! empty( $d['image_file'] ) ? tmt_asset_uri( $d['image_file'] ) : '';
    $wp_customize->add_setting( "tmt_divider_{$index}_image", array( 'default' => 0, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ) );
    $wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, "tmt_divider_{$index}_image", array(
        'label' => __( 'Background Image', 'tmt' ), 'section' => $section, 'mime_type' => 'image',
    ) ) );
    $wp_customize->add_setting( "tmt_divider_{$index}_image_url", array(
        'default' => $image_default, 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh',
    ) );
}

function tmt_register_extra_divider_settings( $wp_customize ) {
    $defaults  = tmt_get_divider_defaults();
    $count     = 3;
    $extra     = (int) get_option( 'tmt_extra_gallery_items', 3 );
    $max_items = $count + $extra;

    for ( $i = 4; $i <= $max_items; $i++ ) {
        $d = isset( $defaults[ $i ] ) ? $defaults[ $i ] : array( 'image_file' => '', 'alt' => '', 'caption' => '' );
        tmt_add_text_setting( $wp_customize, "tmt_divider_{$i}_caption", "Divider {$i} Caption", $d['caption'], 'tmt_section_divider_extra' );
        tmt_add_text_setting( $wp_customize, "tmt_divider_{$i}_alt", "Divider {$i} Image Description", $d['alt'], 'tmt_section_divider_extra' );

        $image_default = ! empty( $d['image_file'] ) ? tmt_asset_uri( $d['image_file'] ) : '';
        $wp_customize->add_setting( "tmt_divider_{$i}_image", array( 'default' => 0, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ) );
        $wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, "tmt_divider_{$i}_image", array(
            'label' => sprintf( __( 'Divider %d Image', 'tmt' ), $i ), 'section' => 'tmt_section_divider_extra', 'mime_type' => 'image',
        ) ) );
        $wp_customize->add_setting( "tmt_divider_{$i}_image_url", array(
            'default' => $image_default, 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh',
        ) );
    }
}

function tmt_register_intake_settings( $wp_customize ) {
    tmt_register_media_video( $wp_customize, 'tmt_intake_video', 'Background Video', tmt_asset_uri( 'apply-loop.mp4' ), 'tmt_section_intake' );

    tmt_add_text_setting( $wp_customize, 'tmt_intake_label', 'Section Label', 'Get listed', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_heading', 'Heading', 'Apply to be featured.', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_intro', 'Introduction', 'Charities, foundations, community chests, and one-off fundraising events are all welcome. Tell us who you are and what you are raising for, and we will follow up with the short verification checklist.', 'tmt_section_intake', 'textarea' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_review_time_label', 'Review Time Label', 'Review time', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_review_time_value', 'Review Time Value', '5 to 7 business days', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_listing_fee_label', 'Listing Fee Label', 'Listing fee', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_listing_fee_value', 'Listing Fee Value', 'None', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_questions_label', 'Questions Label', 'Questions', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_questions_value', 'Questions Link Text', 'info@canyougive.org', 'tmt_section_intake' );
    tmt_add_href_setting( $wp_customize, 'tmt_intake_questions_href', 'Questions Link', 'mailto:info@canyougive.org', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_name_label', 'Name Field Label', 'Name', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_name_placeholder', 'Name Field Placeholder', 'Your full name', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_cause_label', 'Organization Field Label', 'Cause or organization', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_cause_placeholder', 'Organization Field Placeholder', 'Charity, foundation, or event name', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_email_label', 'Email Field Label', 'Email', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_email_placeholder', 'Email Field Placeholder', 'you@organization.org', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_note_label', 'Message Field Label', 'What are you raising for?', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_note_placeholder', 'Message Field Placeholder', 'A couple of sentences is plenty.', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_submit_text', 'Submit Button Text', 'Submit application', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_disclaimer', 'Form Disclaimer', 'We review every application and reply by email.', 'tmt_section_intake', 'textarea' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_success_title', 'Success Message Title', 'Application received', 'tmt_section_intake' );
    tmt_add_text_setting( $wp_customize, 'tmt_intake_success_description', 'Success Message Description', 'Thank you! We will review your application and follow up shortly.', 'tmt_section_intake', 'textarea' );

    $wp_customize->add_setting( 'tmt_contact_email', array(
        'default'           => get_option( 'admin_email' ),
        'sanitize_callback' => 'sanitize_email',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'tmt_contact_email', array(
        'label'   => __( 'Form Recipient Email', 'tmt' ),
        'section' => 'tmt_section_intake',
        'type'    => 'email',
    ) );
}

function tmt_register_footer_settings( $wp_customize ) {
    tmt_add_text_setting( $wp_customize, 'tmt_footer_tagline', 'Tagline', 'A community-run giving portal. Shop the retailers you already use and let the commission go to a cause near you.', 'tmt_section_footer', 'textarea' );
    tmt_add_text_setting( $wp_customize, 'tmt_footer_navigate_label', 'Navigate Column Label', 'Navigate', 'tmt_section_footer' );
    tmt_add_text_setting( $wp_customize, 'tmt_footer_contact_label', 'Contact Column Label', 'Contact', 'tmt_section_footer' );
    tmt_add_text_setting( $wp_customize, 'tmt_footer_home_label', 'Home Link Text', 'Home', 'tmt_section_footer' );
    tmt_add_href_setting( $wp_customize, 'tmt_footer_home_href', 'Home Link', '#top', 'tmt_section_footer' );
    tmt_add_text_setting( $wp_customize, 'tmt_footer_email', 'Email Link Text', 'info@canyougive.org', 'tmt_section_footer' );
    tmt_add_href_setting( $wp_customize, 'tmt_footer_email_href', 'Email Link', 'mailto:info@canyougive.org', 'tmt_section_footer' );
    tmt_add_text_setting( $wp_customize, 'tmt_footer_phone', 'Phone Link Text', '570-441-2926', 'tmt_section_footer' );
    tmt_add_href_setting( $wp_customize, 'tmt_footer_phone_href', 'Phone Link', 'tel:+15704412926', 'tmt_section_footer' );
    tmt_add_text_setting( $wp_customize, 'tmt_footer_apply_text', 'Apply Button Text', 'Apply to be featured', 'tmt_section_footer' );
    tmt_add_href_setting( $wp_customize, 'tmt_footer_apply_href', 'Apply Button Link', '#apply', 'tmt_section_footer' );
    tmt_add_text_setting( $wp_customize, 'tmt_footer_copyright', 'Copyright Line', '© {year} Can You Give. All rights reserved.', 'tmt_section_footer' );
    tmt_add_text_setting( $wp_customize, 'tmt_footer_built_by_text', 'Built By Link Text', 'Built by The Free Website Guys', 'tmt_section_footer' );
    tmt_add_href_setting( $wp_customize, 'tmt_footer_built_by_href', 'Built By Link', 'https://thefreewebsiteguys.com', 'tmt_section_footer' );
    tmt_add_text_setting( $wp_customize, 'tmt_footer_affiliate_disclaimer', 'Affiliate Disclaimer', 'Retailer links are affiliate links. Prices are never affected.', 'tmt_section_footer' );
}

function tmt_customizer_register( $wp_customize ) {
    $wp_customize->add_panel( 'tmt_panel', array(
        'title'    => __( 'Can You Give Theme Options', 'tmt' ),
        'priority' => 10,
    ) );

    $sections = array(
        'tmt_section_colors'       => 'Colors',
        'tmt_section_global'       => 'Logo & Global',
        'tmt_section_hero'         => 'Hero Section',
        'tmt_section_marquee'      => 'Marquee',
        'tmt_section_hiw'          => 'How It Works',
        'tmt_section_divider_1'    => 'Parallax Divider (after How It Works)',
        'tmt_section_faq'          => 'FAQ',
        'tmt_section_retailers'    => 'Retailers',
        'tmt_section_divider_2'    => 'Parallax Divider (after Retailers)',
        'tmt_section_causes'       => 'Causes',
        'tmt_section_divider_3'    => 'Parallax Divider (after Causes)',
        'tmt_section_intake'       => 'Intake Form',
        'tmt_section_footer'       => 'Footer',
    );

    foreach ( $sections as $id => $title ) {
        $wp_customize->add_section( $id, array( 'title' => __( $title, 'tmt' ), 'panel' => 'tmt_panel' ) );
    }

    tmt_register_color_settings( $wp_customize );
    tmt_register_global_settings( $wp_customize );
    tmt_register_hero_settings( $wp_customize );
    tmt_register_marquee_settings( $wp_customize );
    tmt_register_how_it_works_settings( $wp_customize );
    tmt_register_divider_setting( $wp_customize, 1, 'tmt_section_divider_1' );
    tmt_register_faq_settings( $wp_customize );
    tmt_register_retailer_settings( $wp_customize );
    tmt_register_divider_setting( $wp_customize, 2, 'tmt_section_divider_2' );
    tmt_register_cause_settings( $wp_customize );
    tmt_register_divider_setting( $wp_customize, 3, 'tmt_section_divider_3' );

    $extra_dividers = (int) get_option( 'tmt_extra_gallery_items', 3 );
    if ( $extra_dividers > 0 ) {
        $wp_customize->add_section( 'tmt_section_divider_extra', array(
            'title' => __( 'Additional Parallax Dividers', 'tmt' ),
            'panel' => 'tmt_panel',
        ) );
        tmt_register_extra_divider_settings( $wp_customize );
    }

    tmt_register_intake_settings( $wp_customize );
    tmt_register_footer_settings( $wp_customize );
}
add_action( 'customize_register', 'tmt_customizer_register' );
