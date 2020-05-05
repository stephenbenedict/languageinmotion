<div class="listing">
  <ul>
    <?php if ($bookmarks->count() > 0): ?>
      <?php foreach ($bookmarks as $bookmark): ?>
        <li class="bookmark" data-location="<?php echo $bookmark->location() ?>">
          <div class="wrapper">
            <div class="details">
              <?php if ($bookmark->chapter_number()): ?>
                <?php echo $bookmark->chapter_number ?>.&nbsp;
              <?php endif ?>
              <?php if ($bookmark->chapter_title()): ?>
                <?php echo $bookmark->chapter_title() ?>&nbsp;&ndash;
              <?php endif ?>
              <?php echo $bookmark->excerpt() ?>
            </div>
            <div class="controls">
              <a href="#" class="open btn">Open</a>
              <a href="#" class="delete btn secondary">Delete</a>
            </div>
          </div>
        </li>
      <?php endforeach ?>
    <?php else: ?>
      <li class="hint"><div class="wrapper">No bookmarks</div></li>
    <?php endif ?>
   </ul>
</div>
