<?php snippet('header') ?>

<article class="wrapper">
  <h1><?php echo $page->title()->kirbytext() ?></h1>
  <?php echo $page->text()->kirbytext() ?>
</article>

<?php snippet('footer') ?>
