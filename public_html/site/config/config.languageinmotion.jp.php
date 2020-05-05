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

c::set('dictionaryDb', '/var/www/languageinmotion.jp/dictionary.sqlite');
c::set('userBoilerplateDb', '/var/www/languageinmotion.jp/userdata/boilerplate.sqlite');

// Stripe API secret key
c::set('stripeSecretKey', 'stripe-secret-key');
c::set('stripePublishableKey', 'stripe-public-key');
// Block /publish completely since
// it is never used other than locally
c::set('routes', array(
  array(
    'pattern' => 'publish',
    'action'  => function() {
      go('/');
    }
  ),
  array(
    'pattern' => 'publish/(:all)',
    'action'  => function() {
      go('/');
    }
  )
));

/*

---------------------------------------
Kirby Configuration
---------------------------------------

By default you don't have to configure anything to
make Kirby work. For more fine-grained configuration
of the system, please check out http://getkirby.com/docs/advanced/options

*/
