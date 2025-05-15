(function($){
  $(function(){
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
          $('#wg-approval-dialog').attr('data-approved', '1');

          $('#wg-approved-badge')
            .show()
            .find('.wg-time')
            .text(res.data.accepted_time);

          if (typeof window.enableGalleryDownloads === 'function') {
            window.enableGalleryDownloads();
          }

          // Fjern knap og vis badge uden at ændre layout
          btn.closest('.wg-approval-left').find('#wg-approve-btn').remove();
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