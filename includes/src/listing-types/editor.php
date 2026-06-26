<?php

namespace MyListing\Src\Listing_Types;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Editor {
    use \MyListing\Src\Traits\Instantiatable;

    /**
     * Used to cache method return values for multiple calls.
     *
     * @since 2.2
     */
    private $cache = [];

	public function __construct() {
        if ( ! is_admin() ) {
            return;
        }

        Revisions::instance();
        add_action( 'load-post.php', [ $this, 'init_metabox' ] );
        add_action( 'load-post-new.php', [ $this, 'init_metabox' ] );
        add_action( 'admin_notices', [ $this, 'regenerate_preview_cards_notice' ], 1000 );
	}

	public function init_metabox() {
        $screen = get_current_screen();
        if ( ! ( $screen && $screen->id === 'case27_listing_type' ) ) {
            return;
        }

        add_action( 'add_meta_boxes', [ $this, 'add_metabox' ] );
        add_action( 'save_post', [ $this, 'save_metabox' ], 10, 2 );

        // Native replacement for ACF "Listing Type" group (default images) — register side metabox and save handler.
        add_action( 'add_meta_boxes', [ $this, 'add_defaults_metabox' ] );
        add_action( 'save_post', [ $this, 'save_defaults_metabox' ], 10, 2 );

        // @todo: relocate, maybe add a hook for each filter to pass data to JS
        add_filter( 'mylisting/type-editor:config', function( $config ) {
            $config['recur_filter_ranges'] = apply_filters( 'mylisting/filters/recurring-date:ranges', [
                'all' => 'Any day',
                'today' => 'Today',
                'tomorrow' => 'Tomorrow',
                'this-week' => 'This week',
                'this-weekend' => 'This weekend',
                'next-week' => 'Next week',
                'this-month' => 'This month',
                'next-month' => 'Next month',
                'any' => 'Any day (including past days)',
            ] );

            return $config;
        } );
	}

	/**
     * Add a custom metabox in `case27_listing_type` post types to
     * render the listing type editor in.
     *
     * @since 1.0
     */
    public function add_metabox() {
        add_meta_box(
            'case27-listing-type-options',
            __( 'Listing Type Options', 'my-listing' ),
            function( $post ) {
                wp_nonce_field( 'save_type_editor', '_themenonce' );
                require_once locate_template( 'includes/src/listing-types/views/metabox.php' );
            },
            'case27_listing_type',
            'advanced',
            'high'
        );
    }



    /**
     * Register a side metabox for default assets: Logo, Map Marker, Cover Image.
     */
    public function add_defaults_metabox() {
        add_meta_box(
            'ml-listing-type-defaults',
            _x( 'Listing Type Defaults', 'listing type defaults metabox title', 'my-listing' ),
            [ $this, 'render_defaults_metabox' ],
            'case27_listing_type',
            'side',
            'default'
        );

        // Hide legacy ACF group metabox if present (to prevent duplicate UI).
        remove_meta_box( 'acf-group_5a7c64ac11a85', 'case27_listing_type', 'side' );
    }

