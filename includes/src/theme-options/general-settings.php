<?php
/**
* New Theme Options page rendered with the built-in Vue interface.
*
* @since 3.0.0
*/

namespace MyListing\Src\Theme_Options;

use MyListing\Src\Traits\Instantiatable;

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

class General_Settings {
        use Instantiatable;

        const NONCE_ACTION = 'mylisting_theme_options';

        /**
        * Hydrate AJAX select fields with their currently selected labels, so the UI doesn't
        * briefly display raw IDs before the async lookup completes.
        */
        private function hydrate_ajax_select_selected_items( array $fields, array $values ) {
                foreach ( $fields as $i => $field ) {
                        if ( empty( $field['key'] ) || empty( $field['type'] ) ) {
                                continue;
                        }

                        if ( ! in_array( $field['type'], [ 'ajax_select', 'taxonomy_terms' ], true ) ) {
                                continue;
                        }

                        $current = $values[ $field['key'] ] ?? null;
                        $items   = $field['type'] === 'ajax_select'
                                ? $this->get_ajax_select_selected_items( $field, $current )
                                : $this->get_taxonomy_terms_selected_items( $field, $current );

                        if ( ! empty( $items ) ) {
                                $fields[ $i ]['selected_items'] = $items;
                        }
                }

                return $fields;
        }

        /**
        * Resolve selected item labels for an ajax_select field based on its ajax_url and params.
        *
        * Returns an array of `[ 'id' => string, 'text' => string ]`.
        */
        private function get_ajax_select_selected_items( array $field, $value ) {
                $is_multiple = ! empty( $field['multiple'] );
                $ajax_url    = isset( $field['ajax_url'] ) ? $field['ajax_url'] : '';
                $ajax_params = isset( $field['ajax_params'] ) && is_array( $field['ajax_params'] ) ? $field['ajax_params'] : [];

                $ids = [];
                if ( $is_multiple ) {
                        $ids = is_array( $value ) ? $value : ( ( $value === null || $value === '' ) ? [] : [ $value ] );
                } else {
                        $ids = ( $value === null || $value === '' ) ? [] : [ $value ];
                }

                $ids = array_values( array_filter( array_map( 'strval', array_filter( $ids, 'is_scalar' ) ) ) );
                if ( empty( $ids ) ) {
                        return [];
                }

                if ( $ajax_url === 'mylisting_list_terms' && ! empty( $ajax_params['taxonomy'] ) ) {
                        $taxonomy = sanitize_key( $ajax_params['taxonomy'] );
                        $term_ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
                        if ( empty( $term_ids ) ) {
                                return [];
                        }

                        $terms = get_terms( [
                                'taxonomy'   => $taxonomy,
                                'hide_empty' => false,
                                'include'    => $term_ids,
                                'orderby'    => 'include',
                                'number'     => count( $term_ids ),
                        ] );

                        if ( empty( $terms ) || is_wp_error( $terms ) ) {
                                return [];
                        }

                        $indexed = [];
                        foreach ( $terms as $term ) {
                                $indexed[ (int) $term->term_id ] = $term;
                        }

                        $items = [];
                        foreach ( $term_ids as $id ) {
                                if ( isset( $indexed[ $id ] ) ) {
                                        $items[] = [ 'id' => (string) $id, 'text' => $indexed[ $id ]->name ];
                                }
                        }

                        return $items;
                }

                if ( $ajax_url === 'mylisting_list_posts' && ! empty( $ajax_params['post_type'] ) ) {
                        $post_type = $ajax_params['post_type'];
                        if ( is_array( $post_type ) ) {
                                $post_type = array_map( 'sanitize_key', $post_type );
                        } else {
                                $post_type = [ sanitize_key( $post_type ) ];
                        }

                        $post_ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
                        if ( empty( $post_ids ) ) {
                                return [];
                        }

                        $posts = get_posts( [
                                'post_type'        => $post_type,
                                'post_status'      => 'publish',
                                'post__in'         => $post_ids,
                                'orderby'          => 'post__in',
                                'posts_per_page'   => count( $post_ids ),
                                'suppress_filters' => false,
                        ] );

                        if ( empty( $posts ) || is_wp_error( $posts ) ) {
                                return [];
                        }

                        $indexed = [];
                        foreach ( $posts as $post ) {
                                $indexed[ (int) $post->ID ] = $post;
                        }

                        $items = [];
                        foreach ( $post_ids as $id ) {
                                if ( isset( $indexed[ $id ] ) ) {
                                        $items[] = [ 'id' => (string) $id, 'text' => $indexed[ $id ]->post_title ];
                                }
                        }

                        return $items;
                }

                return [];
        }

