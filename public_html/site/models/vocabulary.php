<?php 

class VocabularyPage extends Page {

  public $database;
  public $bookId;

  public function setBookId($bookId) {
    $this->bookId = $bookId;
  }

  public function database() {
    if (!is_null($this->database)) {
      return $this->database;
    } else {
      $databasePath = dirname(dirname(kirby()->roots()->site())) . '/userdata/' . site()->user()->username() . '/userdata.sqlite';
      $this->database = new Database(array(
        'type'     => 'sqlite',
        'database' => $databasePath
      ));
      return $this->database; 
    }
  }


  //
  // CRUD
  //

  public function fluency() {
    $database = $this->database();
    $vocabularyResults = $database->table('vocabulary_results')
                                  ->select(array('dictionary_id', 'fluency'))
                                  ->where(array('book_id' => $this->bookId))
                                  ->all();

    // Simplify collection object
    $vocabulary = array();
    foreach ($vocabularyResults as $record) {
      $vocabulary[] = array('dictionaryId' => $record->dictionary_id(), 'fluency' => $record->fluency());
    }

    return compact('vocabulary');
  }

  public function start() {
    $card = $this->newCard();
    $dictionaryId = $card->dictionary_id();
    $locations = $this->locations($dictionaryId);
    $targetLocation = $card->location();

    return compact('dictionaryId', 'locations', 'targetLocation');
  }

  public function updateVocabulary() {
    $error = false;
    $messages = array();

    if (v::num(get('dictionary_id')) && v::num(get('quality')) && v::num(get('location'))) { 
      
      static $MAX_QUALITY = 5;
      static $QUALITY_SUBTRACTOR = 5;
      static $E_FACTOR_MAX = 2.5;
      static $E_FACTOR_FLOOR = 1.3;

      $database = $this->database();
      $cards = $this->cards();
      $dictionaryId = get('dictionary_id');

      $vocabulary_results = $database->table('vocabulary_results');
      $submittedCard = $vocabulary_results->where(array('dictionary_id' => $dictionaryId))
                                          ->first();
      
      $location = get('location');
      $quality = (float)get('quality');
      $count = $submittedCard->count();
      $ease_factor = $submittedCard->ease_factor();
      $stackLimit = get('stack_limit');

      // Calculate ease factor
      $qFactor = $QUALITY_SUBTRACTOR - $quality;
      $newEaseFactor = $ease_factor + (0.1 - $qFactor * (0.08 + $qFactor * 0.02));
      if ($newEaseFactor < $E_FACTOR_FLOOR) {
        $newEaseFactor = $E_FACTOR_FLOOR;
      } elseif ($newEaseFactor > $E_FACTOR_MAX) {
        $newEaseFactor = $E_FACTOR_MAX;
      }

      // Calculate count
      $count += 1;

      if ($quality < 3) {
        $count = 1;
      }

      // If the user clicks "Know It", all values
      // except the count are processed as if the
      // user clicked "Got It". The count is given the
      // value 5 or greater which then automatically pushes
      // the fluency to 1. Only modifying the count
      // ensures that spaced repetition works properly.
      if (get('know-it') == "true") {
        if ($count < 5) {
          $count = 5;
        }
      }

      // Calculate fluency
      $fluency = null;
      switch ($count) {
        case '1':
          $fluency = 5;
          break;

        case '2':
          $fluency = 4;
          break;

        case '3':
          $fluency = 3;
          break;

        case '4':
          $fluency = 2;
          break;
        
        default:
         $fluency = 1;
          break;
      }

      // Calculate interval
      $interval = 1;
      if ($count == 2) {
        $interval = 6;
      } elseif ($count > 2) {
        $interval = round($submittedCard->rep_interval() * $newEaseFactor);
      }

      // Update database
      try {
        $vocabulary_results
        ->where(array('dictionary_id' => $dictionaryId))
        ->update(array(
          'count' => $count,
          'rep_interval' => $interval,
          'ease_factor' => $newEaseFactor,
          'fluency'     => $fluency,
          'timestamp'   => time()
        ));
      } catch (Exception $e) {
        error_log("Caught $e");
        $error = true;
        $messages[] = 'The vocabulary entry could not be updated. ' . $e->getMessage();
      }


      //
      // Calculate what card to show next
      //

      $nextCard = $this->nextCard($location);

      $increaseStackLimit = false;
      // We've reached our stack limit, the end of the deck, or a card that has already been reviewed
      if (get('reached_stack_limit') == true || empty($nextCard) || $nextCard->count() > 1) {

        $found = false;

        // First search: cards with count of 1
        $firstSearch = $cards->limit($stackLimit)
                             ->filterBy('count', '==', 1)
                             ->filterBy('dictionary_id', '!=', $submittedCard->dictionary_id())
                             ->first();
        if ($firstSearch) {
          $found = true;
          $nextCard = $firstSearch;
        }

        // Second search: cards whose repetition interval does not exceed the current time
        if (!$found) {
          $secondSearch = $cards->limit($stackLimit)
                                ->filterBy('dictionary_id', '!=', $submittedCard->dictionary_id())
                                ->filterBy('count', '<', 5)
                                ->filter(function($card) {
                                  $cardRepInterval = (($card->rep_interval() * 86400) + $card->timestamp());
                                  return $cardRepInterval < time();
                                })
                                ->first();
          if ($secondSearch) {
            $found = true;
            $nextCard = $secondSearch;
          }
        }

        // Last resort: get the card following the stack limit
        if (!$found) {
          $increaseStackLimit = true; // used to notify JS
          $nextCard = $cards->get($stackLimit);

          // If the next card is empty that means
          // we've reached the end of the deck.
          // It is time to bring up any cards with
          // count < 5 while bypassing the repetition interval
          if (empty($nextCard)) {
            // Third search: cards with count of 2
            $thirdSearch = $cards->limit($stackLimit)
                                 ->filterBy('count', '==', 2)
                                 ->filterBy('dictionary_id', '!=', $submittedCard->dictionary_id())
                                 ->first();
            if ($thirdSearch) {
              $found = true;
              $nextCard = $thirdSearch;
            }

            // Fourth search: cards with count of 3
            if (!$found) {
              $fourthSearch = $cards->limit($stackLimit)
                                    ->filterBy('count', '==', 3)
                                    ->filterBy('dictionary_id', '!=', $submittedCard->dictionary_id())
                                    ->first();
              if ($fourthSearch) {
                $found = true;
                $nextCard = $fourthSearch;
              }
            }

            // Fifth search: cards with count of 4
            if (!$found) {
              $fifthSearch = $cards->limit($stackLimit)
                                   ->filterBy('count', '==', 4)
                                   ->filterBy('dictionary_id', '!=', $submittedCard->dictionary_id())
                                   ->first();
              if ($fifthSearch) {
                $found = true;
                $nextCard = $fifthSearch;
              }
            }
          }
        }
      }

      // If after all of this the next card is empty,
      // that means there is no card available and
      // the user has completed vocabulary memorization.
      // Instead of repeating the first card 
      // over and over, show a message.
      if (empty($nextCard)) {
        return array('completedVocabulary' => true, 'message' => "Nice job. You've reached 100% fluency.");
      }

      $card = $nextCard;

      $nextDictionaryId = $card->dictionary_id();
      $nextLocations = $this->locations($card->dictionary_id());
      $nextTargetLocation = $card->location();
      
      return compact('error', 'messages', 'fluency', 'location', 'dictionaryId', 'nextDictionaryId', 'nextLocations', 'nextTargetLocation', 'increaseStackLimit');

    } else {
      $error = true;
      $messages[] = "Invalid form data.";
      return compact('error', 'messages');
    }
  }


