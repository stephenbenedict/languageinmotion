<style>

.logins ul {
  list-style-type: none;
}

.logins li {
  padding: 4px 0;
}

</style>

<div class="logins">
  <ul>
    <?php foreach ($logins as $date => $number): ?>
      <li>
        <?php echo $date ?>: <?php echo $number ?>
      </li>
    <?php endforeach ?>
  </ul>
</div>
