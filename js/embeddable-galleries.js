/**
 * @package Embeddable_Galleries
 */


jQuery(document).ready(function($) {
    var gallery = $('div.gallery');

    if (! gallery.length) {
        return false;
    }

    gallery.append('<div class="embeddable-galleries"><h4 class="embed-gallery">' +embeddable_galleries.embed_text + '</h4><textarea class="embed-code" style="width:100%"></textarea></div>');

    var iframe = '<iframe src="' + embeddable_galleries.embed_url + '" width="' + gallery.width() + '" height="' + gallery.height() + '"></iframe>';

    $('.embed-code').val(iframe);

});