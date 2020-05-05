<?php 

return function($site, $pages, $page) {

  if (!$site->user()) go('login');
  
  if ($site->user()->hasRole('nonsubscriber')) {
    go('profile/subscription/create');
  } elseif ($site->user()->hasRole('friendsandfamily') || $site->user()->isAdmin()) {
    go('profile');
  }

};
