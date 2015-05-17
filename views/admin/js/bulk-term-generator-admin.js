/**
 * Bulk Term Generator
 * Author: @nate-allen
 */

;(function ( $ ) {

    $.bulkTermGenerator = function ( el, options ) {
        var self = this;

        // jQuery and DOM versions of the element
        self.$el = $(el);
        self.el = el;

        // Reverse reference to the DOM object
        self.$el.data( "bulkTermGenerator" , self );

        // Default options
        self.defaultOptions = {
            addTermsBtn: '.btg-add-terms',
            termsField: '.btg-terms-to-add',
            generateTermsButton: '.btg-generate-terms-button',
            parentSelect: '#parent_term',
            termListContainer: '.btg-term-list-container',
            dialog: '#btg-dialog',
            progressBar: '.btg-progressbar',
            nonce: '#btg_add_term_nonce',
            addTemplate: btg_object.plugin_dir+'/views/admin/templates/dialog_add.html',
            editTemplate: btg_object.plugin_dir+'/views/admin/templates/dialog_edit.html'
        };

        // Internal info
        self.internal = {
            paused: false, // Is a job currently paused?
            active: false, // Is a job currently running? (jobs can be active AND paused)
            terms: [], // An array of each term and its info.
            cache: [], // Temporary storage while terms are sent via ajax
            hierarchy: [], // Object containing the hieracrchy of terms
            history: {}, // When temp IDs become real IDs, store info so it can be referenced later
            selectOptions: '', // (HTML) select options
            listItems: '', // (HTML) unordered list items
            seperators: 0, // Temporary count for nesting terms
            id: 1, // A temporary ID for each new term
        };

        // Stats for progress meter
        self.stats = {
            termsToAdd: 0,
            termsAdded: 0,
            termsSkipped: 0,
            errors: 0,
        };

        // Templates
        self.templates = {
            add: '',
            edit: ''
        };

        self.init = function () {

            // Combine defaults with options
            self.options = $.extend({}, self.defaultOptions, options);

            // Get templates and assign them to the template variables
            $.get(self.options.addTemplate, function(template){
                self.templates.add = template.format(btg_object.i18n.creating+' ', btg_object.i18n.done);
            });
            $.get(self.options.editTemplate, function(template){
                self.templates.edit = template.format(btg_object.i18n.name, btg_object.i18n.slug, btg_object.i18n.description);
            });

            // Get all the existing terms for this taxonomy
            self.getExistingTerms();

            // If the user tries to leave but has terms in their queue, alert them
            $(window).bind('beforeunload', function(){
                if (self.stats.termsToAdd > 0){
                    return btg_object.i18n.warning_line_1+"\n\r"+btg_object.i18n.warning_line_2;
                }
            });

            // Setup the dialog box
            $(self.options.dialog).dialog({
                autoOpen: false,
                closeOnEscape: false,
                modal: true,
            });

            /**
             * Event Handlers
             */

            // Add Terms to Queu button clicked
            self.$el.find(self.options.addTermsBtn).on('click', function(e){
                e.preventDefault();

                // If the field is empty, do nothing
                if ( self._termsFieldIsEmpty() )
                    return false;

                // Get the terms to be added, and the parent term (if any)
                var terms_to_add = self.$el.find(self.options.termsField).val().split('\n'),
                    parent_term  = self.$el.find(self.options.parentSelect).val();

                parent_term  = (parent_term) ? parent_term : 0;

                self.createObjects( terms_to_add, parent_term);

                self._reset(['queue', 'merge', 'hierarchy', 'select list', 'terms list', 'submit', 'internals']);

                // Scroll to the top of the page
                $("html, body").animate({ scrollTop: 0 }, "medium");
            });

            // Delete term icon clicked
            self.$el.find(self.options.termListContainer).on('click', 'a.delete', function(e){
                e.preventDefault();

                var id = $(this).data('id');

                for (var i = self.internal.terms.length - 1; i >= 0; i--) {
                    if (self.internal.terms[i].Id == id ){
                        self.internal.terms.splice(i, 1);
                        self.stats.termsToAdd--;
                        break;
                    }
                }

                self._reset(['hierarchy', 'select list', 'terms list', 'submit', 'internals']);
            });

            // Edit term button clicked
            self.$el.find(self.options.termListContainer).on('click', 'a.edit', function(e){
                e.preventDefault();

                var id = $(this).data('id');

                $(self.options.dialog).html(self.templates.edit);

                $(self.options.dialog).dialog({
                    dialogClass: 'btg-dialog-edit',
                    title: btg_object.i18n.edit_term,
                    resizable: true,
                    width: 'auto',
                    buttons: [
                        {
                            text: btg_object.i18n.save,
                            click: function(){
                                var id   = $(this).find('#id').val(),
                                    name = $(this).find('#name').val(),
                                    slug = $(this).find('#slug').val(),
                                    desc = $(this).find('#description').val();

                                for (var i = self.internal.terms.length - 1; i >= 0; i--) {
                                    if (self.internal.terms[i].Id == id ) {
                                        self.internal.terms[i].Name = name;
                                        self.internal.terms[i].Slug = slug;
                                        self.internal.terms[i].Desc = desc;
                                        break;
                                    }
                                }

                                self._reset(['hierarchy', 'select list', 'terms list', 'submit', 'internals']);

                                $(this).dialog('close');
                            }
                        }
                    ]
                });

                for (var i = self.internal.terms.length - 1; i >= 0; i--) {
                    if (self.internal.terms[i].Id == id ) {
                        $(self.options.dialog+' #name').val(self.internal.terms[i].Name);
                        $(self.options.dialog+' #slug').val(self.internal.terms[i].Slug);
                        $(self.options.dialog+' #description').val(self.internal.terms[i].Desc);
                        $(self.options.dialog+' #id').val(id);
                        break;
                    }
                }

                $(self.options.dialog).dialog('open');
            });

            // Generate Terms button clicked
            self.$el.find(self.options.generateTermsButton).on('click', function(e){
                e.preventDefault();

                if (self.stats.termsToAdd === 0 )
                    return false;

                self.internal.active = true;

                $(self.options.dialog).html(self.templates.add);

                // Create the dialog box
                $(self.options.dialog).dialog('option', {
                    dialogClass: 'btg-dialog-add',
                    title: btg_object.i18n.generating_terms,
                    buttons: [
                        {
                            text: btg_object.i18n.stop,
                            class: 'ui-button-save',
                            click: function() {
                                self.internal.active = false;
                                self.internal.paused = false;
                                $(this).dialog('close');
                            }
                        },
                        {
                            text: btg_object.i18n.pause,
                            class: 'ui-button-pause',
                            click: function(){
                                self.internal.paused = ( self.internal.paused ) ? false : true;
                                if ( self.internal.paused ) {
                                    $('.ui-button-pause .ui-button-text').text(btg_object.i18n.continue);
                                    $('.ui-button-pause').toggleClass('ui-button-pause ui-button-continue');
                                } else {
                                    $('.ui-button-continue .ui-button-text').text('Pause');
                                    $('.ui-button-continue').toggleClass('ui-button-continue ui-button-pause');
                                    self.internal.active = true;
                                    self._processNextTerm();
                                }
                            }
                        }
                    ]
                });

                // Create the progress meter
                $(self.options.progressBar).progressbar({value: 0});

                var window_width = $(window).width();

                // If we're on a larger screen, cap the dialog width at 600px
                if ( window_width >= 960){
                    $(self.options.dialog).dialog( 'option', 'width', 600 );
                } else {
                    $(self.options.dialog).dialog( 'option', 'width', '80%' );
                }

                $(self.options.dialog).dialog('open');

                self._processNextTerm();

            });

        };

        self.handleAJAX = function( term ) {
            var parent = ( typeof term.Parent === 'string' && term.Parent.indexOf('new_') === 0 ) ? self.internal.history[term.Parent] : term.Parent;

            // Display the term name under the progress meter
            $('.progress-status em').text('"'+term.Name+'"');

            $.ajax({
                type: 'POST',
                url: window.btg_object.admin_url,
                data: {
                    action: 'btg_add_term',
                    term_name: term.Name,
                    taxonomy: window.btg_object.taxonomy,
                    parent: parent,
                    slug: term.Slug,
                    desc: term.Desc,
                    _ajax_nonce: self.$el.find(self.options.nonce).val()
                },
                success: function(data){

                    data = $.parseJSON(data);

                    // Add the new nonce to the hidden field
                    self.$el.find(self.options.nonce).val( data.new_nonce );

                    if ( data.success || data.error == 'term_exists' ) {

                        if ( data.error == 'term_exists' ) {
                            // Increase "terms skipped" stat
                            self.stats.termsSkipped++;
                        }

                        // Add new ID and old ID so it can be looked up later
                        self.internal.history[term.Id] = data.new_id;

                        // Change the old ID to the new ID on the term
                        self.internal.cache[self.internal.cache.length -1].Id = parseInt(data.new_id);
                        self.internal.cache[self.internal.cache.length -1].Parent = parseInt(data.parent_id);

                        // Update the progress meter
                        $(self.options.progressBar).progressbar( 'option', 'value', ++self.stats.termsAdded / self.stats.termsToAdd * 100 );

                        if ( self.internal.active ){
                            // Process next term
                            self._processNextTerm();
                        } else {
                            // Stop button was pressed
                            self._reset(['merge', 'hierarchy', 'select list', 'terms list', 'dialog', 'internals', 'submit']);
                            self.stats.termsToAdd = self.stats.termsToAdd - (self.stats.termsAdded + self.stats.termsSkipped);
                            self.stats.termsAdded = 0;
                            self.stats.termsSkipped = 0;
                        }

                    } else {

                        self.stats.errors++;
                        console.log(data);

                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    console.log(errorThrown);
                }
            });
        };

        self.getExistingTerms = function() {
            var existingTerms = window.btg_object.btg_terms_list;

            for (var i = 0; i < existingTerms.length; i++) {
                var term = {
                    Id : parseInt(existingTerms[i].Id),
                    Name : existingTerms[i].Name,
                    Parent : parseInt(existingTerms[i].Parent)
                };
                self.internal.terms.push(term);
            }
        };

        self.createObjects = function( terms, parent ){
            terms = self._processTerms( terms );

            for (var i = 0; i < terms.length; i++) {

                if ( terms[i][0] === '')
                    continue;

                var key = 'new_'+self.internal.id++,
                    term = {
                        Id: key,
                        Name: terms[i][0],
                        Parent: parent,
                        Slug: terms[i][1] || '',
                        Desc: terms[i][2] || ''
                    };

                self.internal.terms.push(term);
                self.stats.termsToAdd++;
            }
        };

        /**
         * Private functions
         */

        self._processTerms = function( terms ){

            var processedTerms = [];

            // Seperate terms by comma, and trim the white space
            for (var i = 0; i < terms.length; i++) {

                processedTerms.push($.map(terms[i].split(','), $.trim));
            }

            return processedTerms;
        };

        self._processNextTerm = function() {

            // If job is paused or inactive, stop
            if ( self.internal.paused || !self.internal.active )
                return;

            // If there are no more terms, finish the job
            if ( self.internal.terms.length === 0 ){
                self._finishJob();
                return;
            }

            // Get and remove a term
            var data = Array.prototype.shift.apply(self.internal.terms);
            // Temporarily store it
            self.internal.cache.push(data);

            // If it's a new term, create it w/ ajax
            // Else, call function again and try next term
            if (typeof data.Id != 'number'){
                self.handleAJAX( data );
            } else {
                self._processNextTerm();
            }
        };

        self._reset = function(option){
            option = option || false;

            if ( option && !$.isArray(option)) {
                option = $.makeArray(option);
            }

            if ( $.inArray('queue', option) > -1 || !option ) {
                self.$el.find(self.options.termsField).val('');
            }

            if ( $.inArray('merge', option) > -1 || !option ){
                $.merge(self.internal.cache, self.internal.terms);
                self.internal.terms = self.internal.cache;
                self.internal.cache = '';
            }

            if ( $.inArray('hierarchy', option) > -1 || !option ){
                self._buildHierarchy();
            }

            if ( $.inArray('select list', option) > -1 || !option ){
                self._updateSelectList();
            }

            if ( $.inArray('terms list', option) > -1 || !option ){
                self._updateTermList();
            }

            if ( $.inArray('stats', option) > -1 || !option ){
                self._resetStats();
            }

            if ( $.inArray('internals', option) > -1 || !option ){
                self._resetInternals();
            }

            if ( $.inArray('submit', option) > -1 || !option ){
                self._maybeDisableSubmit();
            }

        };

        self._buildHierarchy = function() {
            var roots = [], children = {};

            // Find the top level nodes and hash the children based on parent
            for (var i = 0, len = self.internal.terms.length; i < len; ++i) {
                var item = self.internal.terms[i],
                    p = item.Parent,
                    target = !p ? roots : (children[p] || (children[p] = []));

                target.push({ value: item });
            }

            // Recursively build the tree
            var findChildren = function(parent) {
                if (children[parent.value.Id]) {
                    parent.children = children[parent.value.Id];
                    for (var i = 0, len = parent.children.length; i < len; ++i) {
                        findChildren(parent.children[i]);
                    }
                }
            };

            // Enumerate through to handle the case where there are multiple roots
            for (var j = 0, length = roots.length; j < length; ++j) {
                findChildren(roots[j]);
            }

            self.internal.hierarchy = roots;
        };

        self._updateSelectList = function() {
            for (var i = 0; i < self.internal.hierarchy.length; i++) {
                self._getSelectOptions(self.internal.hierarchy[i]);
            }

            self.$el.find(self.options.parentSelect).empty().append('<option></option>'+self.internal.selectOptions);
        };

        self._updateTermList = function() {
            if ( self.internal.hierarchy.length === 0 ){
                self.$el.find(self.options.termListContainer).html('<p>'+btg_object.i18n.no_terms_yet+'</p>');
                return;
            }

            for (var i = 0; i < self.internal.hierarchy.length; i++) {
                self._getListItems(self.internal.hierarchy[i]);
            }

            self.$el.find(self.options.termListContainer).empty().html('<ul id=\'term-list\'>'+self.internal.listItems+'</ul>');
        };

        self._resetStats = function() {
            self.stats.termsToAdd = 0;
            self.stats.termsAdded = 0;
            self.stats.termsSkipped = 0;
            self.stats.errors = 0;
        };

        self._resetInternals = function() {
            // Note: The only internals that don't get reset are "terms", "history", and "id"
            self.internal.paused = false;
            self.internal.active = false;
            self.internal.cache = [];
            self.internal.selectOptions = '';
            self.internal.listItems = '';
            self.internal.seperators = 0;
        };

        self._termsFieldIsEmpty = function(){
            return ( self.$el.find(self.options.termsField).val().replace(/\r\n/g, '').replace(/\n/g, '') === '' );
        };

        self._maybeDisableSubmit = function(){
            if ( self.stats.termsToAdd > 0 ) {
                self.$el.find(self.options.generateTermsButton).prop('disabled', false);
            } else {
                self.$el.find(self.options.generateTermsButton).prop('disabled', true);
            }
        };

        self._getSelectOptions = function( data ) {
            self.internal.selectOptions += '<option value="'+data.value.Id+'" data-parent="'+data.value.Parent+'" data-name="'+data.value.Name+'">'+self._createSeperators(self.internal.seperators)+data.value.Name+'</option>';
            if ( data.children ) {
                ++self.internal.seperators;
                for (var i = 0; i < data.children.length; i++) {
                    self._getSelectOptions( data.children[i] );
                }
                --self.internal.seperators;
            }
        };

        self._getListItems = function( data ) {
            if (data.children) {
                self.internal.listItems += '<li>'+data.value.Name;
                self.internal.listItems += (typeof data.value.Id != 'number') ? '<a href="#" class="edit" data-id="'+data.value.Id+'"><i class="fa fa-pencil"></i></a><a href="#" class="delete" data-id="'+data.value.Id+'"><i class="fa fa-times"></i></a>' : '';
                self.internal.listItems += '<ul>';
                for (var i = 0; i < data.children.length; i++) {
                    self._getListItems( data.children[i] );
                }
                self.internal.listItems += '</ul></li>';
            } else {
                self.internal.listItems += '<li>'+data.value.Name;
                self.internal.listItems += (typeof data.value.Id != 'number') ? '<a href="#" class="edit" data-id="'+data.value.Id+'"><i class="fa fa-pencil"></i></a><a href="#" class="delete" data-id="'+data.value.Id+'"><i class="fa fa-times"></i></a>' : '';
                self.internal.listItems += '</li>';
            }
        };

        self._createSeperators = function( num ) {
            var sep = '';
            for (var i = 0; i < num; i++) {
                sep += '&#8212;';
            }
            return sep;
        };

        self._finishJob = function() {
            self.internal.active = false;

            // Display "Completed" message in dialog box
            $(self.options.dialog+' .btg-dialog-add .in-progress').hide();
            $(self.options.dialog+'.btg-dialog-add .completed').show();
            var status_text = (self.stats.termsAdded === 1) ? btg_object.i18n.term_added.format(self.stats.termsAdded) : btg_object.i18n.terms_added.format(self.stats.termsAdded);
            $(self.options.dialog+'.btg-dialog-add .num-term-created').text(status_text);

            $(self.options.dialog).dialog( "option",
                {
                    title: btg_object.i18n.finished_adding_terms,
                    buttons: [{
                        text: btg_object.i18n.close,
                        click: function() {
                            $(this).dialog('close');
                        }
                    }],
                    dialogClass: 'btg-dialog-complete'
                }
            );

            self._reset();
        };

        // Add a "format" function to the String prototype
        if (!String.prototype.format) {
            String.prototype.format = function() {
                var args = arguments;
                return this.replace(/{(\d+)}/g, function(match, number) {
                    return typeof args[number] != 'undefined' ? args[number] : match;
                });
            };
        }

        /*********************/

        // Run initializer
        self.init();
    };

    $.fn.bulkTermGenerator = function( options ) {
        return this.each(function () {
            (new $.bulkTermGenerator(this, options));
        });
    };

    // Page is ready
    $(function() {
        $('#btg-generate-terms').bulkTermGenerator();
    });

})( jQuery );
