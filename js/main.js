jQuery(document).ready(function($) {
  $('#facility-filter').on('change', function() {
      var selected = $(this).val();
      $('.park-item').each(function() {
          if (!selected || $(this).hasClass('facility-' + selected)) {
              $(this).show();
          } else {
              $(this).hide();
          }
      });
  });
});