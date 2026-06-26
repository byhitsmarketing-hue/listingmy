<?php
/**
 * Template for rendering a `rating` filter in Explore page.
 *
 * @since 2.8
 *
 * @var $filter
 * @var $location
 * @var $onchange
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

$rating_options = $filter->get_rating_options();
$current_value = $filter->get_request_value();
?>

<rating-filter
	listing-type="<?php echo esc_attr( $filter->listing_type->get_slug() ) ?>"
	filter-key="<?php echo esc_attr( $filter->get_form_key() ) ?>"
	location="<?php echo esc_attr( $location ) ?>"
	label="<?php echo esc_attr( $filter->get_label() ) ?>"
	:options='<?php echo wp_json_encode( $rating_options ) ?>'
	@input="<?php echo esc_attr( $onchange ) ?>"
	inline-template
>
	<div v-if="location === 'primary-filter'" class="explore-head-search form-group radius">
		<i class="mi search"></i>
		<select v-model="selectedValue" @change="handleSelectChange" class="form-control">
			<option v-for="option in optionsArray" :key="option.value" :value="option.value">
				{{ option.label }}
			</option>
		</select>
	</div>
	<div v-else class="form-group radius radius1 explore-filter rating-filter">
		<label>{{ label }}</label>
		<div class="rating-filter-options">
			<div 
				v-for="option in optionsArray" 
				:key="option.value"
				class="rating-option"
				:class="{ 'active': String( selectedValue ) === String( option.value ) }"
				@click="selectRating(option.value)"
			>
				<div class="rating-stars" v-if="option.value">
					<i v-for="star in parseInt(option.value)" :key="star" class="mi star"></i>
					<span class="rating-text">{{ option.label }}</span>
				</div>
				<div v-else class="rating-text">{{ option.label }}</div>
			</div>
		</div>
	</div>
</rating-filter>
