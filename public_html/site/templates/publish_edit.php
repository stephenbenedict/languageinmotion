<?php snippet('header') ?>


<div class="publish">
  <?php snippet('publish_breadcrumb') ?>
  <?php snippet('publish_edict_lookup') ?>
  <a href="#" id="missing-definitions-filter-toggle" class="btn">Filter missing definitions</a>
  
  <?php if (isset($error)): ?>
    <?php foreach ($messages as $message): ?>
      <?php echo $message ?><br> 
    <?php endforeach ?>
  <?php endif ?>

  <h2><?php echo $page->parent()->title()->html() ?></h2>
  <div class="listing edit-list">
    <?php snippet('publish_edit_list') ?>
  </div>
  <?php snippet('publish_nav') ?>
</div>

<?php snippet('footer', array('customJS' => array('publish_edit.js'))) ?>
