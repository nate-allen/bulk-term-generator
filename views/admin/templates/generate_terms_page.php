<div class="wrap bulk-term-generator" id="btg-generate-terms">

    <h2>Bulk Term Generator</h2>

    <?php if ( !empty($error) ) : ?>
        <div class="error">
            <p><?= $error ?></p>
        </div>
    <?php endif ?>

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

    <p><span class="tip"><strong>Note:</strong> Terms won't be generated when you press the button below.<br>
    You will have an opportunity to delete terms or start over before actually generating the terms in bulk.</span></p>

    <p class="submit">
        <input type="submit" class="button button-secondary" id="add-terms" value="Add Terms">
    </p>

    <p>When you're ready to commit, click the button below to generate all the terms.</p>

    <p class="submit">
        <input type="submit" class="button button-primary" name="btg_select_taxonomy_submit" value="Generate Terms">
    </p>

</div>