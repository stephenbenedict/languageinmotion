<div id="edict-lookup">
  <form method="post" class="lookup-form">
    Search EDICT:
    <input type="text" name="word" placeholder="ことば">
    <input type="submit" value="Search">
  </form>
  <div class="listing">
    <ul>
    <?php if (isset($results)): ?>
      <?php foreach ($results as $result): ?>
        <li>
          <input type="text" value="<?php echo $result->kanji() ?>" class="kanji">
          <input type="text" value="<?php echo $result->kana() ?>" class="kana">
          <input type="text" value="<?php echo $result->english() ?>" class="english">
        </li>
      <?php endforeach ?>
    <?php endif ?>
    </ul>
  </div>
</div>