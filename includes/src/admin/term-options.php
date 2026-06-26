<?php

namespace MyListing\Src\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use MyListing\Src\Traits\Instantiatable;

class Term_Options {
    use Instantiatable;

    /**
     * Taxonomies where native term options should be shown.
     *
     * @var array
     */
    protected $taxonomies = [];

    /**
     * Nonce settings used when saving term options.
     */
    protected $nonce_action = 'ml_save_term_options';
    protected $nonce_name   = 'ml_term_options_nonce';

    /**
     * Track whether helper scripts have been printed to avoid duplicates.
     *
     * @var bool
     */
    protected static $assets_enqueued = false;

    /**
     * Vue mount instructions for iconpicker fields.
     *
     * @var array
     */
    protected $iconpicker_mounts = [];

    /**
     * Ensure inline scripts are only printed once per request.
     *
     * @var bool
     */
    protected static $inline_scripts_printed = false;

    public function __construct() {
        $this->taxonomies = $this->get_supported_taxonomies();

        if ( empty( $this->taxonomies ) ) {
            return;
        }

        foreach ( $this->taxonomies as $taxonomy ) {
            add_action( "{$taxonomy}_add_form_fields", [ $this, 'add_form_fields' ] );
            add_action( "{$taxonomy}_edit_form_fields", [ $this, 'edit_form_fields' ], 10, 2 );

            add_action(
                "created_{$taxonomy}",
                function ( $term_id, $tt_id ) use ( $taxonomy ) {
                    $this->save_term_options( $term_id, $tt_id, $taxonomy );
                },
                10,
                2
            );

            add_action(
                "edited_{$taxonomy}",
                function ( $term_id, $tt_id ) use ( $taxonomy ) {
                    $this->save_term_options( $term_id, $tt_id, $taxonomy );
                },
                10,
                2
            );
        }

        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // Hide legacy ACF term field groups ("Term Options" and "Term Landing Page")
        add_filter( 'acf/load_field_groups', [ $this, 'filter_legacy_term_groups' ] );
    }

    /**
     * Determine which taxonomies should display the native term options UI.
     */
    protected function get_supported_taxonomies() {
        $base = [ 'job_listing_category', 'case27_job_listing_tags', 'region', 'category' ];

        if ( function_exists( 'mylisting_custom_taxonomies' ) ) {
            $custom = array_keys( (array) mylisting_custom_taxonomies( 'slug', 'slug' ) );
        } else {
            $custom = [];
        }

        $taxonomies = array_unique( array_filter( array_merge( $base, $custom ) ) );

        return (array) apply_filters( 'mylisting/admin/term-options/taxonomies', $taxonomies );
    }

    protected function get_default_values() {
        return [
            'icon_type'    => 'icon',
            'icon'         => '',
            'icon_image'   => 0,
            'color'        => '',
            'text_color'   => '',
            'image'        => 0,
            '_landing_page'=> 0,
            'listing_type' => [],
        ];
    }

    protected function get_term_values( $term_id ) {
        $values = $this->get_default_values();

        foreach ( array_keys( $values ) as $key ) {
            $stored = get_term_meta( $term_id, $key, true );

            if ( $key === 'listing_type' ) {
                $values[ $key ] = array_filter( array_map( 'absint', (array) $stored ) );
            } elseif ( in_array( $key, [ 'icon_image', 'image', '_landing_page' ], true ) ) {
                $values[ $key ] = absint( $stored );
            } else {
                $values[ $key ] = $stored;
            }
        }

        if ( ! in_array( $values['icon_type'], [ 'icon', 'image' ], true ) ) {
            $values['icon_type'] = 'icon';
        }

        $values['icon']       = is_string( $values['icon'] ) ? $values['icon'] : '';
        $values['color']      = is_string( $values['color'] ) ? $values['color'] : '';
        $values['text_color'] = is_string( $values['text_color'] ) ? $values['text_color'] : '';

        return $values;
    }

    public function add_form_fields( $taxonomy ) {
        if ( ! in_array( $taxonomy, $this->taxonomies, true ) ) {
            return;
        }

        $values = $this->get_default_values();
        $this->render_fields( 'add', $values, $taxonomy );
    }

    public function edit_form_fields( $term, $taxonomy ) {
        if ( ! in_array( $taxonomy, $this->taxonomies, true ) ) {
            return;
        }

        $values = $this->get_term_values( $term->term_id );
        $this->render_fields( 'edit', $values, $taxonomy );
    }

