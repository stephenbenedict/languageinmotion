<?php snippet('header') ?>

<div class="wrapper">

  <h1><?php echo $page->title()->kirbytext() ?></h1>
  <?php echo $page->text()->kirbytext() ?>

  <form method="post" id="subscription-form" class="long-form">
    <div class="errors">
      <?php if (isset($error)): ?>
        <?php foreach ($messages as $message): ?>
          <?php echo $message ?><br> 
        <?php endforeach ?>
      <?php endif ?>
    </div>
    <input type="hidden" name="email" value="<?php echo $site->user()->email() ?>">
    <fieldset class="card-number-with-icon">
      <label for="card-number">Card Number</label>
      <input id="card-number" class="card-number" type="tel" autocomplete="card-number" data-stripe="number">
      <div class="card-icon" data-brand=""></div>
    </fieldset>  
    <fieldset>
      <label for="card-exp">Expiration</label>
      <input id="card-exp" class="card-exp" type="tel" autocomplete="card-exp" placeholder="MM / YY" data-stripe="exp">
    </fieldset>
    <fieldset>
      <label for="card-cvc">CVC</label>
      <input id="card-cvc" class="card-cvc" type="tel" autocomplete="off" placeholder="000" data-stripe="cvc">
    </fieldset>
    <fieldset>
      <input type="submit" class="submit" value="Subscribe">
    </fieldset>
  </form>

  <div class="form-appendix">
    <div class="subscription-security-disclaimer">
      <?php echo $page->security_disclaimer() ?> <a href="https://stripe.com/about" target="_blank"></a>
    </div>
  </div>

</div>

<?php snippet('footer', array('customJS' => array('subscribe.js', 'jquery.payment.js'))) ?>