        /**
        * Resolve selected items for taxonomy_terms fields (token format: `taxonomy:term_id`).
        *
        * Returns an array of `[ 'token' => string, 'taxonomy' => string, 'id' => string, 'text' => string ]`.
        */
        private function get_taxonomy_terms_selected_items( array $field, $value ) {
                $tokens = is_array( $value ) ? $value : ( ( $value === null || $value === '' ) ? [] : [ $value ] );
                $tokens = array_values( array_filter( array_map( 'strval', array_filter( $tokens, 'is_scalar' ) ) ) );
                if ( empty( $tokens ) ) {
                        return [];
                }

                $default_taxonomy = isset( $field['default_taxonomy'] ) ? sanitize_key( $field['default_taxonomy'] ) : '';
                if ( ! $default_taxonomy && isset( $field['taxonomies'] ) && is_array( $field['taxonomies'] ) ) {
                        $keys = array_keys( $field['taxonomies'] );
                        $default_taxonomy = ! empty( $keys ) ? (string) $keys[0] : '';
                }

                $by_tax = [];
                $order  = [];
                $seen   = [];
                foreach ( $tokens as $token ) {
                        $token = trim( $token );
                        if ( $token === '' ) {
                                continue;
                        }

                        $tax = '';
                        $id  = 0;

                        if ( strpos( $token, ':' ) !== false ) {
                                $parts = explode( ':', $token, 2 );
                                $tax   = sanitize_key( $parts[0] );
                                $id    = absint( $parts[1] );
                        } else {
                                // Back-compat: accept raw IDs/slugs under the default taxonomy.
                                $tax = $default_taxonomy;
                                if ( is_numeric( $token ) ) {
                                        $id = absint( $token );
                                } else {
                                        $slug = sanitize_title( $token );
                                        if ( $tax && $slug ) {
                                                $term = get_term_by( 'slug', $slug, $tax );
                                                $id = $term && ! is_wp_error( $term ) ? absint( $term->term_id ) : 0;
                                        }
                                }
                        }

                        if ( ! $tax || ! $id ) {
                                continue;
                        }

                        $key = "{$tax}:{$id}";
                        if ( isset( $seen[ $key ] ) ) {
                                continue;
                        }
                        $seen[ $key ] = true;

                        $by_tax[ $tax ][] = $id;
                        $order[] = $key;
                }

                if ( empty( $order ) ) {
                        return [];
                }

                $indexed = [];
                foreach ( $by_tax as $taxonomy => $ids ) {
                        $terms = get_terms( [
                                'taxonomy'   => $taxonomy,
                                'hide_empty' => false,
                                'include'    => $ids,
                                'orderby'    => 'include',
                                'number'     => count( $ids ),
                        ] );

                        if ( empty( $terms ) || is_wp_error( $terms ) ) {
                                continue;
                        }

                        foreach ( $terms as $term ) {
                                $indexed[ "{$taxonomy}:{$term->term_id}" ] = $term;
                        }
                }

                $items = [];
                foreach ( $order as $key ) {
                        if ( isset( $indexed[ $key ] ) ) {
                                $term = $indexed[ $key ];
                                $items[] = [
                                        'token'    => $key,
                                        'taxonomy' => $term->taxonomy,
                                        'id'       => (string) $term->term_id,
                                        'text'     => $term->name,
                                ];
                        }
                }

                return $items;
        }

