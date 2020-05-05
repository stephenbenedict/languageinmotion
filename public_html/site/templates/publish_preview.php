<?php snippet('header') ?>

<div class="wrapper publish">
  <?php snippet('publish_breadcrumb') ?>
  <h2><?php echo $page->parent()->title()->html() ?></h2>
  <div class="source">
    <?php foreach ($page->parent()->text()->toStructure() as $chapter): ?>
      <div class="chapter">
        <?php echo $chapter->number()->html() ?>. <?php echo $chapter->title()->html() ?>
        <br>
        <?php echo $chapter->text()->kirbytext() ?>
      </div>
    <?php endforeach ?>
  </div>
  <?php snippet('publish_nav') ?>
</div>

<?php snippet('footer') ?>