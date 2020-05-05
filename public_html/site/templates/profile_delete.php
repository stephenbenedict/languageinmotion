<?php snippet('header') ?>

<div class="wrapper">

  <h1><?php echo $page->title() ?></h1>

  <?php if ($site->user()->hasRole('subscriber')): ?>
    <?php echo $page->text_subscriber()->kirbytext() ?>
  <?php else: ?>
    <?php echo $page->text_nonsubscriber()->kirbytext() ?>
  <?php endif ?>

  <form method="post">
    <?php if (isset($error)): ?>
      <div class="errors">
        <?php foreach ($messages as $message): ?>
          <?php echo $message ?><br> 
        <?php endforeach ?>
      </div>
    <?php endif ?>
    <fieldset>
      <label for="password"><?php echo $page->password_confirm_label()->html() ?></label>
      <input type="password" name="password" autocapitalize="none">
    </fieldset>
    <fieldset>
      <button type="submit" value="delete" class="btn"><?php echo $page->submit_button() ?></button>
    </fieldset>
  </form>
</div>

<?php snippet('footer') ?>