        public function __construct() {
                add_action( 'after_setup_theme', [ $this, 'register_menu' ] );
                add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ], 40 );
                add_action( 'wp_ajax_mylisting_theme_options_save', [ $this, 'save_settings' ] );
        }

        /**
        * Register the Theme Options menu once translations are loaded.
        */
        public function register_menu() {
                add_action( 'admin_menu', [ $this, 'register_page' ], 20 );
        }

        /**
        * Register the Theme Tools > Theme Options submenu.
        */
        public function register_page() {
                add_submenu_page(
                        'case27/tools.php',
                        __( 'Theme Options', 'my-listing' ),
                        __( 'Theme Options', 'my-listing' ),
                        'manage_options',
                        'theme-general-settings',
                        [ $this, 'render_page' ]
                );
        }

        /**
        * Render the application container.
        */
        public function render_page() {
                require locate_template( 'templates/admin/theme-options/general-settings.php' );
        }

        /**
        * Localise configuration and enqueue assets for the new interface.
        */
        public function enqueue_assets() {
                if ( empty( $_GET['page'] ) || $_GET['page'] !== 'theme-general-settings' ) { // phpcs:ignore WordPress.Security.NonceVerification
                        return;
                }

                wp_enqueue_media();
                wp_enqueue_style( 'wp-color-picker' );
                wp_enqueue_style( 'mylisting-admin-theme-options' );
                wp_enqueue_script( 'wp-color-picker' );
                // Enable transparency slider for color pickers when requested by fields
                wp_enqueue_script( 'wp-color-picker-alpha' );

                // Load WP Code Editor (CodeMirror) for code fields in Custom Code tab.
                if ( function_exists( 'wp_enqueue_code_editor' ) ) {
                        // Enqueue modes we need. Settings return value can be used if needed by JS.
                        wp_enqueue_code_editor( [ 'type' => 'text/css' ] );
                        wp_enqueue_code_editor( [ 'type' => 'application/javascript' ] );
                        wp_enqueue_code_editor( [ 'type' => 'text/html' ] );
                }

                $repository = Settings_Repository::instance();

                $config = [
                        'nonce'      => wp_create_nonce( self::NONCE_ACTION ),
                        'tabs'       => $repository->get_tabs(),
                        'fields'     => [],
                        'layouts'    => [],
                        'values'     => [],
                        'activeTab'  => 'general',
                        'endpoints'  => [
                                'save' => admin_url( 'admin-ajax.php?action=mylisting_theme_options_save' ),
                        ],
                        'strings'    => [
                                'save'        => __( 'Save changes', 'my-listing' ),
                                'saving'      => __( 'Saving...', 'my-listing' ),
                                'saved'       => __( 'Settings saved.', 'my-listing' ),
                                'error'       => __( 'Something went wrong while saving.', 'my-listing' ),
                                'selectImage' => __( 'Select image', 'my-listing' ),
                                'changeImage' => __( 'Change image', 'my-listing' ),
                                'removeImage' => __( 'Remove image', 'my-listing' ),
                                'noImage'     => __( 'No image selected', 'my-listing' ),
                                'chooseColor' => __( 'Choose color', 'my-listing' ),
                                'dismiss'     => __( 'Dismiss notice', 'my-listing' ),
                        ],
                ];

                wp_enqueue_script( 'mylisting-admin-theme-options' );

                foreach ( $repository->get_tabs() as $tab ) {
                        $key = $tab['key'];
                        $fields = $repository->get_tab_fields( $key );
                        $config['layouts'][ $key ] = $repository->get_tab_layout( $key );

                        $values = $repository->get_values_for_editor( $key );
                        $fields = $this->hydrate_ajax_select_selected_items( $fields, $values );

                        $config['fields'][ $key ] = $fields;
                        $config['values'][ $key ] = $values;
                }

                wp_localize_script( 'mylisting-admin-theme-options', 'MyListingThemeOptionsConfig', $config );
        }

        /**
        * Persist settings updates from the Vue interface.
        */
        public function save_settings() {
                check_ajax_referer( self::NONCE_ACTION, 'nonce' );

                if ( ! current_user_can( 'manage_options' ) ) {
                        wp_send_json_error( [ 'message' => __( 'You are not allowed to perform this action.', 'my-listing' ) ], 403 );
                }

                $group = isset( $_POST['group'] ) ? sanitize_key( wp_unslash( $_POST['group'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
                if ( ! $group ) {
                        wp_send_json_error( [ 'message' => __( 'Invalid settings group.', 'my-listing' ) ] );
                }

                $raw_values = isset( $_POST['values'] ) ? wp_unslash( $_POST['values'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
                $values     = json_decode( $raw_values, true );

                if ( ! is_array( $values ) ) {
                        wp_send_json_error( [ 'message' => __( 'Invalid payload received.', 'my-listing' ) ] );
                }

                $repository = Settings_Repository::instance();
                if ( ! $repository->has_tab( $group ) ) {
                        wp_send_json_error( [ 'message' => __( 'Invalid settings group.', 'my-listing' ) ] );
                }

                $updated = $repository->update_group( $group, $values );
                if ( is_wp_error( $updated ) ) {
                        wp_send_json_error( [ 'message' => $updated->get_error_message() ] );
                }

                \MyListing\generate_dynamic_styles();

                wp_send_json_success( [
                        'values'  => $repository->get_values_for_editor( $group ),
                        'message' => __( 'Settings saved.', 'my-listing' ),
                ] );
        }
}
