<?php

require_once(kirby()->roots()->plugins() . '/api/vendor/stripe-php/init.php');

class WebhookPage extends Page {

  public function receive() {
  
    \Stripe\Stripe::setApiKey(c::get('stripeSecretKey'));

    // Retrieve the request's body and parse it as JSON
    $input = @file_get_contents("php://input");
    $event_json = json_decode($input);

    // Check against Stripe to confirm that the ID is valid
    $event = \Stripe\Event::retrieve($event_json->id);

    if (isset($event)) {
      switch ($event->type) {
        case 'customer.created':
          return $this->customerCreated($event);
          break;
        
        case 'customer.updated':
          return $this->customerUpdated($event);
          break;

        case 'customer.deleted':
          return $this->customerDeleted($event);
          break;

        case 'invoice.payment_succeeded':
          return $this->paymentSucceeded($event);
          break;

        case 'invoice.payment_failed':
          return $this->paymentFailed($event);
          break;

        default:
          return $this->unknownEventReceived($event); // Email admin details in case
      }
    }
  }

  private function customerCreated($event) {
    $error = false;
    $messages = array();

    $userEmail = $event->data->object->email;

    $email = new Email(array(
      'to'      => $userEmail,
      'from'    => site()->email(),
      'subject' => snippet('emails/subject/customer_created', array(), true),
      'body'    => snippet('emails/body/customer_created', array(), true)
    ));

    $sent = $email->send();

    if (!$sent) {
      $error = true;
      $messages[] = 'Customer created webhook email failed.';
    }

    return compact('error', 'messages');
  }

  private function customerUpdated($event) {
    $error = false;
    $messages = array();

    // Possibly user only updated email,
    // so check if default_source (i.e. card)
    // was updated and only then send an email.
    if (isset($event->data->previous_attributes->default_source)) {
      $customer = \Stripe\Customer::retrieve(array('id' => $event->data->object->id, 'expand' => array('default_source')));
      $brand = $customer->default_source->brand;
      $last4 = $customer->default_source->last4;
      $expMonth = $customer->default_source->exp_month;
      $expYear = $customer->default_source->exp_year;

      $email = new Email(array(
        'to'      => $customer->email,
        'from'    => site()->email(),
        'subject' => snippet('emails/subject/customer_updated', array(), true),
        'body'    => snippet('emails/body/customer_updated', compact('brand', 'last4', 'expMonth', 'expYear'), true)
      ));

      $sent = $email->send();

      if (!$sent) {
        $error = true;
        $messages[] = 'Customer source updated webhook email failed.';
      }
    }

    return compact('error', 'messages');
  }

  private function customerDeleted($event) {
    $error = false;
    $messages = array();

    $customerId = $event->data->object->id;
    $userEmail = $event->data->object->email;

    // Email admin
    $emailToAdmin = new Email(array(
      'to'      => site()->email(),
      'from'    => site()->email(),
      'subject' => snippet('emails/admin/subject/customer_deleted', array(), true),
      'body'    => snippet('emails/admin/body/customer_deleted', array('email' => $userEmail, 'customerId' => $customerId), true)
    ));

    $sentAdmin = $emailToAdmin->send();

    if (!$sentAdmin) {
      $error = true;
      $messages[] = 'Failed to send admin subscription canceled email.';
    }

    // Email user
    $emailToUser = null;
    $emailToUser = new Email(array(
      'to'      => $userEmail,
      'from'    => site()->email(),
      'subject' => snippet('emails/subject/customer_deleted', array(), true),
      'body'    => snippet('emails/body/customer_deleted', array(), true)
    ));
    $sentUser = $emailToUser->send();

    if (!$sentUser) {
      $error = true;
      $messages[] = 'Failed to send user subscription canceled email.';
    }

    return compact('error', 'messages');
  }

  private function paymentSucceeded($event) {
    $error = false;
    $messages = array();

    $customer = \Stripe\Customer::retrieve(array('id' => $event->data->object->customer, 'expand' => array('default_source')));
    $amount = $this->formatStripeAmount($event->data->object->amount_due);
    $periodStart = $this->formatStripeTimestamp($customer->subscriptions->data[0]->current_period_start);
    $periodEnd = $this->formatStripeTimestamp($customer->subscriptions->data[0]->current_period_end);
    $brand = $customer->default_source->brand;
    $last4 = $customer->default_source->last4;

    $email = new Email(array(
      'to'      => $customer->email,
      'from'    => site()->email(),
      'subject' => snippet('emails/subject/payment_succeeded', array(), true),
      'body'    => snippet('emails/body/payment_succeeded', compact('amount', 'periodStart', 'periodEnd', 'brand', 'last4'), true)
    ));

    $sent = $email->send();

    if (!$sent) {
      $error = true;
      $messages[] = 'Payment succeeded webhook email failed.';
    }

    return compact('error', 'messages');
  }

  private function paymentFailed($event) {
    $error = false;
    $messages = array();

    $customer = \Stripe\Customer::retrieve($event->data->object->customer);
    $amount = $this->formatStripeAmount($event->data->object->amount_due);

    $email = new Email(array(
      'to'      => $customer->email,
      'from'    => site()->email(),
      'subject' => snippet('emails/subject/payment_failed', array(), true),
      'body'    => snippet('emails/body/payment_failed', compact('amount'), true)
));

    $sent = $email->send();

    if (!$sent) {
      $error = true;
      $messages[] = 'Payment failed webhook email failed.';
    }

    return compact('error', 'messages');
  }

  private function unknownEventReceived($event) {
    $error = false;
    $messages = array();

    $email = new Email(array(
      'to'      => site()->email(),
      'from'    => site()->email(),
      'subject' => snippet('emails/admin/subject/unknown_event_received', array(), true),
      'body'    => snippet('emails/admin/body/unknown_event_received', compact('event'), true)
    ));

    $sent = $email->send();

    if (!$sent) {
      $error = true;
      $messages[] = 'Unknown event received webhook email failed.';
    }

    return compact('error', 'messages');
  }


  // Helpers
  
  private function formatStripeAmount($amount) {
    return '¥' . $amount;
  }

  private function formatStripeTimestamp($timestamp) {
    return strftime("%m/%d/%Y", $timestamp);
  }

}
