jQuery(document).ready(function ($) {
  // Mobile sidebar toggle
  $('#spMobileToggle').on('click', function () {
    $('.sp-sidebar-floating').toggleClass('open');
  });

  // Floating label initial check & dynamic listener
  function updateFloatingLabels() {
    $('.sp-floating-input, .sp-floating-select').each(function () {
      if ($(this).val() && $(this).val().toString().trim() !== '') {
        $(this).addClass('has-value');
      } else {
        $(this).removeClass('has-value');
      }
    });
  }

  window.spUpdateFloatingLabels = updateFloatingLabels;

  updateFloatingLabels();

  $(document).on('change input blur focus', '.sp-floating-input, .sp-floating-select', function () {
    updateFloatingLabels();
  });

  $(document).on('reset', 'form', function () {
    setTimeout(updateFloatingLabels, 50);
  });

  // Reusable Modal Open/Close Helpers
  window.spOpenModal = function (modalId) {
    $('#' + modalId).css('display', 'flex').addClass('active');
    updateFloatingLabels();
  };

  window.spCloseModal = function (modalId) {
    $('#' + modalId).css('display', 'none').removeClass('active');
  };

  $(document).on('click', '.sp-modal-close, .sp-modal-overlay', function (e) {
    if (e.target === this) {
      $(this).closest('.sp-modal-overlay').css('display', 'none').removeClass('active');
    }
  });

  // Button Loading State Helper
  window.spSetButtonLoading = function (btnElement, isLoading, originalText) {
    var $btn = $(btnElement);
    if (isLoading) {
      $btn.data('orig-text', originalText || $btn.html());
      $btn.prop('disabled', true).css('opacity', '0.7').html('Processing...');
    } else {
      $btn.prop('disabled', false).css('opacity', '1').html($btn.data('orig-text') || originalText);
    }
  };
});