    protected function render_fields( $context, array $values, $taxonomy ) {
        if ( ! in_array( $context, [ 'add', 'edit' ], true ) ) {
            return;
        }

        if ( $context === 'add' ) {
            wp_nonce_field( $this->nonce_action, $this->nonce_name );
        } else {
            echo '<tr class="ml-term-field-hidden" style="display:none;"><td colspan="2">';
            wp_nonce_field( $this->nonce_action, $this->nonce_name );
            echo '</td></tr>';
        }

        $field_prefix = 'ml-term-' . sanitize_html_class( $taxonomy );
        $iconpicker_id = wp_unique_id( 'ml-term-iconpicker-' );
        $icon_value    = isset( $values['icon'] ) ? (string) $values['icon'] : '';

        $this->start_field( $context, 'icon-type', __( 'Icon Option', 'my-listing' ), "{$field_prefix}-icon-type" );
        ?>
        <fieldset class="ml-term-radio">
            <label><input type="radio" name="ml_term_options[icon_type]" value="icon" <?php checked( $values['icon_type'], 'icon' ); ?> /> <?php esc_html_e( 'Icon Font', 'my-listing' ); ?></label>
            <label><input type="radio" name="ml_term_options[icon_type]" value="image" <?php checked( $values['icon_type'], 'image' ); ?> /> <?php esc_html_e( 'Upload Image', 'my-listing' ); ?></label>
        </fieldset>
        <?php
        $this->end_field( $context );

        $this->start_field( $context, 'icon', __( 'Icon', 'my-listing' ), "{$field_prefix}-icon", 'ml-term-when-icon' );
        ?>
        <p class="description"><?php esc_html_e( 'Select the icon to use for this term.', 'my-listing' ); ?></p>
        <div id="<?php echo esc_attr( $iconpicker_id ); ?>" class="ml-term-iconpicker-wrapper">
            <input type="hidden" name="ml_term_options[icon]" v-model="value" />
            <iconpicker v-model="value"></iconpicker>
        </div>
        <?php
        $this->mount_iconpicker( $iconpicker_id, $icon_value );
        $this->end_field( $context );

        $icon_image_id = isset( $values['icon_image'] ) ? absint( $values['icon_image'] ) : 0;
        $this->start_field( $context, 'icon-image', __( 'Icon Image', 'my-listing' ), "{$field_prefix}-icon-image", 'ml-term-when-image' );
        $this->render_media_field(
            'icon_image',
            $icon_image_id,
            __( 'Shown when Icon Option is set to Upload Image.', 'my-listing' )
        );
        $this->end_field( $context );

        $this->start_field( $context, 'color', __( 'Color', 'my-listing' ), "{$field_prefix}-color" );
        ?>
        <input type="text" id="<?php echo esc_attr( "{$field_prefix}-color" ); ?>" class="ml-term-color-field" name="ml_term_options[color]" value="<?php echo esc_attr( $values['color'] ); ?>" data-default-color="" />
        <?php
        $this->end_field( $context );

        $this->start_field( $context, 'text-color', __( 'Text Color', 'my-listing' ), "{$field_prefix}-text-color" );
        ?>
        <input type="text" id="<?php echo esc_attr( "{$field_prefix}-text-color" ); ?>" class="ml-term-color-field" name="ml_term_options[text_color]" value="<?php echo esc_attr( $values['text_color'] ); ?>" data-default-color="" />
        <?php
        $this->end_field( $context );

        $image_id = isset( $values['image'] ) ? absint( $values['image'] ) : 0;
        $this->start_field( $context, 'image', __( 'Image', 'my-listing' ), "{$field_prefix}-image" );
        $this->render_media_field(
            'image',
            $image_id,
            __( 'Used for category backgrounds in Explore and widgets.', 'my-listing' )
        );
        $this->end_field( $context );

        $this->start_field( $context, 'listing-type', __( 'Listing Type(s)', 'my-listing' ), "{$field_prefix}-listing-type" );
        $this->render_listing_type_field( (array) $values['listing_type'] );
        $this->end_field( $context );

        // Custom Landing Page (optional)
        $this->start_field( $context, 'landing-page', __( 'Custom Landing Page (optional)', 'my-listing' ), "{$field_prefix}-landing-page" );
        $this->render_landing_page_field( isset( $values['_landing_page'] ) ? absint( $values['_landing_page'] ) : 0 );
        $this->end_field( $context );
    }

