<?php snippet('header') ?>

<div class="listing library">
  <ul>
    <?php foreach ($page->children() as $book): ?>
      <li class="<?php echo str::lower(str::slug($book->status())) ?>">
        <div class="wrapper">
          <?php if ($book->status() == 'Editing'): ?>
            <a href="<?php echo $book->children()->find('edit')->url() ?>">
          <?php elseif ($book->status() == 'Finalized'): ?>
            <a href="<?php echo $book->children()->find('finalize')->url() ?>">
          <?php else: ?>
            <a href="<?php echo $book->children()->find('preview')->url() ?>">
          <?php endif ?>
          
            <span><?php echo $book->uid() ?>: <?php echo $book->title()->html() ?></span>
              <span class="meta"><?php echo $book->status() ?></span>
              <span class="btn">Open</span>
          </a>
        </div>
      </li>
    <?php endforeach ?>
  </ul>
</div>

<?php snippet('footer') ?>