    /**
     * Render the defaults metabox (native replacement for ACF group_5a7c64ac11a85).
     */
    public function render_defaults_metabox( $post ) {
        if ( ! ( $post && $post->post_type === 'case27_listing_type' ) ) {
            return;
        }

        // Ensure media modal is available.
        if ( function_exists( 'wp_enqueue_media' ) ) { wp_enqueue_media(); }

        // Nonce for saving.
        wp_nonce_field( 'ml_listing_type_defaults_save', 'ml_listing_type_defaults_nonce' );

        $default_logo        = absint( get_post_meta( $post->ID, 'default_logo', true ) );
        $default_map_marker  = absint( get_post_meta( $post->ID, 'default_map_marker', true ) );
        $default_cover_image = absint( get_post_meta( $post->ID, 'default_cover_image', true ) );

        // Helper to render one image control.
        $render_image_control = function( $field_id, $label, $value ) {
            $img_html = $value ? wp_get_attachment_image( $value, 'thumbnail', false ) : '';
            echo '<p style="margin-bottom:16px">';
            echo '<label class="ml-backend-label" for="' . esc_attr( $field_id ) . '">' . esc_html( $label ) . '</label>';
            echo '<input type="hidden" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_id ) . '" value="' . esc_attr( $value ) . '" />';
            echo '<div class="ml-image-preview" data-target="' . esc_attr( $field_id ) . '">' . ( $img_html ?: '<em style="color:#666">' . esc_html__( 'No image selected', 'my-listing' ) . '</em>' ) . '</div>';
            echo '<button type="button" class="button select-ml-image" data-target="' . esc_attr( $field_id ) . '">' . esc_html__( 'Select Image', 'my-listing' ) . '</button> ';
            echo '<button type="button" class="button link-button clear-ml-image" data-target="' . esc_attr( $field_id ) . '">' . esc_html__( 'Clear', 'my-listing' ) . '</button>';
            echo '</p>';
        };

        $render_image_control( 'default_logo', _x( 'Default Logo', 'listing type defaults', 'my-listing' ), $default_logo );
        echo '<p class="description">' . esc_html__( "This image will be used when listing logo isn't present. Leave empty to hide the logo completely.", 'my-listing' ) . '</p>';

        $render_image_control( 'default_map_marker', _x( 'Default Map Marker', 'listing type defaults', 'my-listing' ), $default_map_marker );
        echo '<p class="description">' . esc_html__( 'This image will be used as the default map marker if listing logo is not present', 'my-listing' ) . '</p>';

        $render_image_control( 'default_cover_image', _x( 'Default Cover Image', 'listing type defaults', 'my-listing' ), $default_cover_image );
        echo '<p class="description">' . esc_html__( "This image will be used when listing cover isn't present. Leave empty to hide the cover completely.", 'my-listing' ) . '</p>';

        // Inline script to handle media selection.
        ?>
        <script type="text/javascript">
        jQuery(function($){
            function mlSelectImage(target){
                var frame = wp.media({ title: '<?php echo esc_js( _x( 'Select Image', 'media modal', 'my-listing' ) ); ?>', multiple: false });
                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#'+target).val( attachment.id );
                    var html = attachment.sizes && attachment.sizes.thumbnail ? '<img src="'+attachment.sizes.thumbnail.url+'" />' : '<img src="'+attachment.url+'" />';
                    $('.ml-image-preview[data-target="'+target+'"]').html( html );
                });
                frame.open();
            }
            $('.select-ml-image').on('click', function(e){ e.preventDefault(); mlSelectImage($(this).data('target')); });
            $('.clear-ml-image').on('click', function(e){ e.preventDefault(); var t=$(this).data('target'); $('#'+t).val(''); $('.ml-image-preview[data-target="'+t+'"]').html('<em style="color:#666"><?php echo esc_js( __( 'No image selected', 'my-listing' ) ); ?></em>'); });
        });
        </script>
        <?php
    }

    /**
     * Save the values from the defaults metabox.
     */
    public function save_defaults_metabox( $post_id, $post ) {
        if ( ! ( $post && $post->post_type === 'case27_listing_type' ) ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reading nonce from $_POST to verify it.
        if ( empty( $_POST['ml_listing_type_defaults_nonce'] ) || ! wp_verify_nonce( $_POST['ml_listing_type_defaults_nonce'], 'ml_listing_type_defaults_save' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above; safe to read $_POST values below.
        $default_logo        = isset( $_POST['default_logo'] ) ? absint( $_POST['default_logo'] ) : 0;
        $default_map_marker  = isset( $_POST['default_map_marker'] ) ? absint( $_POST['default_map_marker'] ) : 0;
        $default_cover_image = isset( $_POST['default_cover_image'] ) ? absint( $_POST['default_cover_image'] ) : 0;
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        // Validate attachment IDs (if provided), otherwise clear.
        foreach ( [ 'default_logo' => $default_logo, 'default_map_marker' => $default_map_marker, 'default_cover_image' => $default_cover_image ] as $meta_key => $att_id ) {
            if ( $att_id && get_post_type( $att_id ) !== 'attachment' ) { $att_id = 0; }
            if ( $att_id ) { update_post_meta( $post_id, $meta_key, $att_id ); } else { delete_post_meta( $post_id, $meta_key ); }
        }
    }

    /**
     * Save the listing type configuration on post save.
     *
     * @since 1.0
     */
    public function save_metabox( $post_id, $post ) {
        // Add nonce for security and authentication.
        $nonce_name   = isset( $_POST['_themenonce'] ) ? $_POST['_themenonce'] : '';
        $nonce_action = 'save_type_editor';

        // Check if nonce is set and valid.
        if ( ! ( isset( $nonce_name ) && wp_verify_nonce( $nonce_name, $nonce_action ) ) ) {
            return;
        }

        // Check if user has permissions to save data.
        if ( ! current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
            return;
        }

        do_action( 'mylisting/admin/types/before-update', $post );

        // Fields TAB
        if ( ! empty( $_POST['case27_listing_type_fields'] ) ) {
            $decoded_fields = json_decode( stripslashes( $_POST['case27_listing_type_fields'] ), true );

            if ( json_last_error() === JSON_ERROR_NONE ) {
                // set field priorities to preserve order set in listing type editor through drag&drop.
                $updated_fields = [];
                foreach ( (array) $decoded_fields as $i => $field ) {
                    $field['priority'] = ($i + 1);
                    $updated_fields[ $field['slug'] ] = (array) $field;
                }
                update_post_meta( $post_id, 'case27_listing_type_fields', wp_slash( serialize( $updated_fields ) ) );
            }
        }

        // Single Page TAB
        if ( ! empty( $_POST['case27_listing_type_single_page_options'] ) ) {
            $options = (array) json_decode( stripslashes( $_POST['case27_listing_type_single_page_options'] ), true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                update_post_meta( $post_id, 'case27_listing_type_single_page_options', wp_slash( serialize( $options ) ) );
            }
        }

        // Result Template TAB
        if ( ! empty( $_POST['case27_listing_type_result_template'] ) ) {
            $result_template = (array) json_decode( stripslashes( $_POST['case27_listing_type_result_template'] ), true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $cache_enabled = (bool) get_option( 'mylisting_cache_previews' );
                $old_result_template = get_post_meta( $post_id, 'case27_listing_type_result_template', true );
                if ( $cache_enabled && serialize( $result_template ) !== $old_result_template ) {
                    add_filter( 'redirect_post_location', function( $location ) {
                        return add_query_arg( [ 'regen_previews' => 1 ], $location );
                    } );
                }

                update_post_meta( $post_id, 'case27_listing_type_result_template', wp_slash( serialize( $result_template ) ) );
            }
        }

        // Search Forms TAB
        if ( ! empty( $_POST['case27_listing_type_search_page'] ) ) {
            $search_forms = (array) json_decode( stripslashes( $_POST['case27_listing_type_search_page'] ), true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                update_post_meta( $post_id, 'case27_listing_type_search_page', wp_slash( serialize( $search_forms ) ) );
            }
        }

        // Settings TAB
        if ( ! empty( $_POST['case27_listing_type_settings_page'] ) ) {
            $settings_page = (array) json_decode( stripslashes( $_POST['case27_listing_type_settings_page'] ), true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                update_post_meta( $post_id, 'case27_listing_type_settings_page', wp_slash( serialize( $settings_page ) ) );
            }
        }

        do_action( 'mylisting/admin/types/after-update', $post );
    }

    public function get_field_types() {
        if ( ! empty( $this->cache['field_types'] ) ) {
            return $this->cache['field_types'];
        }

        $fields = apply_filters( 'mylisting/listing-types/register-fields', [
            \MyListing\Src\Forms\Fields\Checkbox_Field::class,
            \MyListing\Src\Forms\Fields\Date_Field::class,
            \MyListing\Src\Forms\Fields\Email_Field::class,
            \MyListing\Src\Forms\Fields\File_Field::class,
            \MyListing\Src\Forms\Fields\Form_Heading_Field::class,
            \MyListing\Src\Forms\Fields\Links_Field::class,
            \MyListing\Src\Forms\Fields\Location_Field::class,
            \MyListing\Src\Forms\Fields\Multiselect_Field::class,
            \MyListing\Src\Forms\Fields\Number_Field::class,
            \MyListing\Src\Forms\Fields\Password_Field::class,
            \MyListing\Src\Forms\Fields\Radio_Field::class,
            \MyListing\Src\Forms\Fields\Related_Listing_Field::class,
            \MyListing\Src\Forms\Fields\Select_Field::class,
            \MyListing\Src\Forms\Fields\Select_Product_Field::class,
            \MyListing\Src\Forms\Fields\Select_Products_Field::class,
            \MyListing\Src\Forms\Fields\Term_Select_Field::class,
            \MyListing\Src\Forms\Fields\Text_Field::class,
            \MyListing\Src\Forms\Fields\Textarea_Field::class,
            \MyListing\Src\Forms\Fields\Texteditor_Field::class,
            \MyListing\Src\Forms\Fields\Url_Field::class,
            \MyListing\Src\Forms\Fields\Video_Url_Field::class,
            \MyListing\Src\Forms\Fields\Work_Hours_Field::class,
            \MyListing\Src\Forms\Fields\Wp_Editor_Field::class,
            \MyListing\Src\Forms\Fields\Recurring_Date_Field::class,
            \MyListing\Src\Forms\Fields\General_Repeater_Field::class,
        ] );

        foreach ( $fields as $field_class ) {
            if ( ! ( class_exists( $field_class ) && is_subclass_of( $field_class, \MyListing\Src\Forms\Fields\Base_Field::class ) ) ) {
                mlog()->warn( 'Listing type field: '.$field_class.' is invalid, skipping.' );
                continue;
            }

            $field = new $field_class;
            $this->cache['field_types'][ $field->props['type'] ] = $field;
        }

        return $this->cache['field_types'];
    }

    /**
     * Get list of field modifiers and modifier descriptions, to be
     * used with the `atwho` component in the listing type editor.
     *
     * @since 2.4.5
     */
    public function get_field_modifiers() {
        $modifiers = [];
        foreach ( $this->get_field_types() as $field ) {
            $modifiers[ $field->get_type() ] = [];
            if ( is_array( $field->modifiers ) && ! empty( $field->modifiers ) ) {
                $modifiers[ $field->get_type() ] = $field->modifiers;
            }

            $modifiers[ $field->get_type() ] = (object) apply_filters(
                sprintf( 'mylisting/%s-field/modifiers', $field->get_type() ),
                $modifiers[ $field->get_type() ]
            );
        }

        return $modifiers;
    }

    /**
     * Get list of available special keys to be shown in the
     * `atwho` component in the listing type editor.
     *
     * @since 2.4.5
     */
    public function get_special_keys() {
        return [
            ':id' => 'Listing ID',
            ':url' => 'Listing URL',
            ':authid' => 'Author ID',
            ':authname' => 'Author name',
            ':authlogin' => 'Author username',
            ':reviews-average' => 'Rating',
            ':reviews-count' => 'Review Count',
            ':reviews-mode' => 'Review mode',
            ':reviews-stars' => 'Star ratings',
            ':currentuserid' => 'Logged in user ID',
            ':currentusername' => 'Logged in user name',
            ':currentuserlogin' => 'Logged in user username',
            ':date' => 'Date posted (formatted)',
            ':rawdate' => 'Date posted',
            ':last-modified' => 'Date modified',
            ':calculate-distance' => 'Calculate Distance',
            ':views' => 'Views',
            ':uniqueviews' => 'Unique Views'
        ];
    }

    public function get_tab_types() {
        if ( ! empty( $this->cache['tab_types'] ) ) {
            return $this->cache['tab_types'];
        }

        $tabs = apply_filters( 'mylisting/listing-types/register-tabs', [
            \MyListing\Src\Listing_Types\Content_Tabs\Profile_Tab::class,
            \MyListing\Src\Listing_Types\Content_Tabs\Reviews_Tab::class,
            \MyListing\Src\Listing_Types\Content_Tabs\Related_Listings_Tab::class,
            \MyListing\Src\Listing_Types\Content_Tabs\Store_Tab::class,
            \MyListing\Src\Listing_Types\Content_Tabs\Bookings_Tab::class,
        ] );

        foreach ( $tabs as $tab_class ) {
            if ( ! ( class_exists( $tab_class ) && is_subclass_of( $tab_class, \MyListing\Src\Listing_Types\Content_Tabs\Base_Tab::class ) ) ) {
                mlog()->warn( 'Listing type tab: '.$tab_class.' is invalid, skipping.' );
                continue;
            }

            $tab = new $tab_class;
            $this->cache['tab_types'][ $tab->type ] = $tab;
        }

        return $this->cache['tab_types'];
    }

    public function get_block_types() {
        if ( ! empty( $this->cache['block_types'] ) ) {
            return $this->cache['block_types'];
        }

        $blocks = apply_filters( 'mylisting/listing-types/register-blocks', [
            \MyListing\Src\Listing_Types\Content_Blocks\Text_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Gallery_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Categories_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Tags_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Terms_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Location_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Contact_Form_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Reviews_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Related_Listing_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Countdown_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Upcoming_Dates_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Table_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Details_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\File_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Social_Networks_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Accordion_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Tabs_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Work_Hours_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Video_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Author_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Code_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Raw_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\Google_Ad_Block::class,
            \MyListing\Src\Listing_Types\Content_Blocks\General_Repeater_Block::class,
        ] );

        foreach ( $blocks as $block_class ) {
            if ( ! ( class_exists( $block_class ) && is_subclass_of( $block_class, \MyListing\Src\Listing_Types\Content_Blocks\Base_Block::class ) ) ) {
                mlog()->warn( 'Listing type content block: '.$block_class.' is invalid, skipping.' );
                continue;
            }

            $block = new $block_class;
            $this->cache['block_types'][ $block->get_type() ] = $block;
        }

        return $this->cache['block_types'];
    }

    public function get_packages_dropdown() {
        $packages = (array) \MyListing\Src\Paid_Listings\Util::get_products( [ 'fields' => false ] );

        $items = [];
        foreach ( (array) $packages as $package ) {
            $items[ $package->ID ] = $package->post_title;
        }

        return $items;
    }

    /**
     * Print filter settings in search tab.
     *
     * @since 1.7.5
     */
    public function get_filter_types() {
        if ( ! empty( $this->cache['filter_types'] ) ) {
            return $this->cache['filter_types'];
        }

        $filters = apply_filters( 'mylisting/listing-types/register-filters', [
            \MyListing\Src\Listing_Types\Filters\Wp_Search::class,
            \MyListing\Src\Listing_Types\Filters\Text::class,
            \MyListing\Src\Listing_Types\Filters\Range::class,
            \MyListing\Src\Listing_Types\Filters\Location::class,
            \MyListing\Src\Listing_Types\Filters\Proximity::class,
            \MyListing\Src\Listing_Types\Filters\Dropdown::class,
            \MyListing\Src\Listing_Types\Filters\Date::class,
            \MyListing\Src\Listing_Types\Filters\Recurring_Date::class,
            \MyListing\Src\Listing_Types\Filters\Checkboxes::class,
            \MyListing\Src\Listing_Types\Filters\Related_Listing::class,
            \MyListing\Src\Listing_Types\Filters\Order::class,
            \MyListing\Src\Listing_Types\Filters\Heading_Ui::class,
            \MyListing\Src\Listing_Types\Filters\Open_Now::class,
            \MyListing\Src\Listing_Types\Filters\Rating::class,
        ] );

        foreach ( $filters as $filter_class ) {
            if ( ! ( class_exists( $filter_class ) && is_subclass_of( $filter_class, \MyListing\Src\Listing_Types\Filters\Base_Filter::class ) ) ) {
                mlog()->warn( 'Listing type filter: '.$filter_class.' is invalid, skipping.' );
                continue;
            }

            $filter = new $filter_class;
            $this->cache['filter_types'][ $filter->get_type() ] = $filter;
        }

        return $this->cache['filter_types'];
    }

    public function get_explore_tab_presets() {
        $presets = [
            'search-form' => [
                'type' => 'search-form',
                'label' => 'Filters',
                'icon' => 'mi filter_list',
                'orderby' => '',
                'order' => '',
                'hide_empty' => false,
            ],
            'categories' => [
                'type' => 'categories',
                'label' => 'Categories',
                'icon' => 'mi bookmark_border',
                'orderby' => 'count', 'order' => 'DESC',
                'hide_empty' => true,
            ],
            'regions' => [ 'type' => 'regions',
                'label' => 'Regions',
                'icon' => 'mi bookmark_border',
                'orderby' => 'count',
                'order' => 'DESC',
                'hide_empty' => true,
            ],
            'tags' => [ 'type' => 'tags',
                'label' => 'Tags',
                'icon' => 'mi bookmark_border',
                'orderby' => 'count',
                'order' => 'DESC',
                'hide_empty' => true,
            ],
        ];

        foreach ( mylisting_custom_taxonomies() as $key => $label ) {
            $presets[ $key ] = [ 'type' => $key,
                'label' => $label,
                'icon' => 'mi bookmark_border',
                'orderby' => 'count',
                'order' => 'DESC',
                'hide_empty' => true,
            ];
        }

        return $presets;
    }

    public function get_custom_tax_list() {
        $presets = array();
        
        foreach ( mylisting_custom_taxonomies() as $key => $label ) {
            $presets[ $key ] = [ 
                'type' => $key,
                'label' => $label,
            ];
        }

        return $presets;
    }

    /**
     * Get all listing types present on the site, wrapped in
     * the custom Listing_Type class.
     *
     * @since 2.2
     */
    public function get_listing_types() {
        if ( ! empty( $this->cache['listing_types'] ) ) {
            return $this->cache['listing_types'];
        }

        $type_objs = get_posts( [
            'post_type' => 'case27_listing_type',
            'numberposts' => -1,
        ] );

        $this->cache['listing_types'] = array_map( function( $type_obj ) {
            return \MyListing\Src\Listing_Type::get( $type_obj );
        }, (array) $type_objs );

        return $this->cache['listing_types'];
    }

    public function get_quick_actions() {
        return require_once locate_template( 'includes/src/listing-types/quick-actions/quick-actions.php' );
    }

    public function regenerate_preview_cards_notice() {
        global $post, $current_screen;
        if ( empty( $_GET['regen_previews'] ) || ! $post || $current_screen->id !== 'case27_listing_type' ) {
            return;
        }

        $url = admin_url( 'admin.php?page=mylisting-options&active_tab=preview-cards&generate='.$post->post_name );
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>You've made changes to the preview card template. To reflect changes on the site frontend, you must regenerate the cache files.</p>
            <p><a href="<?php echo esc_url( $url ) ?>" class="button button-primary">Regenerate cache</a></p>
        </div>
        <?php
    }
}
