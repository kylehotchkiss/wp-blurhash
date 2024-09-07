jQuery(document).ready(function($) {
  $('#js-blurhash-run-cron').on('click', function() {
      // Disable the button and show the loader
      $(this).prop('disabled', true);
      $('#blurhash-loader').show();

      // Send an AJAX request
      $.post(blurhash_vars.ajax_url, {
          action: 'run_blurhash_cron',
          nonce: blurhash_vars.nonce
      }, function(response) {
          // Enable the button and hide the loader when the request completes
          $('#js-blurhash-run-cron').prop('disabled', false);
          $('#blurhash-loader').hide();

          if (response.success) {
              $('#js-blurhash-count-completed').text(response.data.completed);
              $('#js-blurhash-count-pending').text(response.data.pending);
          } else {
              alert('Error: ' + response.data);
          }
      });
  });
});
