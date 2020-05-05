<?php snippet('header') ?>


<div class="wrapper">

  <h1><?php echo $page->title() ?></h1>
  You have been issued a full refund in the amount of ¥2,500 to the card on file since Language in Motion will be shutting down on July 13th. (More info <a href="https://languageinmotion.jp/sayonara" class="btn">here</a>.)
  <del>Your card will automatically be rebilled <?php echo $nextBillingAmount ?> on <?php echo $nextBillingDate ?>.</del>
  <form method="post" id="subscription-form">
    <?php if (isset($success)): ?>
        <div class="success"><?php echo $page->form_success() ?></div>
    <?php endif ?>
    <div class="errors">
      <?php if (isset($error)): ?>
        <?php foreach ($messages as $message): ?>
          <?php echo $message ?><br> 
        <?php endforeach ?>
      <?php endif ?>
    </div>
    <fieldset class="card-number-with-icon">
      <label for="card-number"><?php echo $page->number_label() ?></label>
      <input id="card-number" class="card-number" type="tel" autocomplete="card-number" data-stripe="number" value="**** **** **** <?php echo $last4 ?>">
      <div class="card-icon" data-brand="<?php echo str::slug($brand) ?>"></div>
    </fieldset>  
    <fieldset>
      <label for="card-exp"><?php echo $page->expiration_label() ?></label>
      <input id="card-exp" class="card-exp" type="tel" autocomplete="card-exp" placeholder="MM / YY" data-stripe="exp" value="<?php echo $expMonth . ' / ' . $expYear ?>">
    </fieldset>
    <fieldset>
      <label for="card-cvc"><?php echo $page->cvc_label() ?></label>
      <input id="card-cvc" class="card-cvc" type="tel" autocomplete="off" placeholder="000" data-stripe="cvc">
    </fieldset>
    <fieldset>
      <button type="submit" class="submit" value="<?php echo $page->submit_button() ?>"><?php echo $page->submit_button() ?></button>
    </fieldset>
  </form>
  <div class="form-appendix">
    <div class="subscription-security-disclaimer">
      <?php echo $page->security_disclaimer() ?> <a href="https://stripe.com/about" target="_blank"></a>
    </div>
    <div>
      <a href="<?php echo url('profile/subscription/cancel') ?>" class="btn secondary"><?php echo $page->cancel_subscription_link() ?></a>
    </div>
  </div>
</div>

<?php snippet('footer', array('customJS' => array('subscribe.js', 'jquery.payment.js'))) ?>
