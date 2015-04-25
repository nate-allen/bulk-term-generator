<div class="wrap bulk-term-generator" id="btg-generate-terms">

    <h2>Bulk Term Generator</h2>

    <?php if ( !empty($error) ) : ?>
        <div class="error">
            <p><?= $error ?></p>
        </div>
    <?php endif ?>

    <div class="btg-main">

        <p>On this page you can add terms to the "<?= $taxonomy_name ?>" taxonomy in bulk.</p>

        <h3>Your Terms:</h3>

        <div id="term-list-container">
            <?php if ( !empty($terms) ) : ?>
                <?= $term_list ?>
            <?php else : ?>
                <p>No terms yet. Add some below!</p>
            <?php endif; ?>
        </div>

        <h3>Add Terms</h3>

        <div class="instructions">
            <p><strong>Enter each term below <span>on its own line</span>.</strong></p>
        </div>

        <p><span class="tip"><strong>Optional:</strong> You can specify the "slug" and "description" for each term by seperating them with commas.<br>
        <span class="example">(ie: United States, united_states, Population is 317 Million)</span></span></p>

        <textarea id="terms-to-add" rows="10" class="example"></textarea>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="parent-term">Parent <? $hook_suffix ?></label></th>
                    <td>
                        <?php if ( $is_hierarchical ) : ?>
                            <?= $term_select_list ?>
                        <?php else : ?>
                            <span class="sorry">(Sorry, this taxonomy isn't hierarchical)</span>
                        <?php endif ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <form action="">

            <p class="submit">
                <input type="submit" class="button button-secondary" id="add-terms" value="Add Terms to Queue">
                <input type="submit" class="button button-primary" name="btg_select_taxonomy_submit" value="Generate Terms" id="btg-generate-terms-button" disabled>
            </p>

            <?php wp_nonce_field( 'btg_add_term_to_'.$taxonomy_slug, 'btg_add_term_nonce' ); ?>

        </form>

        <!-- Dialog boxes -->
        <div id="btg-dialog-add" title="Generating Terms..." style="display:none;">
            <div class="in-progress">
                <div id="btg-progressbar"></div>
                <p class="progress-status">Creating <em></em></p>
            </div>
            <div class="completed" style="display:none;">
                <p><strong>Done!</strong> <span class="num-term-created">0 terms have</span> been created.</p>
            </div>
        </div>
        <div id="btg-dialog-edit" title="Edit Term" style="display:none;">
            <p class="message" style="display:none;"></p>
            <form>
                <fieldset>
                    <div class="input-group">
                        <label for="name">Name</label>
                        <input type="text" name="name" id="name" value="" class="text ui-widget-content ui-corner-all">
                    </div>
                    <div class="input-group">
                        <label for="slug">Slug</label>
                        <input type="text" name="slug" id="slug" value="" class="text ui-widget-content ui-corner-all">
                    </div>
                    <div class="input-group">
                        <label for="password">Description</label>
                        <input type="text" name="description" id="description" value="" class="text ui-widget-content ui-corner-all">
                    </div>
                    <input type="hidden" name="id" id="id" val="">
                </fieldset>
              </form>
        </div>

    </div>

    <div class="btg-side-container">

        <div class="btg-side">

            <div class="btg-about">

                <h3>About</h3>

                <p><strong>Bulk Term Generator</strong> was developed by Nate Allen, Senior Web Developer at <a href="http://fireflypartners.com">Firefly Partners</a>.</p>

            </div>

            <div class="btg-support">

                <h3>Support</h3>

                <p>If need help, check out the support page for the plugin. I will do my best to answer questions or patch bugs. Please be patient; this is a free plugin and I have a full-time job. :)</p>

            </div>

            <div class="btg-feedback">

                <h3>Feedback</h3>

                <p>Do you have an idea for an improvement? <a href="mailto:email@ncallen.com">Email me</a> and I'll see what I can do. Or better yet, <a href="https://github.com/nate-allen/bulk-term-generator">contribute code to the Github repository</a>!</p>

            </div>

        </div>

    </div>

</div>