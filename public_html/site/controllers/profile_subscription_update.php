<?php 

require_once(kirby()->roots()->plugins() . '/api/vendor/stripe-php/init.php');

return function($site, $pages, $page) {

  if (!$site->user()) go('login');

  if ($site->user()->hasRole('nonsubscriber')) {
    go('profile/subscription/create');
  } elseif ($site->user()->hasRole('friendsandfamily') || $site->user()->isAdmin()) {
    go('profile');
  }

  \Stripe\Stripe::setApiKey(c::get('stripeSecretKey'));
  $customerId = $site->user()->data()['customerid'];

  // Retrieve the customer and expand their default source
  $customer = \Stripe\Customer::Retrieve(
    array("id" => $customerId, "expand" => array("default_source"))
  );
  $nextBillingAmount = '¥' . $customer->subscriptions->data[0]->plan->amount;
  $nextBillingDate = date( "m/d/Y", $customer->subscriptions->data[0]->current_period_end);

  return array('brand' => $customer->default_source->brand,
               'last4' => $customer->default_source->last4,
               'expMonth' => $customer->default_source->exp_month,
               'expYear'  => $customer->default_source->exp_year,
               'nextBillingAmount' => $nextBillingAmount,
               'nextBillingDate' => $nextBillingDate);
};
