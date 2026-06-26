<?php
/**
 * Helper functions for block types that have content rows, like tables,
 * accordions, details, and tabs blocks.
 *
 * @since 2.2
 */

namespace MyListing\Src\Listing_Types\Content_Blocks\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Content_Rows {

	/**
	 * Validate and format rows for use in template files.
	 *
	 * @since 2.2
	 */
	public function get_formatted_rows( $listing ) {
		$rows = [];
		$raw_rows = (array) $this->get_prop('rows');
		$block_type = $this->get_prop('type');

		// Block types that have translatable row labels (details uses icons instead)
		$blocks_with_row_labels = [ 'table', 'accordion', 'tabs' ];

		foreach ( $raw_rows as $row_index => $row ) {
			if ( ! is_array( $row ) || empty( $row['content'] ) ) {
				continue;
			}

			$content = do_shortcode( $listing->compile_string( $row['content'] ) );
			if ( ! empty( $content ) ) {
				$label = $row['label'] ?? '';

				// Apply translation for row labels in table, accordion, and tabs blocks
				if ( $label !== '' && in_array( $block_type, $blocks_with_row_labels, true ) ) {
					$label = \c27()->ml_t(
						$label,
						'block.row.label',
						[
							'block' => $this,
							'row_index' => $row_index,
						]
					);
				}

				$rows[] = [
					'title' => $label,
					'content' => $content,
					'icon' => $row['icon'] ?? '',
				];
			}
		}

		return $rows;
	}

	protected function getRowsField() { ?>
		<div class="repeater-option">
			<label>Rows</label>
			<draggable v-model="block.rows" :options="{group: 'repeater', handle: '.row-head'}">
				<div v-for="row, row_id in block.rows" class="row-item">
					<div class="row-head" @click="toggleRepeaterItem($event)">
						<div class="row-head-toggle"><i class="mi chevron_right"></i></div>
						<div class="row-head-label">
							<h4>
								<span v-for="part in $root.getLabelParts( row.content, '(click to edit)' )"
									:class="'label-part-'+part.type" v-text="part.content"></span>
							</h4>
							<div class="details">
								<div class="detail">Click to edit</div>
							</div>
						</div>
						<div class="row-head-actions">
							<span title="Remove" @click.stop="block.rows.splice(row_id, 1)" class="action red"><i class="mi delete"></i></span>
						</div>
					</div>
					<div class="row-edit">
						<div class="form-group" v-if="block.type === 'details'">
							<label>Icon</label>
							<iconpicker v-model="row.icon"></iconpicker>
						</div>

						<div class="form-group" v-if="block.type !== 'details'">
							<label>Label</label>
							<input type="text" v-model="row.label">
						</div>

						<div class="form-group">
							<label>Content</label>
							<atwho v-model="row.content" template="input"></atwho>
						</div>

						<div class="text-right mt10">
							<div class="btn btn-xs" @click.prevent="toggleRepeaterItem($event)">Done</div>
						</div>
					</div>
				</div>
				<div class="text-right mt10">
					<div class="btn btn-xs" @click.prevent="block.rows.push({label: '', content: '', icon: ''})">Add row</div>
				</div>
			</draggable>
		</div>
	<?php }

}
