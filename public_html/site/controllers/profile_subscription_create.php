<?php 

return function($site, $pages, $page) {

  if ($site->user()->hasRole('subscriber')) {
    go('profile/subscription/update');
  } elseif ($site->user()->hasRole('friendsandfamily') || $site->user()->isAdmin()) {
    go('profile');
  }

};
