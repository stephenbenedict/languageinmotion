<?php snippet('header') ?>

<div class="wrapper">

  <form method="post">
    <?php if (isset($error)): ?>
      <div class="errors">
        <?php foreach ($messages as $message): ?>
          <?php echo $message ?><br> 
        <?php endforeach ?>
      </div>
    <?php endif ?>
    <fieldset>
      <label for="username"><?php echo $page->username()->html() ?></label>
      <input type="text" id="username" name="username" autocapitalize="none">
    </fieldset>
    <fieldset>
      <label for="password"><?php echo $page->password()->html() ?></label>
      <input type="password" id="password" name="password" autocapitalize="none">
    </fieldset>
    <fieldset>
      <button type="submit" value="<?php echo $page->button()->html() ?>" class="btn"><?php echo $page->button()->html() ?></button>
    </fieldset>
  </form>
</div>

<?php snippet('footer') ?>
