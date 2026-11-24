<?php
/**
 * Plugin Name: IONOS WordPress Template Guard
 * Description: Temporary mitigation for CVE-2026-87902. Remove after updating WordPress and verifying the upstream fix.
 * Version: 1.0.1
 * Requires at least: 4.7
 * Requires PHP: 5.6
 * License: GPL-2.0-or-later
 *
 * Install this file directly in wp-content/mu-plugins/.
 * Uses PHP's already parsed fields; never reads php://input or uploaded files.
 * This is not a replacement for the WordPress core update and cannot intercept
 * arbitrary PHP include/require calls made directly by another plugin/theme.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class IONOS_WP_Template_Guard_87902
{
    /** Register before ordinary plugins and themes load. */
    public static function register()
    {
        // MU-plugins load before wp_magic_quotes(). PHP has already decoded
        // query/form keys, parsed multipart fields, and applied its input limits.
        self::check_query_vars($_GET);
        self::check_query_vars($_POST);

        // Also inspect pagename derived from permalink rewrites or query filters.
        add_filter('request', array(__CLASS__, 'check_query_vars'), PHP_INT_MAX);

        // get_page_template() has already built its decoded and original names;
        // this filter runs before locate_template(), including on WordPress 4.7.
        add_filter('page_template_hierarchy', array(__CLASS__, 'check_candidates'), PHP_INT_MAX);

        // Check paths returned by page/template filters before normal inclusion.
        add_filter('page_template', array(__CLASS__, 'check_template_path'), PHP_INT_MAX);
        add_filter('template_include', array(__CLASS__, 'check_template_include'), PHP_INT_MAX);

        // Available since WordPress 6.1. On older versions the page-template
        // guards above still work, but direct load_template() calls lack this hook.
        add_action('wp_before_load_template', array(__CLASS__, 'check_template_path'), -PHP_INT_MAX, 1);
    }

    private static function block()
    {
        if (!headers_sent()) {
            status_header(403);
            nocache_headers();
            header('Content-Type: text/plain; charset=utf-8');
        }
        exit("Forbidden\n");
    }

    private static function has_traversal($path)
    {
        // Same traversal shape as the upstream fix, including backslashes and
        // components containing extra dots or trailing spaces.
        return 0 !== preg_match('~(?:^|/)\.\.[. ]*(?:/|$)~', wp_normalize_path($path));
    }

    private static function check_name($name)
    {
        if (!is_string($name) || strlen($name) > 4096 || false !== strpos($name, "\0")) {
            self::block();
        }
        if (0 !== validate_file($name) || self::has_traversal($name)) {
            self::block();
        }
    }

    public static function check_query_vars($vars)
    {
        if (!is_array($vars)) {
            self::block();
        }
        if (!array_key_exists('pagename', $vars)) {
            return $vars;
        }

        $name = $vars['pagename'];
        for ($depth = 0; $depth <= 4; ++$depth) {
            self::check_name($name);
            $decoded = urldecode($name);
            if ($decoded === $name) {
                return $vars;
            }
            if (4 === $depth) {
                self::block();
            }
            $name = $decoded;
        }

        return $vars;
    }

    public static function check_candidates($templates)
    {
        if (!is_array($templates)) {
            self::block();
        }
        foreach ($templates as $name) {
            // Inspect actual filesystem candidates; do not decode them again.
            self::check_name($name);
        }
        return $templates;
    }

    public static function check_template_include($path)
    {
        // WordPress accepts template wrappers implementing __toString(), as
        // used by Cherry Framework. Resolve once and return the checked string
        // so inclusion cannot trigger a second conversion with another result.
        if (is_object($path) && method_exists($path, '__toString')) {
            $path = (string) $path;
        }
        return self::check_template_path($path);
    }

    public static function check_template_path($path)
    {
        // WordPress filters may return an empty value to suppress inclusion.
        if (false === $path || null === $path || '' === $path) {
            return $path;
        }
        if (!is_string($path) || false !== strpos($path, "\0")) {
            self::block();
        }

        // Match the upstream traversal-specific restriction. Ordinary absolute
        // plugin templates and block-theme template-canvas.php remain supported.
        if (!self::has_traversal($path)) {
            return $path;
        }

        $resolved = realpath($path);
        if (false === $resolved) {
            self::block();
        }
        $resolved = wp_normalize_path($resolved);
        $stylesheet = get_stylesheet_directory();
        $template = get_template_directory();
        $roots = array($stylesheet, $template, ABSPATH . WPINC . '/theme-compat');

        // Preserve the nested-theme compatibility exceptions in the core fix.
        if (false !== strpos(get_stylesheet(), '/')) {
            $roots[] = dirname($stylesheet);
        }
        if (false !== strpos(get_template(), '/')) {
            $roots[] = dirname($template);
        }

        foreach ($roots as $root) {
            $root = realpath($root);
            if (false !== $root) {
                // The trailing slash prevents /theme-other matching /theme.
                $root = trailingslashit(wp_normalize_path($root));
                if (0 === strpos($resolved, $root)) {
                    return $path;
                }
            }
        }
        self::block();
    }
}

IONOS_WP_Template_Guard_87902::register();
