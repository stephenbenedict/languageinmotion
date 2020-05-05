<?php snippet('header') ?>

<div class="wrapper">

  <h1><?php echo $page->title() ?></h1>
  <?php echo $page->text()->kirbytext() ?>

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
      <button type="submit" value="<?php echo $page->submit_button() ?>" class="btn"><?php echo $page->submit_button() ?></button>
    </fieldset>
  </form>

</div>

<?php snippet('footer') ?>
