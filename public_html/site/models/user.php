<?php 

require_once(kirby()->roots()->plugins() . '/api/vendor/stripe-php/init.php');

class UserPage extends Page {

  public function createAccount() {

    $data = array(
      'username' => get('username'),
      'email'    => get('email'),
      'password' => get('password')
    );

    $rules = array(
      'username' => array('required', 'alphanum', 'uniqueUsername'),
      'email'    => array('required', 'email'),
      'password' => array('required')
    );

    $messages = array(
      'username' => 'Please enter a valid username',
      'email' => 'Please enter a valid email',
      'password' => 'Please enter a password'
    );

    if($invalid = invalid($data, $rules, $messages)) {
      $error = true;

      if (!v::uniqueUsername($data['username'])) { 
        $invalid['username'] = 'That username is taken';
      }

      return array('error' => $error, 'messages' => $invalid, 'data' => $data);
    } else {
      $error = false;

      try {
        // Create the user account
        $user = site()->users()->create(array(
          'username'  => str::lower(get('username')),
          'email'     => get('email'),
          'password'  => get('password'),
          'language'  => 'en',
          'role'      => 'nonsubscriber'
        ));
      } catch(Exception $e) {
        error_log("Caught $e");
        $error = true;
        $messages['other'][] = 'The user account could not be created. ' . $e->getMessage();
      }

      // Only proceed if the user account is created.
      if (!$error) {
        $userDataPath = dirname(dirname(kirby()->roots()->site())) . '/userdata';
        $boilerplate = $userDataPath . '/boilerplate.sqlite';
        $userCustomDatabase = $userDataPath . '/' . str::lower($data['username']) . '/userdata.sqlite';
        
        // Create folder userdata/username
        $userData = new Folder($userDataPath . '/' . str::lower($data['username']));
        $userData->create();
        
        // Copy boilerplate SQlite DB into created folder
        f::copy($boilerplate, $userCustomDatabase);

        // Email admin
        $emailToAdmin = new Email(array(
          'to'      => site()->email(),
          'from'    => site()->email(),
          'subject' => snippet('emails/admin/subject/account_created', array(), true),
          'body'    => snippet('emails/admin/body/account_created', array('username' => str::lower(get('username')), 'email' => get('email')), true)
        ));

        $sentAdmin = $emailToAdmin->send();
        
        // Email user
        $emailToUser = new Email(array(
          'to'      => get('email'),
          'from'    => site()->email(),
          'subject' => snippet('emails/subject/account_created', array(), true),
          'body'    => snippet('emails/body/account_created', array('username' => str::lower(get('username'))), true)
        ));

        $sentUser = $emailToUser->send();

        if (!$sentAdmin || !$sentUser) {
          $error = true;
          $messages[] = 'Failed to send account created email.';
        }

        $user->login(get('password'));
      }
      
      return array('error' => $error, 'messages' => $messages);
    }
  }

  public function updateAccount() {

    $data = array(
      'email'    => get('email'),
      'password' => get('password')
    );

    $rules = array(
      'email'    => array('email'),
      'password' => array('match' => '/^.{6,}$/')
    );

    $messages = array(
      'email' => 'Please enter a valid email',
      'password' => 'Please enter a password with at least 6 characters'
    );

    if($invalid = invalid($data, $rules, $messages)) {
      $error = true;

      return array('error' => $error, 'messages' => $invalid, 'data' => $data);
    } else {
      $error = false;

      $user = site()->user();

      try {

        if (get('email') != '') {
          // Update email in user account
          $user->update(array(
            'email' => get('email')
          ));

          // If user is a subscriber, update their email in Stripe's records as well
          if ($user->hasRole('subscriber')) {
            \Stripe\Stripe::setApiKey(c::get('stripeSecretKey'));
            $customer = \Stripe\Customer::retrieve($user->data()['customerid']);
            $customer->email = get('email');
            $customer->save();
          }
        }

        if (get('password') != '') {
          site()->user()->update(array(
            'password' => get('password')
          ));
        }

        return array('success' => 'Your account has been updated.');

      } catch(Exception $e) {
        error_log("Caught $e");
        $error = true;
        $messages[] = 'Your account could not be updated. ' . $e->getMessage();
      }
    }

    return compact('error', 'messages');
  }

