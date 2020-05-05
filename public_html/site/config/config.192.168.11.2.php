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

c::set('debug', true);

// SQLite
c::set('dictionaryDb', '/Users/stephenbenedict/Sites/languageinmotion/dictionary.sqlite');
c::set('userBoilerplateDb', '/Users/stephenbenedict/Sites/languageinmotion/userdata/boilerplate.sqlite');
c::set('edictDb', '/Users/stephenbenedict/Sites/languageinmotion/edict.sqlite');

// Stripe API keys
c::set('stripeSecretKey', 'stripe-secret-key');

/*

---------------------------------------
Kirby Configuration
---------------------------------------

By default you don't have to configure anything to
make Kirby work. For more fine-grained configuration
of the system, please check out http://getkirby.com/docs/advanced/options

*/
