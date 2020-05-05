<?php 

return array(
  'title' => array(
    'text'   => 'Subscribers',
    'link'   => 'https://dashboard.stripe.com/customers',
    'target' => '_blank',
  ),
  'html' => function() {
    $subscribers = panel()->users()->filterBy('role', 'subscriber');
    return tpl::load(__DIR__ . DS . 'subscribers.html.php', compact('subscribers'));
  }
);
