$(document).ready(function() { 

  //
  // jQuery.payment form validation
  //
  
  $('[data-numeric]').payment('restrictNumeric');
  $('.card-number').payment('formatCardNumber');
  $('.card-exp').payment('formatCardExpiry');
  $('.card-cvc').payment('formatCardCVC');

  $.fn.toggleInputError = function(erred) {
    this.parent('fieldset').toggleClass('has-error', erred);
    return this;
  };

  $('.card-number').change(function(e) {
    var cardType = $.payment.cardType($('.card-number').val());
    $('.card-icon').attr('data-brand', cardType);
  });

  $('form').submit(function(e) {
    e.preventDefault();

    var cardType = $.payment.cardType($('.card-number').val());
    $('.card-number').toggleInputError(!$.payment.validateCardNumber($('.card-number').val()));
    $('.card-exp').toggleInputError(!$.payment.validateCardExpiry($('.card-exp').payment('cardExpiryVal')));
    $('.card-cvc').toggleInputError(!$.payment.validateCardCVC($('.card-cvc').val(), cardType));
    $('.card-icon').attr('data-brand', cardType);
  });

  //
  // Stripe token generation
  //
  
  var $form = $('#subscription-form');
  $form.submit(function(event) {
    // Disable the submit button to prevent repeated clicks:
    $form.find('.submit').prop('disabled', true);

    // Request a token from Stripe:
    Stripe.card.createToken($form, stripeResponseHandler);

    // Prevent the form from being submitted:
    return false;
  });

  function stripeResponseHandler(status, response) {
    // Grab the form:
    var $form = $('#subscription-form');

    if (response.error) { // Problem!

      // Show the errors on the form:
      $form.find('.errors').text(response.error.message);
      $form.find('.submit').prop('disabled', false); // Re-enable submission

    } else { // Token was created!

      // Get the token ID:
      var token = response.id;

      // Insert the token ID into the form so it gets submitted to the server:
      $form.append($('<input type="hidden" name="stripeToken">').val(token));

      // Submit the form:
      $form.get(0).submit();
    }
  };
});
