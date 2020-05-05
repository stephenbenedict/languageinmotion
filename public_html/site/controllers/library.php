<?php 

return function($site, $pages, $page) {

  $loggedInWithIE = false;

  if (site()->user() && isset($_SERVER['HTTP_USER_AGENT'])) {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    if (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) {
      $loggedInWithIE = true;
    }
  }

  $showSiteUpdates = false;
  if ($user = site()->user()) {
    $lastViewedUpdateId = '';
    if (array_key_exists('last_viewed_site_update_id', $user->data())) {
      $lastViewedUpdateId = $user->data()['last_viewed_site_update_id'];
    }

    $currentUpdateId = $page->site_updates()->yaml()['id'];
    if ($currentUpdateId != $lastViewedUpdateId) {
      $showSiteUpdates = true;
      $user->update(array(
        'last_viewed_site_update_id' => $currentUpdateId
      ));
    } 
  }

  return compact('loggedInWithIE', 'showSiteUpdates');
};
