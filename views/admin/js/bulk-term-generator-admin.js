(function( $ ) {
    'use strict';

    // The page is ready
    $(function() {

        var terms_array = [],
            hierarchy   = [],
            new_id      = 1;


        $('#add-terms').on('click', function(e){
            e.preventDefault();

            // The new terms to be added, and the parent they should be added to
            var terms_to_add = $('#terms-to-add').val().split('\n'),
                parent_term  = ( $('#parent_term').val() ) ? $('#parent_term').val() : 0;

            // If the "Parents" select list exists, and this is the first time, get the terms
            // and create an object for each one
            if ( $('#parent_term').length > 0 && new_id === 1 ) {
                $('#parent_term option').not( ":empty" ).each(function(){
                    terms_array.push({
                        Id : parseInt($(this).val()),
                        Name : $(this).data('name'),
                        Parent : $(this).data('parent')
                    });
                });
            }

            // Create object for each new term. Added to terms_array
            create_objects( terms_to_add, parent_term);

            //console.log(terms_array);
            //console.log(build_hierarchy(terms_array));

            // Build the hierarchy
            build_hierarchy(terms_array);

            // Update the select list
            update_select_list();

            // Update the term list
            update_term_list();

        });

        var create_objects = function( terms, parent ) {

            terms = process_terms( terms );

            for (var i = 0; i < terms.length; i++) {
                var key = 'new_'+new_id++;

                terms_array.push({
                    Id     : key,
                    Name   : terms[i][0],
                    Slug   : (terms[i][1]) ? terms[i][1] : '',
                    Desc   : (terms[i][2]) ? terms[i][2] : '',
                    Parent : parent
                });
            }

        };

        var process_terms = function( terms ) {

            var terms_array = [];

            // Seperate terms by comma, and trim the white space
            for (var i = 0; i < terms.length; i++) {
                terms_array.push($.map(terms[i].split(','), $.trim));
            }

            return terms_array;

        };

        var add_terms = function( terms ) {

            var terms_object;

            var html = '<ul id="term-list">';

            for (var i = 0; i < terms.length; i++) {
                html += '<li>' + terms[i] + '</li>';
            }

            html += '</ul>';

            $('#term-list-container').html(html);

        };

        var build_hierarchy = function(arry) {

            var roots = [], children = {};

            // find the top level nodes and hash the children based on parent
            for (var i = 0, len = arry.length; i < len; ++i) {
                var item = arry[i],
                    p = item.Parent,
                    target = !p ? roots : (children[p] || (children[p] = []));

                target.push({ value: item });
            }

            // function to recursively build the tree
            var find_children = function(parent) {
                if (children[parent.value.Id]) {
                    parent.children = children[parent.value.Id];
                    for (var i = 0, len = parent.children.length; i < len; ++i) {
                        find_children(parent.children[i]);
                    }
                }
            };

            // enumerate through to handle the case where there are multiple roots
            for (var j = 0, length = roots.length; j < length; ++j) {
                find_children(roots[j]);
            }

            hierarchy = roots;

        };

    });

    // The window has loaded
    $( window ).load(function() {

    });

})( jQuery );
