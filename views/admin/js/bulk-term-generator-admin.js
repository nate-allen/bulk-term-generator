(function( $ ) {
    'use strict';

    // The page is ready
    $(function() {

        var terms_array      = [], // An array of each term and its info.
            terms_array_temp = [], // While terms are sent via ajax, they're held here temporarily.
            hierarchy        = [], // A hierachal version of the terms_array.
            select_options   = '', // Select options are stored here temporarily.
            list_items       = '', // Unordered list items stored here temporarily.
            seperator        = 0,  // Temporary number for keeping track of nesting count.
            num_terms_to_add = 0,  // Number of terms waiting to be added. Used for progress meter.
            num_terms_added  = 0,  // Number of terms that have been added. Used for preogress meter.
            new_id           = 1,  // A temp id for new terms.
            new_ids          = {}; // When a temp ID becomes a real one, store the info here so it can be looked up.


        $('#add-terms').on('click', function(e){
            e.preventDefault();

            if ($('#terms-to-add').val() === '')
                return false;

            // The new terms to be added, and the parent they should be added to
            var terms_to_add = $('#terms-to-add').val().split('\n'),
                parent_term  = ( $('#parent_term').val() ) ? $('#parent_term').val() : 0;

            // If this is the first time, get the existing terms
            if ( new_id === 1 ) {
                var existing_terms = window.btg_object.btg_terms_list;

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

        });

        $('#term-list-container').on('click', 'a.delete', function(e){

            e.preventDefault();

            var id = $(this).data('id');

            for (var i = terms_array.length - 1; i >= 0; i--) {
                if (terms_array[i].Id == id ){
                    terms_array.splice(i, 1);
                    break;
                }
            }

            reset_everything();

        });
        $('#term-list-container').on('click', 'a.edit', function(e){
            e.preventDefault();
            var id = $(this).data('id');

            for (var i = terms_array.length - 1; i >= 0; i--) {
                if (terms_array[i].Id == id ) {
                    $('.btg-dialog-edit #name').val(terms_array[i].Name);
                    $('.btg-dialog-edit #slug').val(terms_array[i].Slug);
                    $('.btg-dialog-edit #description').val(terms_array[i].Desc);
                    $('.btg-dialog-edit #id').val(id);
                    break;
                }
            }

            $('#btg-dialog-edit').dialog('open');
        });

        $( '#btg-dialog-add' ).dialog({
            autoOpen: false,
            dialogClass: 'btg-dialog-add',
            closeOnEscape: false,
            resizable: false,
            modal: true,
            width: '80%',
            buttons: [
                {
                  text: 'Stop',
                  click: function() {
                    $(this).dialog('close');
                  }
                }
              ]
        });
        $( '#btg-progressbar' ).progressbar({
            value: 0
        });

        $( '#btg-dialog-edit' ).dialog({
            autoOpen: false,
            dialogClass: 'btg-dialog-edit',
            closeOnEscape: true,
            resizable: true,
            modal: true,
            width: 'auto',
            buttons: [
                {
                    text: 'Save',
                    click: function(){
                        save_term_edit(this);
                    }
                }
            ]
        });

        $('#btg-generate-terms-button').on('click', function(e){

            e.preventDefault();

            if (num_terms_to_add === 0 )
                return false;

            var window_width = $(window).width();

            // If we're on a larger screen, cap the dialog width at 600px
            if ( window_width >= 960){
                $('#btg-dialog-add').dialog( "option", "width", 600 );
            }

            $('#btg-dialog-add').dialog('open');

            cycle_terms( terms_array );

        });

        var cycle_terms = function ( terms ) {

            // If there are no more terms, reset the terms_array
            if ( terms.length === 0 ){
                // Reset number of terms to add and terms added number
                num_terms_to_add = 0;
                num_terms_added  = 0;
                // Reset everything else
                reset_everything();
                return;
            }

            var data = Array.prototype.shift.apply(terms);
            terms_array_temp.push(data);

            // If it's a new term, create it w/ ajax
            // Else, call function again and try next term
            if (typeof data.Id != 'number'){
                handle_ajax( data );
            } else {
                cycle_terms( terms );
            }

        };

        var create_objects = function( terms, parent ) {

            terms = process_terms( terms );

            for (var i = 0; i < terms.length; i++) {
                if ( terms[i][0] === '')
                    continue;

                num_terms_to_add++;

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

            if ( hierarchy.length === 0 ){
                $('#term-list-container').html('<p>No terms yet. Add some below!</p>');
                return;
            }

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
                list_items += (typeof data.value.Id != 'number') ? '<a href="#" class="edit" data-id="'+data.value.Id+'">Edit</a><a href="#" class="delete" data-id="'+data.value.Id+'">X</a>' : '';
                list_items += '<ul>';
                for (var i = 0; i < data.children.length; i++) {
                    get_list_items( data.children[i] );
                }
                list_items += '</ul></li>';
            } else {
                list_items += '<li>'+data.value.Name;
                list_items += (typeof data.value.Id != 'number') ? '<a href="#" class="edit" data-id="'+data.value.Id+'">Edit</a><a href="#" class="delete" data-id="'+data.value.Id+'">X</a>' : '';
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

        var handle_ajax = function( term ) {

            var parent = ( typeof term.Parent === 'string' && term.Parent.indexOf('new_') === 0 ) ? new_ids[term.Parent] : term.Parent;

            // Display the term name under the progress meter
            $('.progress-status em').text('"'+term.Name+'"');

            $.ajax({
                type: "POST",
                url: window.btg_object.admin_url,
                data: {
                    action: "btg_add_term",
                    term_name: term.Name,
                    taxonomy: window.btg_object.taxonomy,
                    parent: parent,
                    slug: term.Slug,
                    desc: term.Desc,
                    _ajax_nonce: $('#btg_add_term_nonce').val()
                },
                success: function(data){

                    data = $.parseJSON(data);

                    // Add the new nonce to the hidden field
                    $('#btg_add_term_nonce').val( data.new_nonce );

                    if ( data.success || data.error == 'term_exists' ) {

                        // Add new ID and old ID so it can be looked up later
                        new_ids[term.Id] = data.new_id;

                        // Change the old ID to the new ID on the term
                        terms_array_temp[terms_array_temp.length -1].Id = parseInt(data.new_id);
                        terms_array_temp[terms_array_temp.length -1].Parent = parseInt(data.parent_id);

                        // Update the progress meter
                        $( '#btg-progressbar' ).progressbar( "option", "value", ++num_terms_added / num_terms_to_add * 100 );

                        // Run terms_array again to do next term
                        cycle_terms(terms_array);

                    } else {

                        console.log(data);

                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    console.log(errorThrown);
                }
            });

        };

        var save_term_edit = function(self) {

            var id   = $(self).find('#id').val(),
                name = $(self).find('#name').val(),
                slug = $(self).find('#slug').val(),
                desc = $(self).find('#description').val();

            for (var i = terms_array.length - 1; i >= 0; i--) {
                if (terms_array[i].Id == id ) {
                    terms_array[i].Name = name;
                    terms_array[i].Slug = slug;
                    terms_array[i].Desc = desc;
                    break;
                }
            }

            reset_everything();

            $(self).dialog('close');
        };

        var reset_everything = function() {

            // Combine terms_array and terms_array_temp
            $.merge(terms_array, terms_array_temp);

            // Build the hierarchy
            build_hierarchy();

            // Update the select list
            update_select_list();

            // Update the term list
            update_term_list();

            // Reset everything else
            select_options = '';
            seperator = 0;
            list_items = '';
            terms_array_temp = [];
            new_ids = {};

            // Enable/Disable Submit Button
            if ( num_terms_to_add > 0 ) {
                $('#btg-generate-terms-button').prop("disabled", false);
            } else {
                $('#btg-generate-terms-button').prop("disabled", true);
            }

        };

        // If the user tries to leave but has terms in their queue, alert them
        $(window).bind('beforeunload', function(){
            if (num_terms_to_add > 0){
                return "Your terms haven't been created yet! \n\rClick the 'Generate Terms' button at the bottom of the page before you leave.";
            }
        });

    });

    // The window has loaded
    $( window ).load(function() {

    });

})( jQuery );
