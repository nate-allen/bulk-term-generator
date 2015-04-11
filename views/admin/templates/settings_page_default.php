<div class="wrap bulk-term-generator" id="btg-default">

    <h2>Bulk Term Generator</h2>

    <p>First, choose a taxonomy you'd like to add terms to:</p>

    <form action="tools.php" method="post">

        <label class="select" for="chosen_taxonmy">Taxonomy:</label>

        <!-- Select list populated with taxonomies -->
        <?= $taxonomy_select_list ?>

        <p class="submit">
            <input type="submit" class="button-primary" name="btg_select_taxonomy_submit" value="Select Taxonomy">
        </p>

    </form>

</div>