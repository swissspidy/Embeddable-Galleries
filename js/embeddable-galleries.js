/**
 * @package Embeddable_Galleries
 */

/*
jQuery(document).ready(function($) {
    var gallery = $('div.gallery'), data = [];

    $.each(gallery, function() {
        data = {
            text: 'test',
            embed_url: embeddable_galleries.embed_url + '1'
        }
    })

    var Model = new Backbone.Model({
        data: [data]
    });


    var View = Backbone.View.extend({
        el: "#container",
        events: {
            "click button": "render"
        },
        render: function(){
            var data = this.model.get('data');
            for(var i = 0; i<data.length; i++){
                var variables = { href: data[i].href, text: data[i].text };
                var li = _.template( $("#embed-gallery-template").html(), variables );
                this.$el.find('ul').append(li);
            }
        }
    });

    var view = new View({ model: Model });

    var gallery = $('div.gallery');

    if (! gallery.length) {
        return false;
    }

    gallery.append('<div class="embeddable-galleries"><h4 class="embed-gallery">' +embeddable_galleries.embed_text + '</h4><textarea class="embed-code" style="width:100%"></textarea></div>');

    var iframe = '<iframe src="' + embeddable_galleries.embed_url + '" width="' + gallery.width() + '" height="' + gallery.height() + '"></iframe>';

    $('.embed-code').val(iframe);

});
*/

( function($) {
    var embeddableGalleries = window.embeddableGalleries || {};

    embeddableGalleries.Data.Galleries = $('.gallery');

    embeddableGalleries.hasGalleries =  embeddableGalleries.Data.Galleries.length >= 1;

    embeddableGalleries.Run = {
        init: function() {
            var self = this;

            // Initialize script

            if ( ! embeddableGalleries.hasGalleries ) {
                return false;
            }

            embeddableGalleries.Run.addEmbedLink();
        },

        showModal: function( post, gallery ) {
            var embedUrl = this.getEmbedUrl( post, gallery),
                galleryWidth = $( '#gallery-' + gallery).outerWidth(),
                galleryHeight = $( '#gallery-' + gallery).outerHeight();

            if ( embedUrl === false ) {
                console.log('Error loading embed data');
                return false;
            }

            var gallery = {
                    title: embeddableGalleries.Data.Modal.Title,
                    description: embeddableGalleries.Data.Modal.Description,
                    close: embeddableGalleries.Data.Modal.Close,
                    embed_code: this.getEmbedCode(embedUrl, galleryWidth, galleryHeight)
                },
                overlay = _.template( $("#embed-gallery-modal").html(), gallery );

            $( 'body').append(overlay);
            $( '.embed-gallery-modal').addClass('visible');
        },

        getEmbedCode: function( src, width, height ) {
           var vars = {
               src:src,
               width:width,
               height:height
           };

           return _.template( $.trim($("#embed-gallery-iframe").html()), vars );
        },

        getEmbedUrl: function( post, gallery ) {
            var result = false,
                data = {
                    action: 'embeddable_galleries',
                    method: 'get_embed_link',
                    post: post,
                    gallery: gallery
                };

            $.ajax({
                type:    "POST",
                url:     ajaxurl,
                async:   false,
                data:    data,
                success: function(response) {
                    if ( $.isNumeric(response) || $.isNumeric == "" ) {
                        result = false;
                    }

                    result = response;
                }
            });

            return result;
        },

        addEmbedLink: function() {
            $( '.gallery:not(.embeddable)' ).each(function(i) {
                var gallery = $(this),
                    embedLink = _.template( $("#embed-gallery-link").html(), { text: embeddableGalleries.Data.EmbedText } );
                gallery.append(embedLink);
                gallery.addClass('embeddable');
            });
        },

        clickHandler: function() {
            $( '.gallery.embeddable .embed-link').on('click', function(e) {
                e.preventDefault();

                var post = $(this).parents('.gallery').attr('class').match(/galleryid-\d+/)[0].slice(10),
                    gallery = $(this).parents('.gallery').attr('id').slice(8);

                embeddableGalleries.Run.showModal( post, gallery );
            });

            $( '.embed-modal-close').on('click', function(e) {
                e.preventDefault();

                $('.embed-gallery-modal').removeClass('visible');
            });

            $( 'body').on('click', function(e){
                console.log(e.target);
                if ( $(e.target).hasClass('embed-modal-close') ) {
                    e.preventDefault();

                    $('.embed-gallery-modal').removeClass('visible');
                }
            });
        }
    };

    $(document).ready(function() {
        // Run, Forrest, run!
        embeddableGalleries.Run.init();

        // Add embed links using timeouts, to support infinite scrolling feature
        setTimeout( function() {
            embeddableGalleries.Run.addEmbedLink();
        }, 2000 );

        embeddableGalleries.Run.clickHandler();
    });

})( jQuery );