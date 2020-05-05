<?php 

kirby()->routes(
  array(

    array(
      'pattern' => 'signup', 
      'method'  => 'POST',
      'action' => function() {

        $user = page('api/user');
        $data = $user->createAccount();

        if ($data['error']) {
          return array('signup', array('error' => $data['error'], 'messages' => $data['messages'], 'data' => $data['data']));
        } else {
          if (get('subscribe') == true) {
            go(url('profile/subscription/create'));
          } else {
            return array('library', array('accountJustCreated' => true));
          } 
        }
      }
    ),
    
    array(
      'pattern' => 'profile', 
      'method'  => 'POST',
      'action' => function() {
        $user = page('api/user');
        $data = $user->updateAccount();

        return array('profile', $data);
      }
    ),

    array(
      'pattern' => 'profile/delete', 
      'method' => 'POST',
      'action' => function() {
        $user = page('api/user');
        $data = $user->deleteAccount();

        if ($data['error']) {
          return array('profile/delete', array('error' => $data['error'], 'messages' => $data['messages']));
        } else {
          go('thankyou');
        }
      }
    ),

    array(
      'pattern' => 'login',
      'method'  => 'POST',
      'action'  => function() {
        $user = page('api/user');
        $data = $user->login();

        if ($data['error']) {
          return array('login', array('error' => $data['error'], 'messages' => $data['messages']));
        } else {
          go(url('library'));
        }
      }
    ),
    
    array(
      'pattern' => 'logout',
      'action'  => function() {
        if($user = site()->user()) {
          $user->logout();
          go('login');
        } else {
          go('login');
        }
      }
    ),

    array(
      'pattern' => 'profile/subscription/create',
      'method'  => 'POST',
      'action'  => function() {

        $user = page('api/user');
        $data = $user->createSubscription();

        if ($data['error']) {
          return array('profile/subscription/create', array('error' => $data['error'], 'messages' => $data['messages']));
        } else {
          go(url('profile/subscription/success'));
        }
      }
    ),

    array(
      'pattern' => 'profile/subscription/update',
      'method'  => 'POST',
      'action'  => function() {

        $user = page('api/user');
        $data = $user->updateSubscription();

        if ($data['error']) {
          return array('profile/subscription/update', array('error' => $data['error'], 'messages' => $data['messages']));
        } else {
          return array('profile/subscription/update', array('success' => true));
        }
      }
    ),

    array(
      'pattern' => 'profile/subscription/cancel',
      'method'  => 'POST',
      'action'  => function() {
        $user = page('api/user');
        $data = $user->confirmCancelSubscription();

        if ($data['error']) {
          return array('profile/subscription/cancel', array('error' => $data['error'], 'messages' => $data['messages']));
        } else {
          go(url('profile/subscription/canceled'));
        }
      }
    ),

    array(
      'pattern' => 'library/(:num)/vocabulary/fluency', 
      'action' => function($bookId) {

        $vocabulary = page('api/vocabulary');
        $vocabulary->setBookId($bookId);
        $data = $vocabulary->fluency();
        
        return response::json($data);
      }
    ),

    array(
      'pattern' => 'library/(:num)/vocabulary/start', 
      'action' => function($bookId) {

        $vocabulary = page('api/vocabulary');
        $vocabulary->setBookId($bookId);
        $data = $vocabulary->start();
        
        return response::json($data);
      }
    ),

    array(
      'pattern' => 'library/(:num)/vocabulary/update', 
      'method'  => 'POST',
      'action' => function($bookId) {

        $vocabulary = page('api/vocabulary');
        $vocabulary->setBookId($bookId);
        $data = $vocabulary->updateVocabulary();

        return response::json($data);
      }
    ),

    array(
      'pattern' => 'library/(:num)/bookmark/add', 
      'method'  => 'POST',
      'action' => function($bookId) {

        $bookmark = page('api/bookmark');
        $bookmark->setBookId($bookId);
        $data = $bookmark->add($bookId);

        return response::json($data);
      }
    ),

    array(
      'pattern' => 'library/(:num)/bookmark/delete', 
      'method'  => 'POST',
      'action' => function($bookId) {

        $bookmark = page('api/bookmark');
        $bookmark->setBookId($bookId);
        $data = $bookmark->deleteBookmark();

        return response::json($data);
      }
    ),


    //
    // Internal
    //

    array(
      'pattern' => 'publish/(:num)/generate', 
      'action' => function($bookId) {

        $publish = page('api/publish');
        $publish->setBookId($bookId);
        $data = $publish->generateVocabulary();

        if ($data['error']) {
          return array("publish/{$bookId}/generate", $data);
        } else {
          go("publish/{$bookId}/edit");
        }
      }
    ),

    array(
      'pattern' => 'publish/(:num)/edit/lookup', 
      'method'  => 'POST',
      'action' => function() {

        $publish = page('api/publish');
        $data = $publish->lookup();

        return response::json($data);
      }
    ),



    array(
      'pattern' => 'publish/(:num)/finalize', 
      'action' => function($bookId) {

        $publish = page('api/publish');
        $publish->setBookId($bookId);
        $data = $publish->finalize();

        return array("publish/{$bookId}/finalize", $data);
      }
    ),

    array(
      'pattern' => 'publish/(:num)/updateboilerplate',
      'action' => function($bookId) {

        $publish = page('api/publish');
        $publish->setBookId($bookId);
        $data = $publish->updateBoilerplate();

        return array("publish/{$bookId}/updateboilerplate", $data);
      }
    ),
    
    array(
      'pattern' => 'webhook',
      'method' => 'GET|POST|DELETE',
      'action' => function() {

        $webhook = page('api/webhook');
        $data = $webhook->receive();

        if ($data['error']) {
          // Email admin
          $email = new Email(array(
            'to'      => site()->email(),
            'from'    => site()->email(),
            'subject' => 'Language in Motion: Webhook Error',
            'body'    => json_encode($data)
          ));
          $email->send();

          return response::error('Something went wrong.', 400, $data);
        } else {
          return response::success();
        }
      }
    ),

  )
);
