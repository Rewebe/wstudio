// assets/js/approval.js
(function($){
  $(function(){
    const dialog = $('#wg-approval-dialog');
    let approved = dialog.attr('data-approved') === '1';

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
        .attr({'aria-disabled': 'true', 'tabindex': '-1'})
        .prop('disabled', true);
    }

    // Initial setup
    if (approved) {
      enableDownloads();
    } else {
      dialog.find('.wg-approved').hide();
      disableDownloads();
    }

    // Accepter billeder knap
    $(document).on('click', '#wg-approve-btn', function(e){
      e.preventDefault();
      const btn = $(this);
      btn.prop('disabled', true).text(wgAjax.labels.processing);

      $.post(wgAjax.ajax_url, {
        action: 'wg_approve_gallery',
        gallery_id: wgAjax.gallery_id,
        security: wgAjax.nonce
      })
      .done(function(res){
        if (res.success) {
          approved = true;

          dialog.find('.wg-approved')
            .show()
            .find('.wg-time')
            .text(res.data.accepted_time);

          enableDownloads();
          btn.remove();
        } else {
          btn.prop('disabled', false).text(wgAjax.labels.approve_button);
          alert('Fejl ved godkendelse: ' + (res.data || 'Ukendt fejl'));
        }
      })
      .fail(function(){
        btn.prop('disabled', false).text(wgAjax.labels.approve_button);
        alert('Serverfejl. Prøv igen.');
      });
    });
  });
})(jQuery);