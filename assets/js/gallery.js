(function($){
  $(document).ready(function(){
    // LightGallery initialisering
    $('.kundegalleri-wrapper').lightGallery({
      selector: '.gallery-item a',
      download: false
    });

    // Disable højreklik på billeder
    $(document).on('contextmenu', 'img', function(e) {
      e.preventDefault();
    });
  });
})(jQuery);