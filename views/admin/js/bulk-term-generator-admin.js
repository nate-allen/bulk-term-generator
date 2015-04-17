(function( $ ) {
    'use strict';

    // The page is ready
    $(function() {

        var terms_array    = [],
            hierarchy      = [],
            new_id         = 1,
            select_options = '',
            list_items     = '',
            seperator      = 0;


        $('#add-terms').on('click', function(e){
            e.preventDefault();

            if ($('#terms-to-add').val() === '')
                return false;

            // The new terms to be added, and the parent they should be added to
            var terms_to_add = $('#terms-to-add').val().split('\n'),
                parent_term  = ( $('#parent_term').val() ) ? $('#parent_term').val() : 0;

            // If this is the first time, get the existing terms
            if ( new_id === 1 ) {
                var existing_terms = window.btg_terms_list;

                for (var i = 0; i < existing_terms.length; i++) {
                    terms_array.push({
                        Id : parseInt(existing_terms[i].Id),
                        Name : existing_terms[i].Name,
                        Parent : parseInt(existing_terms[i].Parent)
                    });
                }
            }

            // Create object for each new term. Added to terms_array
            create_objects( terms_to_add, parent_term);

            reset_everything();

            // Clear the "terms to add" textarea
            $('#terms-to-add').val('');

            // Scroll to the top of the page
            $("html, body").animate({ scrollTop: 0 }, "medium");

            // Add JSON data to hidden field
            $('#terms-json').val(JSON.stringify(hierarchy));

        });

        $('#term-list-container').on('click', 'a.delete', function(e){

            var id = $(this).data('id');

            for (var i = terms_array.length - 1; i >= 0; i--) {
                if (terms_array[i].Id == id )
                    terms_array.splice(i, 1);
            }

            reset_everything();

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

        var build_hierarchy = function() {

            var roots = [], children = {};

            // find the top level nodes and hash the children based on parent
            for (var i = 0, len = terms_array.length; i < len; ++i) {
                var item = terms_array[i],
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

        var update_select_list = function() {

            for (var i = 0; i < hierarchy.length; i++) {
                get_select_options(hierarchy[i]);
            }

            $('#parent_term').empty().append('<option></option>'+select_options);

        };

        var update_term_list = function() {

            for (var i = 0; i < hierarchy.length; i++) {
                get_list_items(hierarchy[i]);
            }

            $('#term-list-container').html('<ul id="term-list">'+list_items+'</ul>');

        };

        var get_select_options = function( data ) {

            select_options += '<option value="'+data.value.Id+'" data-parent="'+data.value.Parent+'" data-name="'+data.value.Name+'">'+create_seperators(seperator)+data.value.Name+'</option>';
            if ( data.children ) {
                ++seperator;
                for (var i = 0; i < data.children.length; i++) {
                    get_select_options( data.children[i] );
                }
                --seperator;
            }

        };

        var get_list_items = function( data ) {

            if (data.children) {
                list_items += '<li>'+data.value.Name;
                list_items += (typeof data.value.Id != 'number') ? '<a href="#" class="delete" data-id="'+data.value.Id+'">X</a>' : '';
                list_items += '<ul>';
                for (var i = 0; i < data.children.length; i++) {
                    get_list_items( data.children[i] );
                }
                list_items += '</ul></li>';
            } else {
                list_items += '<li>'+data.value.Name;
                list_items += (typeof data.value.Id != 'number') ? '<a href="#" class="delete" data-id="'+data.value.Id+'">X</a>' : '';
                list_items += '</li>';
            }

        };

        var create_seperators = function() {
            var sep = '';
            for (var i = 0; i < seperator; i++) {
                sep += '&#8212;';
            }
            return sep;
        };

        var reset_everything = function() {
            // Build the hierarchy
            build_hierarchy();

            // Update the select list
            update_select_list();

            // Update the term list
            update_term_list();

            // Reset everything
            select_options = '';
            seperator = 0;
            list_items = '';
        };

    });

    // The window has loaded
    $( window ).load(function() {

    });

})( jQuery );