    protected function start_field( $context, $field, $label = '', $id = '', $additional_classes = '' ) {
        $classes = trim( 'ml-term-field ml-term-field-' . sanitize_html_class( $field ) . ' ' . $additional_classes );

        if ( $context === 'add' ) {
            echo '<div class="form-field ' . esc_attr( $classes ) . '">';
            if ( $label ) {
                echo '<label for="' . esc_attr( $id ?: $field ) . '">' . esc_html( $label ) . '</label>';
            }
        } else {
            echo '<tr class="form-field ' . esc_attr( $classes ) . '">';
            echo '<th scope="row">';
            if ( $label ) {
                echo '<label for="' . esc_attr( $id ?: $field ) . '">' . esc_html( $label ) . '</label>';
            }
            echo '</th><td>';
        }
    }

    protected function end_field( $context ) {
        if ( $context === 'add' ) {
            echo '</div>';
        } else {
            echo '</td></tr>';
        }
    }

    protected function render_media_field( $key, $attachment_id = 0, $description = '' ) {
        $image_html    = $attachment_id ? wp_get_attachment_image( $attachment_id, 'thumbnail' ) : '';
        $select_label  = esc_html__( 'Select image', 'my-listing' );
        $change_label  = esc_html__( 'Change image', 'my-listing' );
        ?>
        <div class="ml-term-media-field" data-field="<?php echo esc_attr( $key ); ?>">
            <input type="hidden" name="ml_term_options[<?php echo esc_attr( $key ); ?>]" class="ml-term-media-input" value="<?php echo esc_attr( $attachment_id ); ?>" />
            <div class="ml-term-media-preview" <?php echo $image_html ? '' : 'style="display:none;"'; ?>>
                <?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
            <p>
                <button type="button" class="button ml-term-media-upload" data-select-label="<?php echo esc_attr( $select_label ); ?>" data-change-label="<?php echo esc_attr( $change_label ); ?>">
                    <?php echo $attachment_id ? $change_label : $select_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </button>
                <button type="button" class="button-link-delete ml-term-media-remove" <?php echo $attachment_id ? '' : 'style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'my-listing' ); ?></button>
            </p>
            <?php if ( $description ) : ?>
                <p class="description"><?php echo esc_html( $description ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    protected function render_listing_type_field( array $selected ) {
        $listing_types = get_posts( [
            'post_type'      => 'case27_listing_type',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );
        ?>
        <select name="ml_term_options[listing_type][]" class="ml-term-listing-types" multiple="multiple" data-placeholder="<?php esc_attr_e( 'All listing types', 'my-listing' ); ?>">
            <?php foreach ( $listing_types as $post ) : ?>
                <option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( in_array( $post->ID, $selected, true ), true ); ?>>
                    <?php echo esc_html( $post->post_title ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e( 'Choose the listing types where this term is available. Leave empty for all.', 'my-listing' ); ?></p>
        <?php
    }

    protected function render_landing_page_field( $selected_page_id = 0 ) {
        $page_select_params = c27()->encode_attr( [
            'post_type' => 'page',
            'item_key'  => 'ID',
        ] );
        ?>
        <select name="ml_term_options[_landing_page]" class="ml-term-landing-page custom-select" data-placeholder="<?php esc_attr_e( 'No custom page (default behavior)', 'my-listing' ); ?>"
            data-mylisting-ajax="true" data-mylisting-ajax-url="mylisting_list_posts"
            data-mylisting-ajax-params="<?php echo esc_attr( $page_select_params ); ?>"
        >
            <option></option>
            <?php if ( $selected_page_id && ( $title = get_the_title( $selected_page_id ) ) ) : ?>
                <option value="<?php echo esc_attr( $selected_page_id ); ?>" selected="selected"><?php echo esc_html( $title ); ?></option>
            <?php endif; ?>
        </select>
        <p class="description"><?php esc_html_e( 'If set, visitors clicking this term will be redirected to the selected page.', 'my-listing' ); ?></p>
        <?php
    }

    protected function mount_iconpicker( $element_id, $value ) {
        $this->iconpicker_mounts[] = [
            'selector' => '#' . $element_id,
            'value'    => (string) $value,
        ];

        $selector = wp_json_encode( '#' . $element_id );
        $value    = wp_json_encode( (string) $value );
        // Mount immediately if DOM is ready; otherwise, wait for DOMContentLoaded.
        $script   = "(function(){var init=function(){var el=document.querySelector(" . $selector . ");if(!el||typeof Vue==='undefined'){return;}new Vue({el:el,data:{value:" . $value . "}});};if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',init);}else{init();}})();";

        wp_add_inline_script( 'theme-script-main', $script );
    }

    public function enqueue_assets() {
        if ( self::$assets_enqueued ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || ! in_array( $screen->taxonomy ?? '', $this->taxonomies, true ) ) {
            return;
        }

        if ( ! in_array( $screen->base, [ 'term', 'edit-tags' ], true ) ) {
            return;
        }

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_media();

        wp_enqueue_script( 'vuejs' );
        wp_enqueue_script( 'theme-script-main' );

        self::$assets_enqueued = true;
    }

    public function save_term_options( $term_id, $tt_id = null, $taxonomy = '' ) {
        if ( ! in_array( $taxonomy, $this->taxonomies, true ) ) {
            return;
        }

        if ( empty( $_POST[ $this->nonce_name ] ) || ! wp_verify_nonce( $_POST[ $this->nonce_name ], $this->nonce_action ) ) {
            return;
        }

        if ( empty( $_POST['ml_term_options'] ) || ! is_array( $_POST['ml_term_options'] ) ) {
            $data = [];
        } else {
            $data = json_decode( wp_json_encode( wp_unslash( $_POST['ml_term_options'] ) ), true );
        }

        $icon_type = isset( $data['icon_type'] ) && $data['icon_type'] === 'image' ? 'image' : 'icon';
        update_term_meta( $term_id, 'icon_type', $icon_type );

        $icon = isset( $data['icon'] ) ? sanitize_text_field( $data['icon'] ) : '';
        if ( $icon ) {
            update_term_meta( $term_id, 'icon', $icon );
        } else {
            delete_term_meta( $term_id, 'icon' );
        }

        $icon_image = isset( $data['icon_image'] ) ? absint( $data['icon_image'] ) : 0;
        if ( $icon_image ) {
            update_term_meta( $term_id, 'icon_image', $icon_image );
        } else {
            delete_term_meta( $term_id, 'icon_image' );
        }

        $image = isset( $data['image'] ) ? absint( $data['image'] ) : 0;
        if ( $image ) {
            update_term_meta( $term_id, 'image', $image );
        } else {
            delete_term_meta( $term_id, 'image' );
        }

        $landing_page = isset( $data['_landing_page'] ) ? absint( $data['_landing_page'] ) : 0;
        if ( $landing_page ) {
            update_term_meta( $term_id, '_landing_page', $landing_page );
        } else {
            delete_term_meta( $term_id, '_landing_page' );
        }

        $color = isset( $data['color'] ) ? sanitize_text_field( $data['color'] ) : '';
        if ( $color ) {
            update_term_meta( $term_id, 'color', $color );
        } else {
            delete_term_meta( $term_id, 'color' );
        }

        $text_color = isset( $data['text_color'] ) ? sanitize_text_field( $data['text_color'] ) : '';
        if ( $text_color ) {
            update_term_meta( $term_id, 'text_color', $text_color );
        } else {
            delete_term_meta( $term_id, 'text_color' );
        }

        $listing_types = [];
        if ( ! empty( $data['listing_type'] ) ) {
            $listing_types = array_filter( array_map( 'absint', (array) $data['listing_type'] ) );
        }

        if ( ! empty( $listing_types ) ) {
            update_term_meta( $term_id, 'listing_type', $listing_types );
        } else {
            delete_term_meta( $term_id, 'listing_type' );
        }

        do_action( 'mylisting/admin/term-options/saved', $term_id, $taxonomy, [
            'icon_type'    => $icon_type,
            'icon'         => $icon,
            'icon_image'   => $icon_image,
            'color'        => $color,
            'text_color'   => $text_color,
            'image'        => $image,
            '_landing_page'=> $landing_page,
            'listing_type' => $listing_types,
        ] );
    }

    /**
     * Disable legacy ACF term groups by removing their location rules,
     * since these have been replaced with the native Term Options UI.
     *
     */
    public function filter_legacy_term_groups( $groups ) {
        if ( ! is_admin() || ! did_action( 'current_screen' ) ) {
            return $groups;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

        if ( ! $screen || ! isset( $screen->taxonomy ) ) {
            return $groups;
        }

        $taxonomy = (string) $screen->taxonomy;

        if ( ! $taxonomy || ! in_array( $taxonomy, (array) $this->taxonomies, true ) ) {
            return $groups;
        }

        if ( isset( $screen->base ) && ! in_array( $screen->base, [ 'term', 'edit-tags' ], true ) ) {
            return $groups;
        }

        $legacy_keys = [ 'group_595b74ad4e53f', 'group_5b4158a7bf983' ];

        $filtered = array_filter(
            (array) $groups,
            static function( $group ) use ( $legacy_keys ) {
                if ( ! is_array( $group ) ) {
                    return true;
                }

                $key = $group['key'] ?? '';

                return ! in_array( $key, $legacy_keys, true );
            }
        );

        return array_values( $filtered );
    }
}
