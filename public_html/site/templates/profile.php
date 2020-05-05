<?php snippet('header') ?>

<div class="wrapper">

  <h1><?php echo $page->title() ?></h1>

  <form method="post">
    <?php if (isset($success)): ?>
      <div class="success"><?php echo $success ?></div>
    <?php elseif (isset($error)): ?>
      <div class="errors">
        <?php foreach ($messages as $message): ?>
          <?php echo $message ?><br> 
        <?php endforeach ?>
      </div>
    <?php endif ?>
    <fieldset>
      <label><?php echo $page->username_label() ?></label>
      <span><?php echo $site->user()->username() ?></span>
    </fieldset>
    <fieldset>
      <label for="email"><?php echo $page->email_label() ?></label>
      <input type="text" id="email" name="email" value="<?php echo isset($error) ? $data['email'] : $site->user()->email() ?>">
    </fieldset>
    <fieldset>
      <label for="password"><?php echo $page->password_label() ?></label>
      <input type="password" id="password" name="password" autocapitalize="none">
    </fieldset>
    <fieldset>
      <button type="submit" name="update" value="Update" class="btn"><?php echo $page->submit_button() ?></button>
    </fieldset>
  </form>

  <div class="form-appendix">
    <?php if ($site->user()->hasRole('subscriber')): ?>
    <div>
      <a href="<?php echo url('profile/subscription/update') ?>"class="btn"><?php echo $page->update_subscription_link() ?></a>
    </div>
    <?php endif ?>
    <div class="pad-top">
      <a href="<?php echo url('profile/delete') ?>"class="btn secondary block"><?php echo $page->delete_account_link() ?></a>
    </div>
  </div>

</div>
<?php snippet('footer') ?>
