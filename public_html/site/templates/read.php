<?php snippet('header') ?>

<div id="bookmarks"><?php snippet('bookmarks') ?></div>
<div id="card"><?php snippet('card') ?></div>
<div id="toolbar">
    
  <div class="notif"></div>
  <div class="buttons wrapper">
    <a href="#" id="add-bookmark">Bookmark</a>
    <a href="#" id="bookmarks-toggle">Bmks.</a>
    <a href="#" id="card-toggle">Study</a>
    <a href="#" id="fluency-toggle">Fluency</a>
  </div>
</div>

<div id="book" class="wrapper">
  <?php if ($error): ?>
    <div class="errors">
      <?php foreach ($messages as $message): ?>
        <?php echo $message ?><br> 
      <?php endforeach ?>
    </div>
  <?php endif ?>
  <h1><?php echo $page->title_ruby() ?></h1>
  <h2><?php echo $page->author() ?></h2>
  <div id="text">
    <?php foreach ($bookPages as $number => $bookPage): ?>
      <div data-page="<?php echo $number ?>" <?php e($number == 0, 'class="current"') ?>>
        <?php echo $bookPage->page_text()->html() ?>
      </div>
    <?php endforeach ?>

    <?php if ($bookPages->count() > 1): ?>
      <nav>
        <?php foreach ($bookPages as $number => $bookPage): ?>
          <a href="#" <?php e($number == 0, 'class="current"') ?> data-page="<?php echo $number ?>"><?php echo $number + 1 ?></a>
        <?php endforeach ?>
      </nav>
    <?php endif ?>

  </div>
</div>

<?php snippet('footer', array('customJS' => array('read.js'))) ?>
