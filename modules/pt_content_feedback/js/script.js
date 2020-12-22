(function fn($, Drupal) {
  Drupal.AjaxCommands.prototype.scoreContent = function scoreContent(
    ajax,
    response,
  ) {
    if (response.result) {
      $('#feedback-scores').hide();
      $('#feedback-message').show();
    }
  };
})(jQuery, Drupal);
