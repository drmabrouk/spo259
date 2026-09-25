jQuery(document).ready(function ($) {
  // Mobile sidebar toggle
  $('#spMobileToggle').on('click', function () {
    $('.sp-sidebar-floating').toggleClass('open');
  });

  // Floating label initial check
  function updateFloatingLabels() {
    $('.sp-floating-input, .sp-floating-select').each(function () {
      if ($(this).val() && $(this).val().trim() !== '') {
        $(this).addClass('has-value');
      } else {
        $(this).removeClass('has-value');
      }
    });
  }

  updateFloatingLabels();

  $(document).on('change input blur', '.sp-floating-input, .sp-floating-select', function () {
    updateFloatingLabels();
  });

  // Modal handler helper
  window.spOpenModal = function (modalId) {
    $('#' + modalId).css('display', 'flex').addClass('active');
  };

  window.spCloseModal = function (modalId) {
    $('#' + modalId).css('display', 'none').removeClass('active');
  };

  $(document).on('click', '.sp-modal-close, .sp-modal-overlay', function (e) {
    if (e.target === this) {
      $(this).closest('.sp-modal-overlay').css('display', 'none').removeClass('active');
    }
  });
});
