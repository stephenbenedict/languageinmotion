<?php snippet('header') ?>

<div class="listing library">
  <?php if ($loggedInWithIE): ?>
    <div class="wrapper ie-warning"><?php echo $page->ie_warning()->kirbytext() ?></div>
  <?php endif ?>
  <?php if (isset($accountJustCreated)): ?>
    <div class="wrapper welcome"><?php echo $page->welcome_message() ?></div>
  <?php else: ?>
    <?php if ($showSiteUpdates): ?>
      <div class="wrapper site-updates"><?php echo $page->site_updates()->yaml()['text'] ?></div>
    <?php endif ?>
  <?php endif ?>
  <ul>
    <?php foreach ($page->children()->visible() as $book): ?>
    <li>
      <?php if (!$site->user()): ?>
        <?php if ($book->premium()->isTrue()): ?>
          <a href="<?php echo url('signup?subscribe=true') ?>" class="wrapper">
            <span class="title">
              <?php echo $book->title_ruby() ?>
              <span class="english"><?php echo $book->title_english() ?></span>
            </span>
            <span class="author-english"><?php echo $book->author_english() ?></span>
            <span class="blurb">
              <?php echo $book->blurb() ?>
              <span class="level">(<span class="<?php echo str::lower($book->level()) ?>"><?php echo $book->level() ?></span>)</span>
              <span class="btn"><?php echo $page->subscribe_link() ?></span>
            </span>
          </a>
        <?php else: ?>
          <a href="<?php echo page('signup')->url() ?>" class="wrapper">
            <span class="title">
              <?php echo $book->title_ruby() ?>
              <span class="english"><?php echo $book->title_english() ?></span>
            </span>
            <span class="author-english"><?php echo $book->author_english() ?></span>
            <span class="blurb">
              <?php echo $book->blurb() ?> 
              <span class="level">(<span class="<?php echo str::lower($book->level()) ?>"><?php echo $book->level() ?></span>)</span>
              <span class="btn"><?php echo $page->sign_up_link() ?></span>
            </span>
          </a>
        <?php endif ?>
      <?php elseif ($book->premium()->isFalse() || $site->user()->hasRole('subscriber') || $site->user()->hasRole('friendsandfamily') || $site->user()->isAdmin()): ?>
        <a href="<?php echo url($book->url()) ?>" class="wrapper">
          <?php if ($page->newBook($book->uid())): ?>
            <span class="new"><?php echo $page->new_label() ?></span>
          <?php elseif ($page->newEdition($book->uid())): ?>
            <span class="updated"><?php echo $page->new_edition_label() ?></span>
          <?php endif ?>
          <span class="title">
            <?php echo $book->title_ruby() ?>
            <span class="english"><?php echo $book->title_english() ?></span>
          </span>
          <span class="author-english"><?php echo $book->author_english() ?></span>
          <span class="blurb"><?php echo $book->blurb() ?> 
            <span class="level">(<span class="<?php echo str::lower($book->level()) ?>"><?php echo $book->level() ?></span>)</span>
            <?php if ($page->hasVocabularyResults($book->uid())): ?>
              <span class="fluency">(<?php echo $page->vocabulary_label() ?>  <?php echo $page->progress($book->uid()) ?>%)</span>
            <?php endif ?>
            <span class="btn"><?php echo $page->book_link() ?></span>
        </span>
        </a>
      <?php else: ?>
        <a href="<?php echo url('profile/subscription/create') ?>" class="wrapper">
          <span class="title">
            <?php echo $book->title_ruby() ?>
            <span class="english"><?php echo $book->title_english() ?></span>
          </span>
          <span class="author-english"><?php echo $book->author_english() ?></span>
          <span class="blurb"><?php echo $book->blurb() ?> 
            <span class="level">(<span class="<?php echo str::lower($book->level()) ?>"><?php echo $book->level() ?></span>)</span>
            <?php if ($page->hasVocabularyResults($book->uid())): ?>
              <span class="fluency">(<?php echo $page->vocabulary_label() ?>  <?php echo $page->progress($book->uid()) ?>%)</span>
            <?php endif ?>
            <span class="btn"><?php echo $page->subscribe_link() ?></span>
          </span>
        </a>
      <?php endif ?>
    </li>
    <?php endforeach ?>
  </ul>
  <div class="wrapper pad-top">
    <a href="<?php echo url('grammar') ?>" class="btn secondary"><?php echo $page->grammar_link() ?></a>
  </div>
</div>

<?php snippet('footer') ?>