  public function deleteAccount() {
    $error = false;
    $messages = array();

    if (password::match(get('password'), site()->user()->password())) {
      $user = site()->user();
      $userEmail = $user->email(); // Get now before deleting account in order to send email

      // If subscriber, cancel subscription
      if ($user->hasRole('subscriber')) {
        $canceled = $this->cancelSubscription();
        if ($canceled['error']) {
          $error = true;
          $messages = $canceled['messages'];
        }
      }

      // Delete user data
      try {
        $userDataPath = dirname(dirname(kirby()->roots()->site())) . '/userdata/' . $user->username();
        $userData = new Folder($userDataPath);
        $userData->remove();
      } catch(Exception $e) {
        error_log("Caught $e");
        $error = true;
        $messages[] = 'The user data could not be deleted. ' . $e->getMessage();
      }

      // Delete user account
      try {
        $user->delete();
      } catch(Exception $e) {
        error_log("Caught $e");
        $error = true;
        $messages[] = "The user account could not be deleted. " . $e->getMessage();
      }
      
      if (!$error) {
        // Email admin
        $emailToAdmin = new Email(array(
          'to'      => site()->email(),
          'from'    => site()->email(),
          'subject' => snippet('emails/admin/subject/account_deleted', array(), true),
          'body'    => snippet('emails/admin/body/account_deleted', array('username' => $user->username(), 'email' => $user->email()), true)
        ));
        $emailToAdmin->send();

        // Email user
        $emailToUser = new Email(array(
          'to'      => $userEmail,
          'from'    => site()->email(),
          'subject' => snippet('emails/subject/account_deleted', array(), true),
          'body'    => snippet('emails/body/account_deleted', array(), true)
        ));
        $emailToUser->send();
      }
    } else {
      $error = true;
      $messages[] = 'Incorrect password';
    }

    return compact('error', 'messages');
  }

  public function login() {
    $error = false;
    $messages = array();

    if($user = site()->user(str::lower(get('username'))) and $user->login(get('password'))) {

      // Keep a record of logins
      
      $loginsPath = dirname(dirname(kirby()->roots()->site())) . '/log/login.log';
      $logins = yaml::decode(file_get_contents($loginsPath));
      $todaysLogins = 1;
      // If a record for today already exists, increment that number
      if (array_key_exists(date('Y-m-d'), $logins)) { 
        $todaysLogins = $logins[date('Y-m-d')] + 1;
      }
      $logins[date('Y-m-d')] = $todaysLogins;

      try {
        yaml::write($loginsPath, $logins);
      } catch (Exception $e) {
        error_log("Caught $e");
        $error = true;
        $messages[] = 'Could not update logins stats. ' . $e->getMessage();
      }

      return compact('error', 'messages');
    } else {
      $error = true;
      $messages[] = 'Invalid username or password.';
      return compact('error', 'messages');
    }
  }

  public function createSubscription() {
    $error = false;
    $messages = array();

    \Stripe\Stripe::setApiKey(c::get('stripeSecretKey'));


    // Get the credit card details submitted by the form
    $token = get('stripeToken');

    try {
      // Create Stripe customer
      $customer = \Stripe\Customer::create(array(
        "source" => $token,
        "plan" => "limsubscription",
        "email" => get('email'))
      );

      // Update Kirby user role and add customer ID
      $user = site()->user(get('username'));
      $user->update(array(
        'role' => 'subscriber',
        'customerid' => $customer->id
      ));
    } catch (Exception $e) {
      error_log("Caught $e");
      $error = true;
      $messages[] = "Subscribing could not be completed.";
    }
    

    return compact('error', 'messages');
  }

  public function updateSubscription() {
    $error = false;
    $messages = array();

    \Stripe\Stripe::setApiKey(c::get('stripeSecretKey'));

    // Get the credit card details submitted by the form
    $token = get('stripeToken');

    try {
      // Update Stripe customer billing
      $customer = \Stripe\Customer::retrieve(site()->user()->data()['customerid']);
      $customer->source = $token;
      $customer->save();
    } catch (Exception $e) {
      error_log("Caught $e");
      $error = true;
      $messages[] = 'Billing information could not be updated (the card was declined).';
    }

    return compact('error', 'messages');
  }

  public function confirmCancelSubscription() {
    $error = false;
    $messages = array();

    if (password::match(get('password'), site()->user()->password())) {
      $cancel = $this->cancelSubscription();
      if ($cancel['error']) {
        $error = true;
        $messages = $cancel['messages'];
      }
    } else {
      $error = true;
      $messages[] = 'Incorrect password';
    }

    return compact('error', 'messages');
  }  

  public function cancelSubscription() {
    $error = false;
    $messages = array();

    \Stripe\Stripe::setApiKey(c::get('stripeSecretKey'));

    // Delete Stripe customer
    try {
      $user = site()->user();
      $customer = \Stripe\Customer::retrieve($user->data()['customerid']);
      $customer->delete();
    } catch (Exception $e) {
      error_log("Caught $e");
      $error = true;
      $messages[] = 'Could not remove from payment service provider. ' . $e->getMessage();
    }

    // Update user account role
    // and remove Stripe customer ID
    try {
      $user->update(array(
        'role' => 'nonsubscriber',
        'customerid' => null
      ));
    } catch (Exception $e) {
      error_log("Caught $e");
      $error = true;
      $messages[] = 'Failed to update user account after deleting customer. ' . $e->getMessage();
    }

    return compact('error', 'messages');
  }

}
