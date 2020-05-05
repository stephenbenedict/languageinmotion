<?php snippet('header') ?>

<div class="wrapper">
  <h1><?php e($subscribe, $page->title_subscriber()->kirbytext(), $page->title_nonsubscriber()->kirbytext()) ?></h1>
  <form method="post" id="signup">
    
    <?php if (isset($error)): ?>
      <?php if(array_key_exists('other', $messages)): ?>
        <div class="errors">
          <?php foreach ($messages['other'] as $message): ?>
            <?php echo $message ?><br> 
          <?php endforeach ?>
        </div>
      <?php endif ?>
    <?php endif ?>

    <input type="hidden" name="subscribe" value="<?php echo $subscribe ?>">
    <fieldset>
      <?php if (isset($error)): ?>
        <div class="errors"> 
          <?php if(array_key_exists('username', $messages)): ?><?php echo $messages['username'] ?><?php endif ?>
        </div>
      <?php endif ?>
      <label for="username">Username</label>
      <input type="text" id="username" name="username" value="<?php echo isset($error) ? $data['username'] : '' ?>" autocorrect="off" autocapitalize="none">
    </fieldset>
    <fieldset>
      <?php if (isset($error)): ?>
        <div class="errors"> 
          <?php if(array_key_exists('email', $messages)): ?><?php echo $messages['email'] ?><?php endif ?>
        </div>
      <?php endif ?>
      <label for="email">Email</label>
      <input type="text" id="email" name="email" value="<?php echo isset($error) ? $data['email'] : '' ?>" autocorrect="off" autocapitalize="none">
    </fieldset>
    <fieldset>
      <?php if (isset($error)): ?>
        <div class="errors">
          <?php if(array_key_exists('password', $messages)): ?><?php echo $messages['password'] ?><?php endif ?>
        </div>
      <?php endif ?>
      <label for="password">Password</label>
      <input type="password" id="password" name="password"> 
    </fieldset>
    <fieldset>
      <span class="btn"><del><?php echo $page->submit_button() ?></del></span>
      <br>
      <span class="sayonara">Language in Motion is shutting down. More info <a href="/sayonara" class="btn">here</a>.</span>
    </fieldset>
  </form>

</div>

<?php snippet('footer') ?>
