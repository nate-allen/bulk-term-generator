<div class="wrap bulk-term-generator" id="btg-default">

    <h2>Bulk Term Generator</h2>

    <?php if ( !empty($error) ) : ?>
        <div class="error">
              <p><?= $error ?></p>
        </div>
    <?php endif ?>

    <p>First, choose a taxonomy you'd like to add terms to:</p>

    <form method="post">

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="chosen_taxonmy">Taxonomy <? $hook_suffix ?></label>
                    </th>
                    <td>
                        <?= $taxonomy_select_list ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <input type="submit" class="button button-secondary" name="btg_select_taxonomy_submit" value="Select Taxonomy">
        </p>

        <input type="hidden" name="action" value="taxonomy_selected">

    </form>

</div>