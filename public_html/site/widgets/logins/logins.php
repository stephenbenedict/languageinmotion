<?php 

return array(
  'title' => 'Logins',
  'html' => function() {
    $loginsPath = dirname(dirname(kirby()->roots()->site())) . '/log/login.log';
    $logins = array_slice(yaml::decode(file_get_contents($loginsPath)), -30);
    return tpl::load(__DIR__ . DS . 'logins.html.php', compact('logins'));
  }
);
