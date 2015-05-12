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
            dialogEdit: '.btg-dialog-edit',
            dialogAdd: '#btg-dialog-add',
            progressBar: '.btg-progressbar',
            nonce: '#btg_add_term_nonce'
        };

        // Internal info
        self.internal = {
            paused: false, // Is a job currently paused?
            active: false, // Is a job currently running? (job can be active AND paused...)
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

        self.init = function () {

            // Combine defaults with options
            self.options = $.extend({}, self.defaultOptions, options);

            // Get all the existing terms for this taxonomy
            self.getExistingTerms();

            // If the user tries to leave but has terms in their queue, alert them
            $(window).bind('beforeunload', function(){
                if (self.stats.termsToAdd > 0){
                    return "Your terms haven't been created yet! \n\rClick the 'Generate Terms' button at the bottom of the page before you leave.";
                }
            });

            /**
             * Event Handlers
             */

            // Add Terms to Queu button
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

                self._reset(['queue','merge', 'hierarchy', 'select list', 'terms list', 'submit', 'internals']);

                // Scroll to the top of the page
                $("html, body").animate({ scrollTop: 0 }, "medium");
            });

            // Delete term
            self.$el.find(self.options.termListContainer).on('click', 'a.delete', function(e){
                e.preventDefault();

                var id = $(this).data('id');

                for (var i = self.internal.terms.length - 1; i >= 0; i--) {
                    if (self.internal.terms[i].Id == id ){
                        self.internal.terms.splice(i, 1);
                        internal.stats.termsToAdd--;
                        break;
                    }
                }

                self._reset();
            });

            // Edit term
            self.$el.find(self.options.termListContainer).on('click', 'a.edit', function(e){
                e.preventDefault();

                var id = $(this).data('id');

                for (var i = self.internal.terms.length - 1; i >= 0; i--) {
                    if (self.internal.terms[i].Id == id ) {
                        self.$el.find(self.options.dialogEdit+' #name').val(self.internal.terms[i].Name);
                        self.$el.find(self.options.dialogEdit+' #slug').val(self.internal.terms[i].Slug);
                        self.$el.find(self.options.dialogEdit+' #description').val(self.internal.terms[i].Desc);
                        self.$el.find(self.options.dialogEdit+' #id').val(id);
                        break;
                    }
                }

                self.$el.find(self.options.dialogEdit).dialog('open');
            });

            // Generate Terms button
            self.$el.find(self.options.generateTermsButton).on('click', function(e){
                e.preventDefault();

                if (self.stats.termsToAdd === 0 )
                    return false;

                var window_width = $(window).width();

                // If we're on a larger screen, cap the dialog width at 600px
                if ( window_width >= 960){
                    self.$el.find(self.options.dialogAdd).dialog( "option", "width", 600 );
                }

                self._reset(['dialog']);

                self.$el.find(self.options.dialogAdd).dialog('open');

                self._processNextTerm();

            });

        };

        // TODO: Clean this up, handle errors better
        self.handleAJAX = function( term ) {
            var parent = ( typeof term.Parent === 'string' && term.Parent.indexOf('new_') === 0 ) ? self.internal.history[term.Parent] : term.Parent;

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
                    _ajax_nonce: self.$el.find(self.options.nonce).val()
                },
                success: function(data){

                    data = $.parseJSON(data);

                    // Add the new nonce to the hidden field
                    self.$el.find(self.options.nonce).val( data.new_nonce );

                    if ( data.success || data.error == 'term_exists' ) {

                        // Add new ID and old ID so it can be looked up later
                        self.internal.history[term.Id] = data.new_id;

                        // Change the old ID to the new ID on the term
                        self.internal.cache[self.internal.cache.length -1].Id = parseInt(data.new_id);
                        self.internal.cache[self.internal.cache.length -1].Parent = parseInt(data.parent_id);

                        // Update the progress meter
                        self.$el.find(self.options.progressBar).progressbar( "option", "value", ++self.stats.termsAdded / self.stats.termsToAdd * 100 );

                        // Process next term
                        self._processNextTerm();

                    } else {

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
            console.log('createObjects run');
            terms = self._processTerms( terms );
            console.log('terms: '+terms);
            for (var i = 0; i < terms.length; i++) {
                console.log('createObjects loop');
                if ( terms[i][0] === '')
                    continue;

                var key = 'new_'+self.internal.id++,
                    term = {
                        Id: key,
                        Name: self.internal.terms[i][0],
                        Parent: parent,
                        Slug: self.internal.terms[i][1],
                        Desc: self.internal.terms[i][2]
                    };

                self.internal.terms.push(term);
                self.stats.termsToAdd++;
            }
        };

        /**
         * Object Constructors
         */

        var Term = function( id, name, parent, slug, desc ) {
            this.Id = parseInt(id);
            this.Name = name;
            this.Parent = parseInt(parent);
            this.Slug = slug || '';
            this.Desc = desc || '';
        };

        /**
         * Private functions
         */

        self._processTerms = function( terms ){
            console.log('processTerms run');
            var processedTerms = [];

            // Seperate terms by comma, and trim the white space
            for (var i = 0; i < terms.length; i++) {
                console.log('processTerms loop');
                processedTerms.push($.map(terms[i].split(','), $.trim));
            }

            return processedTerms;
        };

        self._processNextTerm = function() {
            // If there are no more terms, finish the job
            if ( self.internal.terms.length === 0 ){
                self._finishJob();
                return;
            }

            // Get and remove a term
            var data = Array.prototype.shift.apply(terms);
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

            console.log('reset run');

            option = option || false;

            if ( option && !$.isArray(option)) {
                option = $.makeArray(option);
            }

            console.log('option='+option);

            if ( $.inArray('queue', option) > -1 || !option ) {
                self.$el.find(self.options.termsField).val('');
            }

            if ( $.inArray('merge', option) > -1 || !option ){
                $.merge(self.internal.terms, self.internal.cache);
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

            if ( $.inArray('dialog', option) > -1 || !option ){
                self._resetDialog();
            }

            if ( $.inArray('progressBar', option) > -1 || !option ){
                self._resetProgressBar();
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
                self.$el.find(self.options.termListContainer).html('<p>No terms yet. Add some below!</p>');
                return;
            }

            for (var i = 0; i < self.internal.hierarchy.length; i++) {
                self._getListItems(self.internal.hierarchy[i]);
            }

            self.$el.find(self.options.termListContainer).html('<ul id="term-list">'+self.internal.listItems+'</ul>');
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

        self._resetDialog = function() {
            // Reset progress bar
            self.$el.find(self.options.progressBar).progressbar({value: 0});

            // Reset the dialog box
            self.$el.find(self.options.dialogAdd).dialog('option', {
                dialogClass: 'btg-dialog-add',
                title: "Generating Terms...",
                buttons: [{
                    text: "Stop",
                    click: function() {
                        $(this).dialog('close');
                    }
                }]
            });
            self.$el.find(self.options.dialogAdd+' .completed').hide();
            self.$el.find(self.options.dialogAdd+' .in-progress').show();
        };

        self._termsFieldIsEmpty = function(){
            return ( self.$el.find(self.options.termsField).val().replace(/\r\n/g, '').replace(/\n/g, '') === '' );
        };

        self._maybeDisableSubmit = function(){
            if ( self.stats.termsToAdd > 0 ) {
                self.$el.find(self.options.generateTermsButton).prop("disabled", false);
            } else {
                self.$el.find(self.options.generateTermsButton).prop("disabled", true);
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

        // TODO: Redo this
        self._finishJob = function() {
            self.internal.active = false;

            // Display "Completed" message in dialog box
            $('.btg-dialog-add .in-progress').hide();
            $('.btg-dialog-add .completed').show();
            var status_text = (self.stats.termsAdded === 1) ? self.stats.termsAdded+' term has' : self.stats.termsAdded+' terms have';
            $('.btg-dialog-add .num-term-created').text(status_text);

            $( "#btg-dialog-add" ).dialog( "option", {
                    title: "Finished adding terms!",
                    buttons: [{
                        text: "Close",
                        click: function() {
                            $(this).dialog('close');
                        }
                    }],
                    dialogClass: 'btg-dialog-complete'
                }
            );

            self._reset();
        };

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
