function sendKundeEmail(postId) {
    var subject = jQuery('#email_subject').val();
    var body = jQuery('#email_body').val();

    jQuery.post(kundegalleri_email_vars.ajax_url, {
        action: 'send_customer_email',
        post_id: postId,
        email_subject: subject,
        email_body: body
    }, function(response) {
        var responseDiv = jQuery('#email-response-' + postId);
        if (response.success) {
            responseDiv.html('<div style="color:green;">' + response.data + '</div>');
        } else {
            responseDiv.html('<div style="color:red;">' + response.data + '</div>');
        }
    });
}