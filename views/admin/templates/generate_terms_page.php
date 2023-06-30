<div class="wrap bulk-term-generator" id="btg-generate-terms">

    <h2><?php _e('Bulk Term Generator', 'bulk-term-generator') ?></h2>

    <?php if ( !empty($error) ) : ?>
        <div class="error">
            <p><?= $error ?></p>
        </div>
    <?php endif ?>

    <div class="btg-main">

        <p><?php printf( __( 'On this page you can add terms to the %s taxonomy in bulk.', 'bulk-term-generator' ), $taxonomy_name ); ?></p>

        <h3><?php _e('Your Terms:', 'bulk-term-generator') ?></h3>

        <div class="btg-term-list-container">
            <?php if ( !empty($terms) ) : ?>
                <?= $term_list ?>
            <?php else : ?>
                <p><?php _e("No terms yet. Add some below!", 'bulk-term-generator') ?></p>
            <?php endif; ?>
        </div>

        <h3><?php _e('Add Terms', 'bulk-term-generator') ?></h3>

        <div class="instructions">
            <p><strong><?php _e('Enter each term below <span>on its own line</span>.', 'bulk-term-generator') ?></strong></p>
        </div>

        <p><span class="tip"><strong><?php _e('Optional:', 'bulk-term-generator') ?></strong> <?php _e('You can specify the "slug" and "description" for each term by seperating them with commas.', 'bulk-term-generator') ?><br>
        <span class="example"><?php _e('(ie: United States, united_states, Population is 317 Million)', 'bulk-term-generator') ?></span></span></p>

        <textarea class="btg-terms-to-add" rows="10" class="example"></textarea>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="parent-term"><?php _e('Parent', 'bulk-term-generator') ?></label></th>
                    <td>
                        <?php if ( $is_hierarchical ) : ?>
                            <?= $term_select_list ?>
                        <?php else : ?>
                            <span class="sorry"><?php _e("(Sorry, this taxonomy isn't hierarchical)", 'bulk-term-generator') ?></span>
                        <?php endif ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <form action="">

            <p class="submit">
                <input type="submit" class="button button-secondary btg-add-terms" value="<?php esc_attr_e('Add Terms to Queue', 'bulk-term-generator') ?>">
                <input type="submit" class="button button-primary btg-generate-terms-button" name="btg_select_taxonomy_submit" value="<?php esc_attr_e('Generate Terms', 'bulk-term-generator') ?>" disabled>
            </p>

            <?php wp_nonce_field( 'btg_add_term_to_'.$taxonomy_slug, 'btg_add_term_nonce' ); ?>

        </form>

        <!-- Dialog box -->
        <div id="btg-dialog" style="display:none;">
        </div>

    </div>

    <div class="btg-side-container">

        <div class="btg-side">

            <div class="btg-about">

                <h3><?php _e('About', 'bulk-term-generator') ?></h3>

                <p><?php _e('<strong>Bulk Term Generator</strong> was developed by <a href="https://www.linkedin.com/in/nate-allen">Nate Allen</a>, Code Wrangler at <a href="https://automattic.com/work-with-us/">Automattic</a>.', 'bulk-term-generator') ?></p>

                <p><?php _e('If you found this plugin useful, please consider <a href="https://wordpress.org/support/plugin/bulk-term-generator/reviews/#new-post">giving it five stars</a>!', 'bulk-term-generator') ?></p>

            </div>

            <div class="btg-support">

                <h3><?php _e('Support', 'bulk-term-generator') ?></h3>

                <p><?php _e('If you need help, <a href="https://wordpress.org/support/plugin/bulk-term-generator">check out the support page for the plugin</a>. I will do my best to answer questions or patch bugs.', 'bulk-term-generator') ?></p>

            </div>

            <div class="btg-feedback">

                <h3><?php _e('Feedback', 'bulk-term-generator') ?></h3>

                <p><?php _e('Do you have an idea for an improvement? <a href="https://twitter.com/hyphen_nate">Message me on Twitter</a> and I\'ll see what I can do. Or better yet, <a href="https://github.com/nate-allen/bulk-term-generator">contribute code to the Github repository</a>!', 'bulk-term-generator') ?></p>

            </div>

        </div>

    </div>

</div>