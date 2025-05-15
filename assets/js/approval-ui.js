(function($){
  $(function(){
    const dialog = $('#wg-approval-dialog');
    const isApproved = dialog.attr('data-approved') === '1';

    function enableDownloads() {
      $('.image-download.disabled, .wg-download-btn.disabled')
        .removeClass('disabled')
        .removeAttr('aria-disabled tabindex');

      $('.zip-download-select')
        .removeClass('disabled')
        .prop('disabled', false);
    }

    function disableDownloads() {
      $('.image-download, .wg-download-btn, .zip-download-select')
        .addClass('disabled')
        .attr({ 'aria-disabled': 'true', 'tabindex': '-1' })
        .prop('disabled', true);
    }

    // Initial rendering baseret på status
    if (isApproved) {
      enableDownloads();
    } else {
      $('#wg-approved-badge').hide();
      disableDownloads();
    }

    // Gør enableDownloads tilgængelig for anden kode
    window.enableGalleryDownloads = enableDownloads;
  });
})(jQuery);