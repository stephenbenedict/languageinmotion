<style>
.site-updates-views h2 {
  margin-bottom: 1em;
  font-weight: normal;
}

.site-updates-views ul {
  list-style-type: none;
}

.site-updates-views li {
  padding: 4px 0;
}

.site-updates-views .email,
.site-updates-views .customer-id {
  margin-left: 1em;
}
</style>

<div class="site-updates-views">
  <h2><?php echo $number ?> (<?php echo $percentage ?>%)</h2>
  <em><?php echo page('library')->site_updates()->yaml()['text'] ?></em>
</div>
