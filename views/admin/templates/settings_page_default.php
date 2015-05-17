<div class="wrap bulk-term-generator" id="btg-default">

    <h2><?php _e('Bulk Term Generator', 'bulk-term-generator') ?></h2>

    <?php if ( !empty($error) ) : ?>
        <div class="error">
              <p><?= $error ?></p>
        </div>
    <?php endif ?>

    <p><?php _e("First, choose a taxonomy you'd like to add terms to:", 'bulk-term-generator') ?></p>

    <form method="post">

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="chosen_taxonmy"><?php _e('Taxonomy', 'bulk-term-generator') ?></label>
                    </th>
                    <td>
                        <?= $taxonomy_select_list ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <input type="submit" class="button button-secondary" name="btg_select_taxonomy_submit" value="<?php esc_attr_e('Select Taxonomy', 'bulk-term-generator') ?>">
        </p>

        <input type="hidden" name="action" value="taxonomy_selected">

    </form>

</div>