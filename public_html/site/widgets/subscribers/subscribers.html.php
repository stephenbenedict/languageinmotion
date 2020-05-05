<style>
.subscribers h2 {
  margin-bottom: 1em;
  font-weight: normal;
}

.subscribers ul {
  list-style-type: none;
}

.subscribers li {
  padding: 4px 0;
}

.subscribers .email,
.subscribers .customer-id {
  margin-left: 1em;
}
</style>

<div class="subscribers">
  <h2>Total: <?php echo $subscribers->count() ?></h2>
  <ul>
    <?php foreach ($subscribers as $user): ?>
      <li>
        <div class="username"><?php echo $user->username() ?></div>
        <div class="email"><?php echo $user->email() ?></div>
        <div class="customer-id"><?php echo $user->data()['customerid'] ?></div>
      </li>
    <?php endforeach ?>
  </ul>
</div>
