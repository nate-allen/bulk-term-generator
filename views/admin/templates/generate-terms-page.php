<?php
/**
 * @var $taxonomy_name string The name of the taxonomy.
 * @var $taxonomy_slug string The slug of the taxonomy.
 * @var $is_hierarchical bool Whether or not the taxonomy is hierarchical.
 * @var $term_list string A list if of the terms.
 * @var $term_select_list string A list of the terms in a select box.
 */
?>
<div class="wrap bulk-term-generator" id="btg-generate-terms">

	<h2><?php esc_html_e( 'Bulk Term Generator', 'bulk-term-generator' ); ?></h2>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="error">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php endif ?>

	<div class="btg-main">

		<?php /* translators: %s is the taxonomy name. */ ?>
		<p><?php printf( esc_html__( 'On this page you can add terms to the %s taxonomy in bulk.', 'bulk-term-generator' ), esc_html( $taxonomy_name ) ); ?></p>

		<h3><?php esc_html_e( 'Your Terms:', 'bulk-term-generator' ); ?></h3>

		<div class="btg-term-list-container">
			<?php if ( ! empty( $terms ) ) : ?>
				<?php echo wp_kses_post( $term_list ); ?>
			<?php else : ?>
				<p><?php esc_html_e( 'No terms yet. Add some below!', 'bulk-term-generator' ); ?></p>
			<?php endif; ?>
		</div>

		<h3><?php esc_html_e( 'Add Terms', 'bulk-term-generator' ); ?></h3>

		<div class="instructions">
			<p>
				<strong>
					<?php
					echo wp_kses(
						__( 'Enter each term below <span>on its own line</span>.', 'bulk-term-generator' ),
						array( 'span' => array() )
					);
					?>
				</strong>
			</p>
		</div>

		<p><span class="tip"><strong><?php esc_html_e( 'Optional:', 'bulk-term-generator' ); ?></strong> <?php esc_html_e( 'You can specify the "slug" and "description" for each term by seperating them with commas.', 'bulk-term-generator' ); ?><br>
		<span class="example"><?php esc_html_e( '(ie: United States, united_states, Population is 317 Million)', 'bulk-term-generator' ); ?></span></span>
		</p>

		<textarea class="btg-terms-to-add example" rows="10"></textarea>

		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row"><label for="parent-term"><?php esc_html_e( 'Parent', 'bulk-term-generator' ); ?></label></th>
				<td>
					<?php if ( $is_hierarchical ) : ?>
						<?php
						echo wp_kses(
							$term_select_list,
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
					<?php else : ?>
						<span class="sorry"><?php esc_html_e( "(Sorry, this taxonomy isn't hierarchical)", 'bulk-term-generator' ); ?></span>
					<?php endif ?>
				</td>
			</tr>
			</tbody>
		</table>

		<form action="">

			<p class="submit">
				<input type="submit" class="button button-secondary btg-add-terms" value="<?php esc_attr_e( 'Add Terms to Queue', 'bulk-term-generator' ); ?>">
				<input type="submit" class="button button-primary btg-generate-terms-button" name="btg_select_taxonomy_submit" value="<?php esc_attr_e( 'Generate Terms', 'bulk-term-generator' ); ?>" disabled>
			</p>

			<?php wp_nonce_field( 'btg_add_term_to_' . $taxonomy_slug, 'btg_add_term_nonce' ); ?>

		</form>

		<!-- Dialog box -->
		<div id="btg-dialog" style="display:none;">
		</div>

	</div>

	<div class="btg-side-container">

		<div class="btg-side">

			<div class="btg-about">
				<h3><?php echo wp_kses_post( __( 'About', 'bulk-term-generator' ) ); ?></h3>

				<p><?php echo wp_kses_post( __( '<strong>Bulk Term Generator</strong> was developed by <a href="https://www.linkedin.com/in/nate-allen">Nate Allen</a>, Code Wrangler at <a href="https://automattic.com/work-with-us/">Automattic</a>.', 'bulk-term-generator' ) ); ?></p>

				<p><?php echo wp_kses_post( __( 'If you found this plugin useful, please consider <a href="https://wordpress.org/support/plugin/bulk-term-generator/reviews/#new-post">giving it five stars</a>!', 'bulk-term-generator' ) ); ?></p>
			</div>

			<div class="btg-support">
				<h3><?php echo wp_kses_post( __( 'Support', 'bulk-term-generator' ) ); ?></h3>

				<p><?php echo wp_kses_post( __( 'If you need help, <a href="https://wordpress.org/support/plugin/bulk-term-generator">check out the support page for the plugin</a>. I will do my best to answer questions or patch bugs.', 'bulk-term-generator' ) ); ?></p>
			</div>

			<div class="btg-feedback">
				<h3><?php echo wp_kses_post( __( 'Feedback', 'bulk-term-generator' ) ); ?></h3>

				<p><?php echo wp_kses_post( __( 'Do you have an idea for an improvement? <a href="https://twitter.com/hyphen_nate">Message me on Twitter</a> and I\'ll see what I can do. Or better yet, <a href="https://github.com/nate-allen/bulk-term-generator">contribute code to the Github repository</a>!', 'bulk-term-generator' ) ); ?></p>
			</div>

		</div>

	</div>

</div>
