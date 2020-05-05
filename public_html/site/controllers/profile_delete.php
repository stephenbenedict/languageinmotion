<?php 

return function($site, $pages, $page) {

  if (!$site->user()) go('login');

};