<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'str_starts_with' ) ) {
    function str_starts_with( string $haystack, string $needle ): bool {
        return strncmp( $haystack, $needle, strlen( $needle ) ) === 0;
    }
}
if ( ! function_exists( 'str_ends_with' ) ) {
    function str_ends_with( string $haystack, string $needle ): bool {
        return $needle === '' || substr( $haystack, -strlen( $needle ) ) === $needle;
    }
}
if ( ! function_exists( 'str_contains' ) ) {
    function str_contains( string $haystack, string $needle ): bool {
        return $needle === '' || strpos( $haystack, $needle ) !== false;
    }
}
if ( ! function_exists( 'tmt_cbrt' ) ) {
    function tmt_cbrt( float $n ): float {
        return $n >= 0 ? pow( $n, 1 / 3 ) : -pow( -$n, 1 / 3 );
    }
}

function tmt_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo' );
}
add_action( 'after_setup_theme', 'tmt_theme_setup' );

function tmt_enqueue_fonts() {
    wp_enqueue_style(
        'tmt-plus-jakarta-sans',
        'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap',
        array(),
        null
    );
}
add_action( 'wp_enqueue_scripts', 'tmt_enqueue_fonts', 5 );
add_action( 'customize_preview_init', 'tmt_enqueue_fonts', 5 );

function tmt_resource_hints( $urls, $relation_type ) {
    if ( 'preconnect' === $relation_type ) {
        $urls[] = array(
            'href' => 'https://fonts.googleapis.com',
        );
        $urls[] = array(
            'href'        => 'https://fonts.gstatic.com',
            'crossorigin' => 'anonymous',
        );
    }
    return $urls;
}
add_filter( 'wp_resource_hints', 'tmt_resource_hints', 10, 2 );

function tmt_asset_uri( $file ) {
    return get_template_directory_uri() . '/assets/images/' . ltrim( $file, '/' );
}

/**
 * Normalize a hex color string for comparison (#abc -> #aabbcc).
 */
