<?php 

return array(
  'title' => 'Site Updates Views',
  'html' => function() {
    $users = panel()->users();
    $lastSiteUpdateId = page('library')->site_updates()->yaml()['id'];
    $number = 0;
    foreach ($users as $user) {
      if (array_key_exists('last_viewed_site_update_id', $user->data())) {
        if ($user->data()['last_viewed_site_update_id'] == $lastSiteUpdateId) {
          $number += 1;
        }
      }
    }
    $percentage = round($number / $users->count() * 100);

    return tpl::load(__DIR__ . DS . 'site_updates_views.html.php', compact('number', 'percentage'));
  }
);
