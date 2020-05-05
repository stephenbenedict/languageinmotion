<?php if (isset($error)): ?>
  <?php foreach ($messages as $message): ?>
    <?php echo $message ?><br> 
  <?php endforeach ?>
<?php endif ?>

<?php if ($deck->count() == 0): ?>
  <ul>
    <li><h2>No new words to edit.</h2></li>
  </ul>
<?php else: ?>
  <ul>
    <?php foreach ($deck as $card): ?>
      <li <?php e($card->english() == '', "class='missing-definition'") ?>>
        <span class="word"><?php echo $card->word() ?></span>
        <span class="kana"><?php echo $card->kana() ?></span>
        <span class="english"><?php echo $card->english() ?></span>
      </li>
    <?php endforeach ?>
  </ul>

  <div class="wrapper missing-definitions"><?php echo $missingDefinitions ?> missing definitions</div>

<?php endif ?>