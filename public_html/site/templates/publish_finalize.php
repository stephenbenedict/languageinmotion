<?php snippet('header') ?>

<div class="publish">

  <?php snippet('publish_breadcrumb') ?>
  <h2><?php echo $page->parent()->title()->html() ?></h2>

  <?php if ($error): ?>
    <?php foreach ($messages as $message): ?>
      <?php echo $message ?><br> 
    <?php endforeach ?>
  <?php else: ?>
    <?php echo $page->parent()->title()->html() ?> has been finalized.
  <?php endif ?>

  <?php snippet('publish_nav') ?>

</div>

<?php snippet('footer') ?>
