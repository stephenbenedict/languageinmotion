<!DOCTYPE html>
<html lang="en">
<head>

  <meta charset="utf-8" />
  <link rel="shortcut icon" href="/favicon.ico">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?php echo $site->title()->html() ?> | <?php echo $page->title()->html() ?></title>
  <meta content="<?php echo $site->description() ?>" name="description">
  <link href='https://fonts.googleapis.com/css?family=Lato:400,700,400italic' rel='stylesheet' type='text/css'>
  <?php echo css('assets/css/style.css') ?>
  <?php if ($page->uri() == 'profile/subscription/create' || $page->uri() == 'profile/subscription/update'): ?>
    <script type="text/javascript" src="https://js.stripe.com/v2/"></script>
    <script type="text/javascript">
      Stripe.setPublishableKey("<?php echo c::get('stripePublishableKey') ?>");
    </script>
  <?php endif ?>
</head>
<body>
  <div id="container" <?php e($page->parent()->uid() == 'library', 'class="reading"') ?>>
  <header class="wrapper">
    <a class="logo" href="<?php echo url('/') ?>">Language in Motion</a>
    <nav>
      <a href="<?php echo url('library') ?>" <?php e($page->uid() == "library" || $page->parent()->uid() == "library", ' class="current"') ?>>Library</a>
      <?php if($user = $site->user()): ?>
      <a href="<?php echo url('profile') ?>" <?php e($page->uid() == "profile" || $page->uri() == "profile/subscription/update" || $page->uri() == "profile/subscription/cancel", ' class="current"') ?>>Profile</a>
      <a href="<?php echo url('logout') ?>">Logout</a>
      <?php else: ?>
      <a href="<?php echo url('login') ?>" <?php e($page->uid() == "login", ' class="current"') ?>>Login</a>
      <a href="<?php echo url('signup') ?>" <?php e($page->uid() == "signup", ' class="current"') ?>>Sign Up</a>
      <?php endif ?>
    </nav>
  </header>
