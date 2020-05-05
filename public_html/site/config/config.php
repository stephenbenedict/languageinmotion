<?php


/*

---------------------------------------
License Setup
---------------------------------------

Please add your license key, which you've received
via email after purchasing Kirby on http://getkirby.com/buy

It is not permitted to run a public website without a
valid license key. Please read the End User License Agreement
for more information: http://getkirby.com/license

*/

c::set('license', 'your-license-key');

s::$timeout = 10080; // 1 week of session validity
s::$cookie['lifetime'] = 0; // don't let the cookie ever expire

c::set('roles', array(
  array(
    'id'      => 'admin',
    'name'    => 'Admin',
    'default' => true,
    'panel'   => true
  ),
  array(
    'id'      => 'nonsubscriber',
    'name'    => 'Non-Subscriber',
    'panel'   => false
  ),
  array(
    'id'      => 'subscriber',
    'name'    => 'Subscriber',
    'panel'   => false
  ),
  array(
    'id'      => 'friendsandfamily',
    'name'    => 'Friends and Family',
    'panel'   => false
  )
));


c::set('routes', array(

  // Paywall
  array(
    'pattern' => 'library/(:any)',
    'action'  => function($path) {
      // If not logged in send to login
      if (!site()->user()) go('login');

      // Otherwise check for premium status
      $dirs = str::split($path, '/');
      $bookId = array_pop($dirs);

      $page = site()->page('library' . DIRECTORY_SEPARATOR . $bookId);
      
      if ($page->premium() == 'true') {
        if (site()->user()->hasRole('subscriber') || site()->user()->hasRole('friendsandfamily') || site()->user()->isAdmin()) {
          return $page;
        } else {
          go('profile/subscription/create');
        }
      } else {
        return $page;
      }
    }
  )
));

c::set('smartypants', true);
c::set("kirbytext.image.figure", false);

/*

---------------------------------------
Kirby Configuration
---------------------------------------

By default you don't have to configure anything to
make Kirby work. For more fine-grained configuration
of the system, please check out http://getkirby.com/docs/advanced/options

*/
