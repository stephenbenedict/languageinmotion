<?php

return function($site, $pages, $page) {
  
  $user = site()->user();
  if (!$user || !$user->isAdmin()) {
    go('error');
  }

};