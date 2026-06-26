<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Location_Field extends Base_Field {

	private $cached_value;

	public $modifiers = [
		'list'		=> 'Locations List',
		'address'	=> 'Full Address',
		'short' 	=> 'Short Address',
		'lat' 		=> '%s Latitude',
		'lng' 		=> '%s Longitude',
	];

	public function init() {
		add_filter( 'mylisting/compile-string/unescaped-fields', function( $fields ) {
			$fields[] = 'location';
			$fields[] = 'location.list';
			return $fields;
		} );
	}

	public function get_posted_value() {
		$value = ! empty( $_POST[ $this->key ] ) ? (array) $_POST[ $this->key ] : [];
		$locations = [];

		foreach ( $value as $item ) {
			if ( empty( $item['address'] ) && ! empty( $item['lat'] ) && ! empty( $item['lng'] ) ) {
				$item = $this->geocode_location( $item );
			}

			$location = [
				'address' => ! empty( $item['address'] ) ? sanitize_text_field( wp_unslash( $item['address'] ) ) : null,
				'lat' => ! empty( $item['lat'] ) ? round( floatval( $item['lat'] ), 5 ) : null,
				'lng' => ! empty( $item['lng'] ) ? round( floatval( $item['lng'] ), 5 ) : null,
			];

			if ( $location['address'] && $location['lat'] && $location['lng'] ) {
				$locations[] = $location;
			}
		}

		return $locations;
	}

	public function geocode_location( $item ) {
		$geocoder = \MyListing\Src\Geocoder\Geocoder::get();

		if ( is_null( $geocoder ) ) {
			return [
				'address' => ! empty( $item['address'] ) ? sanitize_text_field( wp_unslash( $item['address'] ) ) : null,
				'lat' => ! empty( $item['lat'] ) ? round( floatval( $item['lat'] ), 5 ) : null,
				'lng' => ! empty( $item['lng'] ) ? round( floatval( $item['lng'] ), 5 ) : null,
			];
        }

        $location = [ $item['lat'], $item['lng'] ];

        $feature = $geocoder->geocode( $location );

        return [
        	'address'	=> $feature['address'],
        	'lat'		=> (float) $feature['latitude'],
            'lng'		=> (float) $feature['longitude']
        ];
	}

	public function validate() {
		$value = $this->get_posted_value();
		$limit = $this->get_current_selection_limit();

		$this->validate_selection_count(
			$value,
			$limit,
			$this->props['label'],
			_x( 'You can only add <b>%1$d</b> location in the <b>%2$s</b> field.', 'Add listing form', 'my-listing' ),
			_x( 'You can only add <b>%1$d</b> locations in the <b>%2$s</b> field.', 'Add listing form', 'my-listing' )
		);
	}

	public function update() {
		global $wpdb;

		$value = $this->get_posted_value();

		// delete existing locations
		$wpdb->delete( $wpdb->prefix.'mylisting_locations', [
			'listing_id' => $this->listing->get_id()
		] );
		
		// create query with updated values
		$rows = [];
		foreach ( (array) $value as $location ) {
			if ( $location['address'] && $location['lat'] && $location['lng'] ) {
				$rows[] = sprintf(
					'(%d,"%s",%s,%s)',
					$this->listing->get_id(),
					esc_sql( $location['address'] ),
					(float) $location['lat'],
					(float) $location['lng'],
				);
			}
		}

		// update table with new values
		if ( ! empty( $rows ) ) {
			$query = "INSERT INTO {$wpdb->prefix}mylisting_locations
				(listing_id, address, lat, lng) VALUES ";
			$query .= implode( ',', $rows );
			$wpdb->query( $query );
		}
	}

	public function get_value( $all = false ) {
		if ( is_null( $this->cached_value ) ) {
			$this->cached_value = $this->get_locations();
		}

		if ( $all ) {
			return $this->cached_value;
		}

		if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], [ 'switch', 'duplicate' ], true ) ) {
			return $this->cached_value;
		}

		$limit = $this->get_current_selection_limit();
		if ( $limit !== null && $limit > 0 && count( (array) $this->cached_value ) > $limit ) {
			return array_slice( (array) $this->cached_value, 0, $limit );
		}

		return $this->cached_value;
	}

	public function get_locations() {
		global $wpdb;

		$locations = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}mylisting_locations WHERE listing_id = %d",
			$this->listing->get_id()
		), ARRAY_A );

		if ( ! $locations || ! is_array( $locations ) ) {
			return [];
		}

		$items = [];
		foreach ( $locations as $location ) {
			$items[] = [
				'address' => $location['address'],
				'lat' => $location['lat'],
				'lng' => $location['lng'],
			];
		}
		
		return $items;
	}

	public function get_current_selection_limit() {
		$props = $this->props;

		if ( empty( $props['selection_limit'] ) && isset( $props['max'] ) ) {
			$props['selection_limit'] = $props['max'];
		}

		$package_id = $this->_get_package_id_from_context( $this->listing ?? null );
		return $this->_calculate_current_selection_limit( $props, $package_id );
	}

	public function field_props() {
		$this->props['type'] = 'location';
		$this->props['map-skin'] = false;
		$this->props['map-zoom'] = 12;
		$this->props['max'] = 3;
		$this->props['selection_limit'] = 3;
		$this->props['enable_package_limits'] = false;
		$this->props['package_limits'] = [];
		$this->props['map-default-location'] = [
			'lat' => 0,
			'lng' => 0,
		];
	}

	public function after_custom_props() {
		if ( ! isset( $this->props['selection_limit'] ) || $this->props['selection_limit'] === '' ) {
			$this->props['selection_limit'] = $this->props['max'];
		}

		if ( isset( $this->props['selection_limit'] ) && is_numeric( $this->props['selection_limit'] ) ) {
			$this->props['max'] = absint( $this->props['selection_limit'] );
		}

		if ( ! isset( $this->props['enable_package_limits'] ) ) {
			$this->props['enable_package_limits'] = false;
		}

		if ( empty( $this->props['package_limits'] ) || ! is_array( $this->props['package_limits'] ) ) {
			$this->props['package_limits'] = [];
		}
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getPlaceholderField();
		$this->getDescriptionField();
		$this->getMapSkinField();
		$this->getMapDefaultLocationField();
		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
		$this->getShowInCompareField();
	}

	public function getMapSkinField() { ?>
		<div class="form-group">
			<label>Max number of locations allowed</label>
			<input type="number" min="0" v-model="field.selection_limit" @input="field.max = field.selection_limit">
			<small>Leave blank or set to 0 for no limit.</small>
		</div>

		<?php
		$this->get_package_specific_limit_options(
			'Location',
			'field.enable_package_limits',
			'field.package_limits',
			[
				[ 'model_key' => 'limit', 'placeholder' => 'Max locations', 'type' => 'number', 'step' => '1' ],
			],
			"{package: '', limit: ''}"
		);
		?>

		<div class="form-group">
			<label>Map Skin</label>
			<div class="select-wrapper">
				<select v-model="field['map-skin']">
					<?php foreach ( \MyListing\Apis\Maps\get_skins() as $skin => $label ): ?>
						<option value="<?php echo esc_attr( $skin ) ?>"><?php echo esc_html( $label ) ?></option>
					<?php endforeach ?>
				</select>
			</div>
		</div>
	<?php }

	public function getMapDefaultLocationField() { ?>
		<div class="form-group">
			<label>Default map location</label>
			<input type="number" min="-90" max="90" v-model="field['map-default-location']['lat']" step="any" style="width: 49%;" placeholder="Latitude">
			<input type="number" min="-180" max="180" v-model="field['map-default-location']['lng']" step="any" style="width: 49%; float: right;" placeholder="Longitude">
			<p class="mb0">When the field is empty, this will be used as the map center.</p>
		</div>

		<div class="form-group">
			<label>Default map zoom level</label>
			<input type="number" min="0" max="22" v-model="field['map-zoom']" style="width: 49%;">
			<p class="mb0">Enter a value between 0 (no zoom) and 22 (maximum zoom). Default: 12.</p>
		</div>
	<?php }

	public function string_value( $modifier = null ) {
		$value =  $this->get_value();
		if ( ! $value ) {
			return;
		}

		if ( $modifier === 'list' ) {
			$formatted_address = array_filter( array_map( function( $data ) {
				if ( ! isset( $data['address'] ) ) {
					return;
				}

				return sprintf( '<p>%s</p>', $data['address'] );

			}, $value ) );

			return implode( " ", $formatted_address );
		}

		$address = $value[0];

		if ( $modifier === 'short' ) {
	        $parts = explode(',', $address['address'] );
	        return trim( $parts[0] );
		}

		if ( $modifier === 'lat' ) {
			return $address['lat'];
		}

		if ( $modifier === 'lng' ) {
			return $address['lng'];
		}

		if ( $modifier === 'address' ) {
			return $address['address'];
		}
		
		return $address;
	}
}
