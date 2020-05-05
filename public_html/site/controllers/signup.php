<?php 

return function($site, $pages, $page) {

  if ($site->user()) go('profile');

  $subscribe = a::get(url::query(), 'subscribe');

  return compact('subscribe');
};
