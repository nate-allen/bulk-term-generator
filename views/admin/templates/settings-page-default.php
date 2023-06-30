<?php
/**
 * @var $taxonomy_select_list string The HTML for the select list of taxonomies
 */
?>

<div class="wrap bulk-term-generator" id="btg-default">
	<h2><?php esc_html_e( 'Bulk Term Generator', 'bulk-term-generator' ); ?></h2>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="error">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php endif ?>

	<p><?php esc_html_e( "First, choose a taxonomy you'd like to add terms to:", 'bulk-term-generator' ); ?></p>

	<form method="post">

		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">
					<label for="chosen_taxonmy"><?php esc_html_e( 'Taxonomy', 'bulk-term-generator' ); ?></label>
				</th>
				<td>
					<?php
					echo wp_kses(
						$taxonomy_select_list,
						array(
							'select' => array(
								'name'  => array(),
								'id'    => array(),
								'class' => array(),
							),
							'option' => array(
								'value'    => array(),
								'selected' => array(),
							),
						)
					);
					?>
				</td>
			</tr>
			</tbody>
		</table>

		<p class="submit">
			<input type="submit" class="button button-secondary" name="btg_select_taxonomy_submit" value="<?php esc_attr_e( 'Select Taxonomy', 'bulk-term-generator' ); ?>">
		</p>

		<input type="hidden" name="action" value="taxonomy_selected">

	</form>
</div>