  //
  // Private helpers
  //

  public function cards($dictionaryId = '*') {
  
    $result = null;
    $database = $this->database();

    if ($dictionaryId == '*') {
      $result = $database->table('vocabulary_results')
                         ->select(array(
                          'dictionary_id',
                          'location',
                          'count',
                          'ease_factor',
                          'rep_interval',
                          'timestamp'))
                         ->where(array('book_id' => $this->bookId))
                         ->order('location ASC')
                         ->all();
    } else {
      $result = $database->table('vocabulary_results')
                         ->select(array(
                          'dictionary_id',
                          'location',
                          'count',
                          'ease_factor',
                          'rep_interval',
                          'timestamp'))
                         ->where(array('book_id' => $this->bookId))
                         ->where(array('dictionary_id' => $dictionaryId))
                         ->order('location ASC')
                         ->first();
    }

    return $result;
  }

  public function newCard() {
    
    $cards = $this->cards();
    $found = false;

    // First search: cards with 1 count
    foreach ($cards as $card) {
      if ($card->count() == 1) {
        return $card;
      }
    }

    // Second search: cards whose repitition interval does not exceed the current time
    foreach ($cards as $card) {
      $cardRepInterval = (($card->rep_interval() * 86400) + $card->timestamp());
      if ($cardRepInterval < time()) {
        return $card;
      }
    }

    return $cards->first();

  }

  public function nextCard($location) {
    $cards = $this->cards();
    $result = $cards->filterBy('location', '>', $location)->first();
    return $result;
  }

  public function locations($dictionaryId) {
    $database = new Database(array(
      'type' => 'sqlite',
      'database' => c::get('dictionaryDb')
    ));

    $records = $database->table('vocabulary')
                        ->select(array('location'))
                        ->where(array('dictionary_id' => $dictionaryId))
                        ->where(array('book_id' => $this->bookId))
                        ->all();

    // Simplify the collection object
    $result = array();
    foreach ($records as $record) {
      $result[] = $record->location();
    }

    return $result;
  }

}
