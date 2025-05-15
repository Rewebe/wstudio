(function($){
  $(document).ready(function(){

    $('#kundegalleri-login-form').on('submit', function(e){
      e.preventDefault();

      var email = $('#kunde_email').val();
      var password = $('#kunde_password').val();
      var responseBox = $('#kundegalleri-login-response');
      var submitButton = $(this).find('button[type="submit"]');

      // Disable knap og vis spinner
      submitButton.prop('disabled', true).text('Logger ind...');

      $.post(kundegalleri_email_vars.ajax_url, {
        action: 'kundegalleri_ajax_login',
        kunde_email: email,
        kunde_password: password
      }, function(response) {
        console.log('Login response:', response); // fallback debug

        if (response.success && response.data && response.data.goto) {
          // Sæt cookie via JS
          document.cookie = "kundegalleri_loggedin=1;path=/;max-age=3600";

          // Redirect
          window.location.href = response.data.goto;
        } else {
          responseBox.html('<div class="kundegalleri-login-error">Redirect mangler.</div>');
          submitButton.prop('disabled', false).text('Login');
        }
      }).fail(function() {
        responseBox.html('<div class="kundegalleri-login-error">Serverfejl. Prøv igen.</div>');
        submitButton.prop('disabled', false).text('Login');
      });

    });

  });
})(jQuery);