function tmt_normalize_hex( $hex ) {
    $hex = ltrim( strtolower( (string) $hex ), '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return '#' . $hex;
}

/**
 * Hex defaults for the Customizer color pickers (visual match for :root tokens).
 */
function tmt_get_color_hex_defaults() {
    return array(
        'tmt_color_ink'    => '#3d5456',
        'tmt_color_canvas' => '#fafaf8',
        'tmt_color_sand'   => '#edf2f1',
        'tmt_color_clay'   => '#ddb95a',
        'tmt_color_give'   => '#d97830',
        'tmt_color_lilac'  => '#c4a3bf',
        'tmt_color_rule'   => '#dce4e2',
    );
}

/**
 * Exact oklch values copied from the CSS source (:root in index.css / styles.css).
 * Used when the Customizer value matches the hex default — never approximate.
 */
function tmt_get_color_oklch_defaults() {
    return array(
        'tmt_color_ink'    => 'oklch(0.33 0.045 195)',
        'tmt_color_canvas' => 'oklch(0.985 0.005 100)',
        'tmt_color_sand'   => 'oklch(0.952 0.014 190)',
        'tmt_color_clay'   => 'oklch(0.815 0.155 82)',
        'tmt_color_give'   => 'oklch(0.655 0.165 45)',
        'tmt_color_lilac'  => 'oklch(0.775 0.072 315)',
        'tmt_color_rule'   => 'oklch(0.9 0.014 190)',
    );
}

/**
 * Convert hex color to oklch for custom (non-default) Customizer picks only.
 */
function tmt_hex_to_oklch( $hex ) {
    $hex = ltrim( (string) $hex, '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if ( strlen( $hex ) !== 6 ) {
        return $hex;
    }

    $r = hexdec( substr( $hex, 0, 2 ) ) / 255;
    $g = hexdec( substr( $hex, 2, 2 ) ) / 255;
    $b = hexdec( substr( $hex, 4, 2 ) ) / 255;

    $l = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    $max = max( $r, $g, $b );
    $min = min( $r, $g, $b );
    $chroma = $max - $min;
    $hue = 0.0;

    if ( $chroma > 0 ) {
        if ( $max === $r ) {
            $hue = fmod( ( $g - $b ) / $chroma, 6 );
        } elseif ( $max === $g ) {
            $hue = ( $b - $r ) / $chroma + 2;
        } else {
            $hue = ( $r - $g ) / $chroma + 4;
        }
        $hue = $hue * 60;
        if ( $hue < 0 ) {
            $hue += 360;
        }
    }

    $L = $l;
    $C = $chroma * 0.4;
    $H = $hue;

    return sprintf( 'oklch(%.3f %.3f %.1f)', $L, $C, $H );
}

function tmt_get_color_value( $setting_id ) {
    $hex_defaults   = tmt_get_color_hex_defaults();
    $oklch_defaults = tmt_get_color_oklch_defaults();

    if ( ! isset( $hex_defaults[ $setting_id ] ) ) {
        return '';
    }

    $default_hex = $hex_defaults[ $setting_id ];
    $hex         = get_theme_mod( $setting_id, $default_hex );
    if ( empty( $hex ) ) {
        $hex = $default_hex;
    }
    if ( str_starts_with( $hex, 'oklch' ) ) {
        return $hex;
    }
    if ( tmt_normalize_hex( $hex ) === tmt_normalize_hex( $default_hex ) ) {
        return $oklch_defaults[ $setting_id ];
    }
    return tmt_hex_to_oklch( $hex );
}

function tmt_get_theme_colors() {
    $setting_to_token = array(
        'tmt_color_ink'    => 'ink',
        'tmt_color_canvas' => 'canvas',
        'tmt_color_sand'   => 'sand',
        'tmt_color_clay'   => 'clay',
        'tmt_color_give'   => 'give',
        'tmt_color_lilac'  => 'lilac',
        'tmt_color_rule'   => 'rule',
    );

    $colors = array();
    foreach ( $setting_to_token as $setting_id => $token ) {
        $colors[ $token ] = tmt_get_color_value( $setting_id );
    }
    return $colors;
}

function tmt_sanitize_cta_href( $value ) {
    $value = trim( $value );
    if ( empty( $value ) ) return '';

    if ( stripos( $value, 'javascript:' ) === 0 ) return '';

    if ( substr( $value, 0, 1 ) === '#' ) {
        return sanitize_text_field( $value );
    }

    if ( substr( $value, 0, 1 ) === '/' ) {
        return esc_url_raw( $value );
    }

    if ( preg_match( '/^(tel|mailto):/i', $value ) ) {
        return esc_url_raw( $value, array( 'tel', 'mailto' ) );
    }

    return esc_url_raw( $value );
}

function tmt_get_retailer_defaults() {
    return array(
        1 => array( 'name' => 'Northline Apparel', 'category' => 'Fashion', 'logo_file' => 'logo-northline.png', 'give' => '5% back', 'cause' => 'Valley Food Pantry', 'blurb' => 'Everyday basics and outerwear from a family-run label.', 'shop_href' => '#retailers' ),
        2 => array( 'name' => 'Cedar & Co. Home', 'category' => 'Home', 'logo_file' => 'logo-cedar.png', 'give' => '4% back', 'cause' => 'Valley Food Pantry', 'blurb' => 'Furniture, bedding, and kitchen goods for the whole house.', 'shop_href' => '#retailers' ),
        3 => array( 'name' => 'Volt Electronics', 'category' => 'Tech', 'logo_file' => 'logo-volt.png', 'give' => '3% back', 'cause' => 'Riverside Youth League', 'blurb' => 'Laptops, audio, and smart home hardware at market price.', 'shop_href' => '#retailers' ),
        4 => array( 'name' => 'Bloom Beauty Co.', 'category' => 'Beauty', 'logo_file' => 'logo-bloom.png', 'give' => '7% back', 'cause' => 'Riverside Youth League', 'blurb' => 'Skincare and haircare lines, refill programs included.', 'shop_href' => '#retailers' ),
        5 => array( 'name' => 'Ridge Outfitters', 'category' => 'Outdoors', 'logo_file' => 'logo-ridge.png', 'give' => '6% back', 'cause' => 'Second Chance Shelter', 'blurb' => 'Trail gear, training equipment, and team uniforms.', 'shop_href' => '#retailers' ),
        6 => array( 'name' => 'Margin Books', 'category' => 'Books', 'logo_file' => 'logo-margin.png', 'give' => '8% back', 'cause' => 'Second Chance Shelter', 'blurb' => 'Independent bookshop with classroom bulk ordering.', 'shop_href' => '#retailers' ),
        7 => array( 'name' => 'Harbor Kids', 'category' => 'Home', 'logo_file' => 'logo-harbor.png', 'give' => '5% back', 'cause' => 'Community Chest Gala', 'blurb' => "Children's clothing, toys, and school-season essentials.", 'shop_href' => '#retailers' ),
        8 => array( 'name' => 'Studio Nine', 'category' => 'Fashion', 'logo_file' => 'logo-studionine.png', 'give' => '6% back', 'cause' => 'Community Chest Gala', 'blurb' => 'Workwear and tailoring with a made-to-order option.', 'shop_href' => '#retailers' ),
    );
}

function tmt_get_cause_defaults() {
    return array(
        1 => array( 'name' => 'Valley Food Pantry', 'type' => 'Non Profits', 'location' => 'Wilkes-Barre, PA', 'image_file' => 'cause-food-bank.jpg', 'blurb' => 'Weekly grocery boxes for 400 households across the valley.', 'donate_href' => 'https://example.org/valley-food-pantry/donate', 'band' => 'bg-clay', 'cta' => 'bg-clay text-clay-foreground hover:bg-clay/90' ),
        2 => array( 'name' => 'Riverside Youth League', 'type' => 'Events', 'location' => 'Scranton, PA', 'image_file' => 'cause-youth-sports.jpg', 'blurb' => 'Uniforms, field time, and travel costs for kids who play free.', 'donate_href' => 'https://example.org/riverside-youth-league/donate', 'band' => 'bg-give', 'cta' => 'bg-give text-give-foreground hover:bg-give/90' ),
        3 => array( 'name' => 'Second Chance Shelter', 'type' => 'Foundations', 'location' => 'Pittston, PA', 'image_file' => 'cause-animal-shelter.jpg', 'blurb' => 'Medical care and fostering for surrendered pets.', 'donate_href' => 'https://example.org/second-chance-shelter/donate', 'band' => 'bg-lilac', 'cta' => 'bg-lilac text-lilac-foreground hover:bg-lilac/90' ),
        4 => array( 'name' => 'Community Chest Gala', 'type' => 'Community Chests', 'location' => 'Hazleton, PA', 'image_file' => 'cause-gala.jpg', 'blurb' => 'One night funding twelve neighborhood programs at once.', 'donate_href' => 'https://example.org/community-chest-gala/donate', 'band' => 'bg-foreground', 'cta' => 'bg-foreground text-primary-foreground hover:bg-foreground/90' ),
    );
}

function tmt_get_faq_defaults() {
    return array(
        1 => array( 'q' => 'Does shopping through the portal cost me anything extra?', 'a' => 'No. You pay the retailer\'s normal price. Retailers pay us an affiliate commission for sending you their way, and we direct that commission to the cause you picked.' ),
        2 => array( 'q' => 'How much of the commission reaches my cause?', 'a' => 'All of our share. Can You Give is volunteer-run, so the commission we receive is passed through to the charity, foundation, or event you selected.' ),
        3 => array( 'q' => 'Can I change the cause I support?', 'a' => 'Any time. Pick a different charity or event before your next shopping trip and future contributions follow the new selection.' ),
        4 => array( 'q' => 'How do charities and events get listed?', 'a' => 'Submit the intake form on this page. We review each applicant for community standing and non-profit status, then add approved organizations to the directory.' ),
        5 => array( 'q' => 'Do you track what I buy?', 'a' => 'We only need to know that a purchase happened so the commission can be attributed. Individual order contents are never part of what we publish or share.' ),
        6 => array( 'q' => 'Where can I see the numbers?', 'a' => 'We publish a quarterly summary of commissions received and amounts routed, broken out by cause, so anyone can check the math.' ),
    );
}

function tmt_get_marquee_defaults() {
    return array(
        1 => 'Shop as usual',
        2 => 'Give without spending more',
        3 => 'Zero overhead',
        4 => 'Local causes first',
        5 => 'Reported quarterly',
        6 => 'Volunteer run',
    );
}

function tmt_get_divider_defaults() {
    return array(
        1 => array( 'image_file' => 'divider-community.jpg', 'alt' => 'Neighbors handing over crates of fresh groceries outside a community center', 'caption' => 'Commissions become groceries, uniforms, and vet bills.' ),
        2 => array( 'image_file' => 'divider-retail.jpg', 'alt' => 'A shopper browsing shelves inside a bright modern store', 'caption' => 'Same stores, same prices, a different outcome.' ),
        3 => array( 'image_file' => 'divider-event.jpg', 'alt' => 'A local charity fundraising event in a town park at golden hour', 'caption' => 'Every cause here was nominated by someone local.' ),
    );
}

function tmt_get_theme_data() {
    $logo_attachment_id = (int) get_theme_mod( 'tmt_logo_id', 0 );

    $retailer_count    = 8;
    $extra_grid_rows   = (int) get_option( 'tmt_extra_grid_rows', 1 );
    $max_retailers     = $retailer_count + ( $extra_grid_rows * 3 );

    $cause_count         = 4;
    $extra_column_rows   = (int) get_option( 'tmt_extra_column_rows', 3 );
    $max_causes          = $cause_count + ( $extra_column_rows * 2 );

    $faq_count       = 6;
    $max_faqs        = $faq_count + ( $extra_column_rows * 2 );

    $marquee_count   = 6;
    $extra_gallery   = (int) get_option( 'tmt_extra_gallery_items', 3 );
    $max_marquee     = $marquee_count + $extra_gallery;

    $divider_count   = 3;
    $max_dividers    = $divider_count + $extra_gallery;

    $retailer_defaults = tmt_get_retailer_defaults();
    $cause_defaults    = tmt_get_cause_defaults();
    $faq_defaults      = tmt_get_faq_defaults();
    $marquee_defaults  = tmt_get_marquee_defaults();
    $divider_defaults  = tmt_get_divider_defaults();

    $retailers = array_values( array_filter( array_map( function( $i ) use ( $retailer_defaults ) {
        $defaults = isset( $retailer_defaults[ $i ] ) ? $retailer_defaults[ $i ] : array();
        $name     = get_theme_mod( "tmt_retailer_{$i}_name", isset( $defaults['name'] ) ? $defaults['name'] : '' );
        if ( empty( trim( $name ) ) ) return null;
        $logo_url = get_theme_mod( "tmt_retailer_{$i}_logo_url", '' );
        if ( empty( $logo_url ) && ! empty( $defaults['logo_file'] ) ) {
            $logo_url = tmt_asset_uri( $defaults['logo_file'] );
        }
        return array(
            'name'      => $name,
            'category'  => get_theme_mod( "tmt_retailer_{$i}_category", isset( $defaults['category'] ) ? $defaults['category'] : '' ),
            'logo_url'  => $logo_url,
            'give'      => get_theme_mod( "tmt_retailer_{$i}_give", isset( $defaults['give'] ) ? $defaults['give'] : '' ),
            'cause'     => get_theme_mod( "tmt_retailer_{$i}_cause", isset( $defaults['cause'] ) ? $defaults['cause'] : '' ),
            'blurb'     => get_theme_mod( "tmt_retailer_{$i}_blurb", isset( $defaults['blurb'] ) ? $defaults['blurb'] : '' ),
            'shop_href' => get_theme_mod( "tmt_retailer_{$i}_shop_href", isset( $defaults['shop_href'] ) ? $defaults['shop_href'] : '#retailers' ),
        );
    }, range( 1, $max_retailers ) ) ) );

    $causes = array_values( array_filter( array_map( function( $i ) use ( $cause_defaults ) {
        $defaults = isset( $cause_defaults[ $i ] ) ? $cause_defaults[ $i ] : array();
        $name     = get_theme_mod( "tmt_cause_{$i}_name", isset( $defaults['name'] ) ? $defaults['name'] : '' );
        if ( empty( trim( $name ) ) ) return null;
        $image_url = get_theme_mod( "tmt_cause_{$i}_image_url", '' );
        if ( empty( $image_url ) && ! empty( $defaults['image_file'] ) ) {
            $image_url = tmt_asset_uri( $defaults['image_file'] );
        }
        return array(
            'name'         => $name,
            'type'         => get_theme_mod( "tmt_cause_{$i}_type", isset( $defaults['type'] ) ? $defaults['type'] : '' ),
            'location'     => get_theme_mod( "tmt_cause_{$i}_location", isset( $defaults['location'] ) ? $defaults['location'] : '' ),
            'image_url'    => $image_url,
            'blurb'        => get_theme_mod( "tmt_cause_{$i}_blurb", isset( $defaults['blurb'] ) ? $defaults['blurb'] : '' ),
            'donate_href'  => get_theme_mod( "tmt_cause_{$i}_donate_href", isset( $defaults['donate_href'] ) ? $defaults['donate_href'] : '' ),
            'band'         => isset( $defaults['band'] ) ? $defaults['band'] : 'bg-clay',
            'cta'          => isset( $defaults['cta'] ) ? $defaults['cta'] : 'bg-clay text-clay-foreground hover:bg-clay/90',
        );
    }, range( 1, $max_causes ) ) ) );

    $faqs = array_values( array_filter( array_map( function( $i ) use ( $faq_defaults ) {
        $defaults = isset( $faq_defaults[ $i ] ) ? $faq_defaults[ $i ] : array();
        $q        = get_theme_mod( "tmt_faq_{$i}_q", isset( $defaults['q'] ) ? $defaults['q'] : '' );
        if ( empty( trim( $q ) ) ) return null;
        return array(
            'q' => $q,
            'a' => get_theme_mod( "tmt_faq_{$i}_a", isset( $defaults['a'] ) ? $defaults['a'] : '' ),
        );
    }, range( 1, $max_faqs ) ) ) );

    $marquee_items = array_values( array_filter( array_map( function( $i ) use ( $marquee_defaults ) {
        $default = isset( $marquee_defaults[ $i ] ) ? $marquee_defaults[ $i ] : '';
        $text    = get_theme_mod( "tmt_marquee_{$i}_text", $default );
        return empty( trim( $text ) ) ? null : $text;
    }, range( 1, $max_marquee ) ) ) );

    $dividers = array_values( array_filter( array_map( function( $i ) use ( $divider_defaults ) {
        $defaults = isset( $divider_defaults[ $i ] ) ? $divider_defaults[ $i ] : array();
        $caption  = get_theme_mod( "tmt_divider_{$i}_caption", isset( $defaults['caption'] ) ? $defaults['caption'] : '' );
        if ( empty( trim( $caption ) ) ) return null;
        $image_url = get_theme_mod( "tmt_divider_{$i}_image_url", '' );
        if ( empty( $image_url ) && ! empty( $defaults['image_file'] ) ) {
            $image_url = tmt_asset_uri( $defaults['image_file'] );
        }
        return array(
            'image_url' => $image_url,
            'alt'       => get_theme_mod( "tmt_divider_{$i}_alt", isset( $defaults['alt'] ) ? $defaults['alt'] : '' ),
            'caption'   => $caption,
        );
    }, range( 1, $max_dividers ) ) ) );

    return array(
        'colors' => tmt_get_theme_colors(),
        'global' => array(
            'site_name'       => get_theme_mod( 'tmt_site_name', 'Can You Give' ),
            'logo_url'        => get_theme_mod( 'tmt_logo_url', tmt_asset_uri( 'logo-canyougive.png' ) ),
            'logo_is_default' => ( $logo_attachment_id === 0 ),
            'logo_height'     => max( 32, min( 160, (int) get_theme_mod( 'tmt_logo_height', 76 ) ) ),
        ),
        'header' => array(
            'tagline'   => get_theme_mod( 'tmt_header_tagline', 'Shop. Give. Repeat.' ),
            'home_href' => get_theme_mod( 'tmt_header_home_href', '#top' ),
            'cta_text'  => get_theme_mod( 'tmt_header_cta_text', 'Browse Retailers' ),
            'cta_href'  => get_theme_mod( 'tmt_header_cta_href', '#retailers' ),
            'nav_links' => array(
                array( 'label' => get_theme_mod( 'tmt_nav_1_label', 'How it works' ), 'href' => get_theme_mod( 'tmt_nav_1_href', '#how-it-works' ) ),
                array( 'label' => get_theme_mod( 'tmt_nav_2_label', 'Retailers' ), 'href' => get_theme_mod( 'tmt_nav_2_href', '#retailers' ) ),
                array( 'label' => get_theme_mod( 'tmt_nav_3_label', 'Causes' ), 'href' => get_theme_mod( 'tmt_nav_3_href', '#causes' ) ),
                array( 'label' => get_theme_mod( 'tmt_nav_4_label', 'FAQ' ), 'href' => get_theme_mod( 'tmt_nav_4_href', '#faq' ) ),
                array( 'label' => get_theme_mod( 'tmt_nav_5_label', 'Apply' ), 'href' => get_theme_mod( 'tmt_nav_5_href', '#apply' ) ),
            ),
        ),
        'hero' => array(
            'badge_text'         => get_theme_mod( 'tmt_hero_badge_text', 'Zero overhead giving' ),
            'heading'            => get_theme_mod( 'tmt_hero_heading', 'Help others, one shopping trip at a time!' ),
            'subheading'         => get_theme_mod( 'tmt_hero_subheading', 'Shopping through our portal directs a portion of your purchase to a charity, event, or foundation of your choosing.' ),
            'video_url'          => get_theme_mod( 'tmt_hero_video_url', tmt_asset_uri( 'hero-loop.mp4' ) ),
            'poster_image_url'   => get_theme_mod( 'tmt_hero_poster_url', tmt_asset_uri( 'hero-shopping.jpg' ) ),
            'video_aria_label'   => get_theme_mod( 'tmt_hero_video_aria', 'Shoppers walking together with bags on a sunlit street' ),
            'cta_primary_text'   => get_theme_mod( 'tmt_hero_cta_primary_text', 'Browse Retailers' ),
            'cta_primary_href'   => get_theme_mod( 'tmt_hero_cta_primary_href', '#retailers' ),
            'cta_secondary_text' => get_theme_mod( 'tmt_hero_cta_secondary_text', 'Find a Cause' ),
            'cta_secondary_href' => get_theme_mod( 'tmt_hero_cta_secondary_href', '#causes' ),
            'stats'              => array(
                array( 'value' => get_theme_mod( 'tmt_hero_stat_1_value', '100%' ), 'label' => get_theme_mod( 'tmt_hero_stat_1_label', 'of our share passed on' ) ),
                array( 'value' => get_theme_mod( 'tmt_hero_stat_2_value', '180+' ), 'label' => get_theme_mod( 'tmt_hero_stat_2_label', 'partner retailers' ) ),
                array( 'value' => get_theme_mod( 'tmt_hero_stat_3_value', '$0' ), 'label' => get_theme_mod( 'tmt_hero_stat_3_label', 'cost to you, ever' ) ),
            ),
        ),
        'marquee' => array(
            'items' => $marquee_items,
        ),
        'how_it_works' => array(
            'label'                => get_theme_mod( 'tmt_hiw_label', 'How it works' ),
            'heading'              => get_theme_mod( 'tmt_hiw_heading', 'A transparent process, built by the community it serves.' ),
            'steps'                => array(
                array(
                    'icon'  => 'HandHeart',
                    'title' => get_theme_mod( 'tmt_hiw_step_1_title', 'Pick your cause' ),
                    'body'  => get_theme_mod( 'tmt_hiw_step_1_body', 'Choose any charity, community chest, or local event listed in our directory. Switch it whenever you like.' ),
                    'card'  => 'bg-clay text-clay-foreground',
                    'chip'  => 'bg-clay-foreground text-clay',
                    'muted' => 'text-clay-foreground/75',
                ),
                array(
                    'icon'  => 'MousePointerClick',
                    'title' => get_theme_mod( 'tmt_hiw_step_2_title', 'Shop as usual' ),
                    'body'  => get_theme_mod( 'tmt_hiw_step_2_body', 'Head to a partner retailer through our catalog. Same prices, same checkout, no extra step.' ),
                    'card'  => 'bg-give text-give-foreground',
                    'chip'  => 'bg-give-foreground/20 text-give-foreground',
                    'muted' => 'text-give-foreground/80',
                ),
                array(
                    'icon'  => 'Receipt',
                    'title' => get_theme_mod( 'tmt_hiw_step_3_title', 'We pass it on' ),
                    'body'  => get_theme_mod( 'tmt_hiw_step_3_body', 'The retailer pays us a commission and we direct our share to your chosen cause. Nothing added to your total.' ),
                    'card'  => 'bg-lilac text-lilac-foreground',
                    'chip'  => 'bg-lilac-foreground text-lilac',
                    'muted' => 'text-lilac-foreground/75',
                ),
            ),
            'community_image_url'  => get_theme_mod( 'tmt_hiw_community_image_url', tmt_asset_uri( 'community-volunteers.jpg' ) ),
            'community_image_alt'  => get_theme_mod( 'tmt_hiw_community_image_alt', 'Volunteers packing donation boxes in a community hall' ),
            'community_heading'    => get_theme_mod( 'tmt_hiw_community_heading', 'Every dollar routed stays in the community that earned it.' ),
            'community_body'       => get_theme_mod( 'tmt_hiw_community_body', 'No administrative cut, no membership, no paperwork for shoppers.' ),
            'overhead_value'       => get_theme_mod( 'tmt_hiw_overhead_value', '0%' ),
            'overhead_body'        => get_theme_mod( 'tmt_hiw_overhead_body', 'Overhead taken from your chosen cause. Volunteer-run, community-governed, reported quarterly in the open.' ),
        ),
        'faq' => array(
            'label'             => get_theme_mod( 'tmt_faq_label', 'FAQ' ),
            'heading'           => get_theme_mod( 'tmt_faq_heading', 'Questions, answered plainly.' ),
            'contact_prefix'    => get_theme_mod( 'tmt_faq_contact_prefix', 'Still unsure about something? Write to' ),
            'contact_email'     => get_theme_mod( 'tmt_faq_contact_email', 'info@canyougive.org' ),
            'contact_email_href'=> get_theme_mod( 'tmt_faq_contact_email_href', 'mailto:info@canyougive.org' ),
            'contact_suffix'    => get_theme_mod( 'tmt_faq_contact_suffix', 'and a real person replies.' ),
            'items'             => $faqs,
        ),
        'retailers' => array(
            'label'              => get_theme_mod( 'tmt_retailers_label', 'Featured retailers' ),
            'heading'            => get_theme_mod( 'tmt_retailers_heading', 'The catalog. Shop it like any other store.' ),
            'intro'              => get_theme_mod( 'tmt_retailers_intro', 'Each link is an affiliate link. Your price stays the same, and the commission goes to the cause that retailer supports.' ),
            'search_placeholder' => get_theme_mod( 'tmt_retailers_search_placeholder', 'Search by store, category, or cause' ),
            'shop_now_text'      => get_theme_mod( 'tmt_retailers_shop_now_text', 'Shop Now' ),
            'empty_message'      => get_theme_mod( 'tmt_retailers_empty_message', 'No retailers match that search.' ),
            'clear_filters_text' => get_theme_mod( 'tmt_retailers_clear_filters_text', 'Clear filters' ),
            'did_you_mean_prefix'=> get_theme_mod( 'tmt_retailers_did_you_mean_prefix', 'Did you mean:' ),
            'categories'         => array( 'All', 'Fashion', 'Home', 'Tech', 'Beauty', 'Outdoors', 'Books' ),
            'items'              => $retailers,
        ),
        'causes' => array(
            'label'               => get_theme_mod( 'tmt_causes_label', 'Give directly' ),
            'heading'             => get_theme_mod( 'tmt_causes_heading', 'Feel like giving directly?' ),
            'intro'               => get_theme_mod( 'tmt_causes_intro', 'Search for your favorite cause or event to donate to directly, and 100% of your donation goes straight to them.' ),
            'search_placeholder'  => get_theme_mod( 'tmt_causes_search_placeholder', 'Search by name, place, or type' ),
            'donate_text'         => get_theme_mod( 'tmt_causes_donate_text', 'Donate' ),
            'donate_note_template'=> get_theme_mod( 'tmt_causes_donate_note_template', '100% goes to {name}' ),
            'empty_message'       => get_theme_mod( 'tmt_causes_empty_message', 'No causes match that search.' ),
            'clear_filters_text'  => get_theme_mod( 'tmt_causes_clear_filters_text', 'Clear filters' ),
            'did_you_mean_prefix' => get_theme_mod( 'tmt_causes_did_you_mean_prefix', 'Did you mean:' ),
            'categories'          => array( 'All', 'Events', 'Non Profits', 'Community Chests', 'Scholarships', 'Foundations' ),
            'items'               => $causes,
        ),
        'dividers' => array(
            'items' => $dividers,
        ),
        'intake' => array(
            'label'               => get_theme_mod( 'tmt_intake_label', 'Get listed' ),
            'heading'             => get_theme_mod( 'tmt_intake_heading', 'Apply to be featured.' ),
            'intro'               => get_theme_mod( 'tmt_intake_intro', 'Charities, foundations, community chests, and one-off fundraising events are all welcome. Tell us who you are and what you are raising for, and we will follow up with the short verification checklist.' ),
            'video_url'           => get_theme_mod( 'tmt_intake_video_url', tmt_asset_uri( 'apply-loop.mp4' ) ),
            'review_time_label'   => get_theme_mod( 'tmt_intake_review_time_label', 'Review time' ),
            'review_time_value'   => get_theme_mod( 'tmt_intake_review_time_value', '5 to 7 business days' ),
            'listing_fee_label'   => get_theme_mod( 'tmt_intake_listing_fee_label', 'Listing fee' ),
            'listing_fee_value'   => get_theme_mod( 'tmt_intake_listing_fee_value', 'None' ),
            'questions_label'     => get_theme_mod( 'tmt_intake_questions_label', 'Questions' ),
            'questions_value'     => get_theme_mod( 'tmt_intake_questions_value', 'info@canyougive.org' ),
            'questions_href'      => get_theme_mod( 'tmt_intake_questions_href', 'mailto:info@canyougive.org' ),
            'name_label'          => get_theme_mod( 'tmt_intake_name_label', 'Name' ),
            'name_placeholder'    => get_theme_mod( 'tmt_intake_name_placeholder', 'Your full name' ),
            'cause_label'         => get_theme_mod( 'tmt_intake_cause_label', 'Cause or organization' ),
            'cause_placeholder'   => get_theme_mod( 'tmt_intake_cause_placeholder', 'Charity, foundation, or event name' ),
            'email_label'         => get_theme_mod( 'tmt_intake_email_label', 'Email' ),
            'email_placeholder'   => get_theme_mod( 'tmt_intake_email_placeholder', 'you@organization.org' ),
            'note_label'          => get_theme_mod( 'tmt_intake_note_label', 'What are you raising for?' ),
            'note_placeholder'    => get_theme_mod( 'tmt_intake_note_placeholder', 'A couple of sentences is plenty.' ),
            'submit_text'         => get_theme_mod( 'tmt_intake_submit_text', 'Submit application' ),
            'disclaimer'          => get_theme_mod( 'tmt_intake_disclaimer', 'We review every application and reply by email.' ),
            'success_title'       => get_theme_mod( 'tmt_intake_success_title', 'Application received' ),
            'success_description' => get_theme_mod( 'tmt_intake_success_description', 'Thank you! We will review your application and follow up shortly.' ),
        ),
        'footer' => array(
            'tagline'              => get_theme_mod( 'tmt_footer_tagline', 'A community-run giving portal. Shop the retailers you already use and let the commission go to a cause near you.' ),
            'navigate_label'       => get_theme_mod( 'tmt_footer_navigate_label', 'Navigate' ),
            'contact_label'        => get_theme_mod( 'tmt_footer_contact_label', 'Contact' ),
            'home_label'           => get_theme_mod( 'tmt_footer_home_label', 'Home' ),
            'home_href'            => get_theme_mod( 'tmt_footer_home_href', get_theme_mod( 'tmt_header_home_href', '#top' ) ),
            'email'                => get_theme_mod( 'tmt_footer_email', 'info@canyougive.org' ),
            'email_href'           => get_theme_mod( 'tmt_footer_email_href', 'mailto:info@canyougive.org' ),
            'phone'                => get_theme_mod( 'tmt_footer_phone', '570-441-2926' ),
            'phone_href'           => get_theme_mod( 'tmt_footer_phone_href', 'tel:+15704412926' ),
            'apply_text'           => get_theme_mod( 'tmt_footer_apply_text', 'Apply to be featured' ),
            'apply_href'           => get_theme_mod( 'tmt_footer_apply_href', '#apply' ),
            'copyright'            => get_theme_mod( 'tmt_footer_copyright', '© {year} Can You Give. All rights reserved.' ),
            'built_by_text'        => get_theme_mod( 'tmt_footer_built_by_text', 'Built by The Free Website Guys' ),
            'built_by_href'        => get_theme_mod( 'tmt_footer_built_by_href', 'https://thefreewebsiteguys.com' ),
            'affiliate_disclaimer' => get_theme_mod( 'tmt_footer_affiliate_disclaimer', 'Retailer links are affiliate links. Prices are never affected.' ),
        ),
        'contact' => array(
            'recipient_email' => get_theme_mod( 'tmt_contact_email', get_option( 'admin_email' ) ),
        ),
        'preview' => array(
            'is_customizer' => is_customize_preview(),
        ),
    );
}

function tmt_enqueue_assets() {
    $build_dir = get_template_directory() . '/assets/build';
    $build_uri = get_template_directory_uri() . '/assets/build';

    wp_enqueue_style( 'tmt-style', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );

    $css_file = $build_dir . '/theme-app.css';
    if ( file_exists( $css_file ) ) {
        wp_enqueue_style( 'tmt-theme-app', $build_uri . '/theme-app.css', array(), filemtime( $css_file ) );
    }

    wp_register_script( 'tmt-boot', false, array(), null, true );

    wp_add_inline_script(
        'tmt-boot',
        'if (typeof process === "undefined") { window.process = { env: { NODE_ENV: "production" } }; }'
    );

    wp_add_inline_script(
        'tmt-boot',
        sprintf( 'window.TMT_THEME_DATA = %s;', wp_json_encode( array(
            'themeData' => tmt_get_theme_data(),
            'ajax'      => array(
                'url'   => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'tmt_forms' ),
            ),
        ) ) )
    );

    wp_enqueue_script( 'tmt-boot' );

    $js_file = $build_dir . '/theme-app.js';
    if ( file_exists( $js_file ) ) {
        wp_enqueue_script(
            'tmt-theme-app',
            $build_uri . '/theme-app.js',
            array( 'tmt-boot' ),
            filemtime( $js_file ),
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'tmt_enqueue_assets' );

function tmt_customizer_controls_enqueue() {
    wp_enqueue_media();

    wp_enqueue_script(
        'tmt-customizer-controls',
        get_template_directory_uri() . '/assets/js/customizer-controls.js',
        array( 'customize-controls', 'jquery', 'media-models' ),
        filemtime( get_template_directory() . '/assets/js/customizer-controls.js' ),
        true
    );

    $media_pairs = array(
        'tmt_logo_id'             => 'tmt_logo_url',
        'tmt_hero_video'          => 'tmt_hero_video_url',
        'tmt_hero_poster'         => 'tmt_hero_poster_url',
        'tmt_hiw_community_image' => 'tmt_hiw_community_image_url',
        'tmt_intake_video'        => 'tmt_intake_video_url',
    );
    $media_defaults = array(
        'tmt_logo_url'                => tmt_asset_uri( 'logo-canyougive.png' ),
        'tmt_hero_video_url'          => tmt_asset_uri( 'hero-loop.mp4' ),
        'tmt_hero_poster_url'         => tmt_asset_uri( 'hero-shopping.jpg' ),
        'tmt_hiw_community_image_url' => tmt_asset_uri( 'community-volunteers.jpg' ),
        'tmt_intake_video_url'        => tmt_asset_uri( 'apply-loop.mp4' ),
    );

    $retailer_defaults = tmt_get_retailer_defaults();
    $extra_grid_rows   = (int) get_option( 'tmt_extra_grid_rows', 1 );
    $max_retailers     = 8 + ( $extra_grid_rows * 3 );
    for ( $i = 1; $i <= $max_retailers; $i++ ) {
        $media_pairs[ "tmt_retailer_{$i}_logo" ] = "tmt_retailer_{$i}_logo_url";
        $logo_file = isset( $retailer_defaults[ $i ]['logo_file'] ) ? $retailer_defaults[ $i ]['logo_file'] : '';
        $media_defaults[ "tmt_retailer_{$i}_logo_url" ] = $logo_file ? tmt_asset_uri( $logo_file ) : '';
    }

    $cause_defaults    = tmt_get_cause_defaults();
    $extra_column_rows = (int) get_option( 'tmt_extra_column_rows', 3 );
    $max_causes        = 4 + ( $extra_column_rows * 2 );
    for ( $i = 1; $i <= $max_causes; $i++ ) {
        $media_pairs[ "tmt_cause_{$i}_image" ] = "tmt_cause_{$i}_image_url";
        $image_file = isset( $cause_defaults[ $i ]['image_file'] ) ? $cause_defaults[ $i ]['image_file'] : '';
        $media_defaults[ "tmt_cause_{$i}_image_url" ] = $image_file ? tmt_asset_uri( $image_file ) : '';
    }

    $divider_defaults = tmt_get_divider_defaults();
    $extra_gallery    = (int) get_option( 'tmt_extra_gallery_items', 3 );
    $max_dividers     = 3 + $extra_gallery;
    for ( $i = 1; $i <= $max_dividers; $i++ ) {
        $media_pairs[ "tmt_divider_{$i}_image" ] = "tmt_divider_{$i}_image_url";
        $image_file = isset( $divider_defaults[ $i ]['image_file'] ) ? $divider_defaults[ $i ]['image_file'] : '';
        $media_defaults[ "tmt_divider_{$i}_image_url" ] = $image_file ? tmt_asset_uri( $image_file ) : '';
    }

    wp_localize_script( 'tmt-customizer-controls', 'TMT_CUSTOMIZER_CONTROLS', array(
        'mediaPairs'    => $media_pairs,
        'mediaDefaults' => $media_defaults,
    ) );
}
add_action( 'customize_controls_enqueue_scripts', 'tmt_customizer_controls_enqueue' );

function tmt_customizer_preview_enqueue() {
    wp_enqueue_script( 'customize-preview' );
    wp_enqueue_script(
        'tmt-customizer-preview',
        get_template_directory_uri() . '/assets/js/customizer-preview.js',
        array( 'customize-preview', 'jquery' ),
        filemtime( get_template_directory() . '/assets/js/customizer-preview.js' ),
        true
    );

    wp_localize_script( 'tmt-customizer-preview', 'TMT_CUSTOMIZER_PREVIEW', array(
        'colorMap' => array(
            'tmt_color_ink'    => '--ink',
            'tmt_color_canvas' => '--canvas',
            'tmt_color_sand'   => '--sand',
            'tmt_color_clay'   => '--clay',
            'tmt_color_give'   => '--give',
            'tmt_color_lilac'  => '--lilac',
            'tmt_color_rule'   => '--rule',
        ),
        'colorHexDefaults'   => tmt_get_color_hex_defaults(),
        'colorOklchDefaults' => tmt_get_color_oklch_defaults(),
    ) );
}
add_action( 'customize_preview_init', 'tmt_customizer_preview_enqueue' );

function tmt_handle_contact_form() {
    check_ajax_referer( 'tmt_forms', 'nonce' );

    $name    = sanitize_text_field( $_POST['name'] ?? '' );
    $email   = sanitize_email( $_POST['email'] ?? '' );
    $message = sanitize_textarea_field( $_POST['message'] ?? '' );

    if ( empty( $name ) || empty( $email ) || empty( $message ) ) {
        wp_send_json_error( array( 'message' => 'All fields are required.' ) );
    }

    $recipient = get_theme_mod( 'tmt_contact_email', get_option( 'admin_email' ) );
    $sent      = wp_mail(
        $recipient,
        'New contact form submission from ' . $name,
        "Name: $name\nEmail: $email\n\nMessage:\n$message",
        array( 'Content-Type: text/plain; charset=UTF-8', "Reply-To: $email" )
    );

    if ( $sent ) {
        wp_send_json_success( array( 'message' => 'Message sent successfully.' ) );
    } else {
        wp_send_json_error( array( 'message' => 'Failed to send message.' ) );
    }
}
add_action( 'wp_ajax_tmt_contact_form', 'tmt_handle_contact_form' );
add_action( 'wp_ajax_nopriv_tmt_contact_form', 'tmt_handle_contact_form' );

require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/admin/settings-page.php';
