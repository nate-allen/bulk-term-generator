(function( $ ) {
    'use strict';

    // The page is ready
    $(function() {

        $('#add-terms').on('click', function(e){
            e.preventDefault();

            var terms_to_add = $('#terms-to-add').val().split('\n'),
                parent_term  = ( $('#parent_term').val() ) ? $('#parent_term option:selected').text() : false;

            add_terms( terms_to_add );

        });

        var add_terms = function( terms ) {

            var html = '<ul id="term-list">';

            for (var i = 0; i < terms.length; i++) {
                html += '<li>' + terms[i] + '</li>';
            }

            html += '</ul>';

            $('#term-list-container').html(html);

        };

    });

    // The window has loaded
    $( window ).load(function() {

    });

})( jQuery );
