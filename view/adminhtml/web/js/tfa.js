define([
  'jquery',
  'Magento_Ui/js/modal/alert',
  'jquery/ui'
], function ($, alert) {
  'use strict';

  $.widget('tfa.updateQr', {
    options: {
      url: '',
      elementId: ''
    },

    /**
     * Bind handlers to events
     */
    _create: function () {
      this._on({
        'click': $.proxy(this._connect, this)
      });
    },

    /**
     * Method triggers an AJAX request to check search engine connection
     * @private
     */
    _connect: function () {
      var result = this.options.failedText,
        element =  $('#' + this.options.elementId),
        self = this,
        params = {},
        msg = '';

      $('#current_password').validation();

      if(!$('#current_password').validation('isValid')) {
        return false;
      }

      params['current_password'] = $('#current_password').val();
      params['require_tfa'] = $('#require_tfa').val();
      params['form_key'] = FORM_KEY;
      $.ajax({
        url: this.options.url,
        showLoader: true,
        data: params
      }).done(function (response) {
        if (response.success) {
          msg = response.message;
          if (msg) {
            alert({content: $.mage.__(msg)});
          }
          $('#qr-img').attr('src', response.qr);
        } else {
          msg = response.errorMessage;

          if (msg) {
            alert({content: $.mage.__(msg)});
          }
        }
      });
    }
  });

  return $.tfa.updateQr;
});
