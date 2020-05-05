<?php 

class PublishPage extends Page {

  public $database;
  public $edictDatabase;
  public $bookId;
   
  public function setBookId($bookId) {
    $this->bookId = $bookId;
  }

  public function database() {
    if (!is_null($this->database)) {
      return $this->database;
    } else {
      $this->database = new Database(array(
        'type'     => 'sqlite',
        'database' => c::get('dictionaryDb')
      ));
      return $this->database; 
    }
  }

  public function edictDatabase() {
    if (!is_null($this->edictDatabase)) {
      return $this->edictDatabase;
    } else {
      $this->edictDatabase = new Database(array(
        'type'     => 'sqlite',
        'database' => c::get('edictDb')
      ));
      return $this->edictDatabase; 
    }
  }

  public function generateVocabulary() {
    $error = false;
    $messages = array();

    $page = site()->pages()->find('publish')->children()->find($this->bookId);

    // Don't go further if vocabulary has already been generated
    if ($page->status() == "Editing" || $page->status() == "Finalized" || $page->status() == "Published") {
      $error = true;
      $editUrl = $page->children()->find('edit')->url();
      $messages[] = "Vocabulary has already been generated. <a href='{$editUrl}' class='btn'>Edit vocabulary.</a>";
      return compact('error', 'messages');
    }

    ini_set('max_execution_time', 3600); // 3600 seconds = 60 minutes; Generating can take a while.

    $annotated = array(array('title_array' => null, 'number' => null, 'text_array' => null));
    $chapters = $page->text()->toStructure();

    foreach ($chapters as $index => $chapter) {

      // Construct title for annotation
      $titlePreppedForSegmenting = preg_replace_callback('/\<ruby\>(.*?)\<\/ruby\>/u', function($matches) { 
         return "|*$matches[1]|"; // add "*" to indicate a definition is needed 
      }, $chapter->title());
      $titleSegments = preg_split("/\|/u", $titlePreppedForSegmenting);
      $titleArray = array();

      foreach ($titleSegments as $titleSegment) {
        if ($titleSegment != "") { // A empty segment will be made when two ruby enlosed words are next to each other
          if (str::startsWith($titleSegment, '*')) { 
            $titleArray[] = $this->constructSegment($titleSegment);
          } else {
            $titleArray[] = array($titleSegment);
          }
        }
      }

      // Construct text for annotation
      $paragraphs = preg_split("/\n/", $chapter->text()); // Split on new line and add back on later so that parsing works
      $textArray = array();

      foreach ($paragraphs as $paragraph) {
        $preppedForSegmenting = preg_replace_callback('/\<ruby\>(.*?)\<\/ruby\>/u', function($matches) { 
           return "|*$matches[1]|"; // add "*" to indicate a definition is needed 
        }, $paragraph);
        $segments = preg_split("/\|/u", $preppedForSegmenting);

        foreach ($segments as $segment) {
          if ($segment != "") { // A empty segment will be made when two ruby enlosed words are next to each other
            if (str::startsWith($segment, '*')) { 
              $textArray[] = $this->constructSegment($segment);
            } else {
              $textArray[] = array($segment);
            }
          }
        }
        $textArray[] = array('\n');
      }
      
      $annotated[$index]['title_array'] = $titleArray;
      $annotated[$index]['number'] = $chapter->number();
      $annotated[$index]['text_array'] = $textArray;
    }

    try {
      // Bypass the toolkit's yaml::encode method in order to include $wordwrap = 0.
      // Unless wordwrap is removed, the database cannot read some characters properly.
      // preg_replace is in the toolkit's method so that has been duplicated even if unnecessary.
      $page->update(array(
        'annotated' => preg_replace('!^---\n!', '', spyc::yamldump($annotated, $indent = false, $wordwrap = 0, $no_opening_dashes = false))
      ));
    } catch (exception $e) {
      $error = true;
      $messages[] = 'The annotated text could not be saved. ' . $e->getmessage();
    }

    // Update the book status to "Editing"
    try {
      $page->update(array('status' => 'Editing')); 
    } catch (exception $e) {
      $error = true;
      $messages = 'The book status could not be updated. ' . $e->getmessage();
    } 

    return compact('error', 'messages');
  }

  public function constructSegment($segment) {
    $database = $this->database();
    $vocabulary = $database->table('vocabulary');
    $edict = $this->edictDatabase()->table('edict');
    $word = str::substr($segment, 1);
    $kana = "";
    $english = "";
    $existing = $vocabulary->where(array('word' => $word))->first();

    // If the word doesn't have a vocabulary entry (i.e. previous books do not contain the word)
    // look it up in EDICT
    if (!$existing) {
      $result = $edict->where('kanji', 'LIKE', $word)->first();
      if (!$result) {
        $result = $edict->where('kana', 'LIKE', $word)->first();
        if (!$result) {
          $result = $edict->where('kanji', 'LIKE', $word . '%')->first(); 
          if (!$result) {
            $result = $edict->where('kana', 'LIKE', $word . '%')->first();
          }
        }
      }

      if ($result) {
        $kana = $result->kana();
        $english = $result->english();
      }
    } else {
      $kana = $existing->kana();

      // The English definition of a word is kept separate from the vocabulary table entries
      // because words with different auxiliaries each have a unique vocabulary entry
      // but their definition is one and the same, thus storing it separately reduces redundancy.
      //
      // The vocabulary table's word and kana fields are only used when publishing new books.
      // Duplicate words and phrases in new books reuse the kana and English prescribed
      // in previous books.
      //
      // Note that the word and kana fields in the dictionary table are never used (and the English field only here).
      // They exist for conveniency in debugging (e.g., confirming that the right dictionary IDs are added to the text).
      // The tense, auxiliaries, etc., in those fields are arbitrary, having been constructed from
      // the first appearance of the word in a book.
      $existingDictionaryEnglish = $database->table('dictionary')
                                            ->where(array('dictionary_id' => $existing->dictionary_id()))
                                            ->first()->english();

      $english = $existingDictionaryEnglish;
    }

    return array($word, $kana, $english);
  }

  public function lookup() {

    $error = false;
    $message = '';
    $edictDatabase = new Database(array(
      'type' => 'sqlite',
      'database' => c::get('edictDb')
    ));
    $edict = $edictDatabase->table('edict');
    $database = $this->database();
    $word = get('word');
    $dictionary = $database->table('dictionary');


    // Look up LiM dictionary first
    $results = $dictionary->where('word', '=', $word)->all();
    // Then try EDICT
    if ($results->count() < 1) {
      // If kana
      if (!v::match($word, "/[\x{4E00}-\x{9FBF}]+/u")) {
        $results = $edict->where('kana', '=', $word)->all();
        if ($results->count() < 1) {
          $results = $edict->where('kana', 'LIKE', $word . '%')->all(); 
        }
      } else { // kanji
        $results = $edict->where('kanji', '=', $word)->all();
        if ($results->count() < 1) {
          $results = $edict->where('kanji', 'LIKE', $word . '%')->all();
        }
      } 
    }

    if ($results) {
      $data = array();
      foreach ($results as $result) {
        $english = str_replace('"', '&quot;', $result->english()); // Replace double quotes with HTML entity
        $data[] = new Collection(array('kanji' => $result->kanji(), 'kana' => $result->kana(), 'english' => $english));
      }
      $sanitizedResults = new Collection($data);
      $publishEdictLookupSnippet = snippet('publish_edict_lookup', array('results' => $sanitizedResults), true);
    } else {
      $error = true;
      $message = 'Nothing found';
    }

    return compact('error', 'message', 'publishEdictLookupSnippet');
  }

  public function finalize() {
    $error = false;
    $messages = array();

    $page = site()->pages()->find('publish')->children()->find($this->bookId);
    $chapters = $page->annotated()->yaml();
    $database = $this->database();
    $vocabulary = $database->table('vocabulary');

    // Don't go further if this book has already been finalized
    if ($page->status() == "Finalized" || $page->status() == "Published") {
      $error = true;
      $libraryURL = page('library')->children()->find($this->bookId)->url();
      $messages[] = "This book has already been finalized.";
      return compact('error', 'messages');
    }

    ini_set('max_execution_time', 900); // 900 seconds = 15 minutes; Generating can take a while.
    
    $location = 0; // a continuous index through chapters

    foreach ($chapters as $chapter) {

      // Add title words to database if more than 1 chapter
      //
      // In the case that a book has no actual chapters,
      // just 1 chapter will be created with the book's title
      // as the chapter title. It will not be displayed to the user,
      // so it's title words should not be added to the database.
      if (count($chapters) > 1) {
        foreach ($chapter['title_array'] as $segment) {
          if (count($segment) > 1) { // More than one item means it has a definition
            $location += 1;
            $result = $this->addSegmentToDatabase($segment, $location);

            if ($result['error']) {
              $error = true;
              foreach ($result['messages'] as $message) {
                $messages[] = $message;
              }
            }

          }
        }
      }

      // Add text words to database
      foreach ($chapter['text_array'] as $segment) {
        if (count($segment) > 1) { // More than one item means it has a definition
          $location += 1;
          $result = $this->addSegmentToDatabase($segment, $location);

          if ($result['error']) {
            $error = true;
            foreach ($result['messages'] as $message) {
              $messages[] = $message;
            }
          }

        }
      } 
    }

    // Create a string from the annotated array with data- attributes
    $finalizedText = '';
    $location = 0; // A continuous index through chapters
    foreach ($chapters as $index => $chapter) {

      // Create title string
      //
      // In the case that a book has no actual chapters,
      // just 1 chapter will be created with the book's title
      // as the chapter title. It will not be displayed to the user,
      // so it's title words should not be given ruby markup.

      if (count($chapters) > 1) {
        $titleText = '<h2>';

        foreach ($chapter['title_array'] as $segment) {
          if (count($segment) > 1) { // It has a definition
            $location += 1;
            $word = $segment[0];
            $kana = $segment[1];
            $english = str_replace("'", '&#39;', $segment[2]); // Replace single quotes with HTML entity
            $dictionaryId = $vocabulary->where(array('location' => $location))
                                       ->where(array('book_id' => $this->bookId))
                                       ->first()
                                       ->dictionary_id();
            $locations = $this->locations($dictionaryId);

            $titleText .= "<ruby id='$location' data-dictionary-id='$dictionaryId' data-english='$english' data-locations='$locations' data-fluency='5'><rb>$word</rb><rt>$kana</rt></ruby>";    
          } else {
            $titleText .= $segment[0];
          } 
        }

        $titleText .= '</h2>';
        $finalizedText .= $titleText;
      } 

      // Create text string
      $chapterText = '';
      foreach ($chapter['text_array'] as $segment) {
        if (count($segment) > 1) { // It has a definition
          $location += 1;
          $word = $segment[0];
          $kana = $segment[1];
          $english = str_replace("'", '&#39;', $segment[2]); // Replace single quotes with HTML entity
          $dictionaryId = $vocabulary->where(array('location' => $location))
                                     ->where(array('book_id' => $this->bookId))
                                     ->first()
                                     ->dictionary_id();
          $locations = $this->locations($dictionaryId);

          $chapterText .= "<ruby id='$location' data-dictionary-id='$dictionaryId' data-english='$english' data-locations='$locations' data-fluency='5'><rb>$word</rb><rt>$kana</rt></ruby>";    
        } else {
          $chapterText .= $segment[0] == '\n' ? "\n<br>\n" : $segment[0];
        } 
      }

      $finalizedText .= $chapterText;
    }            

    try {
      $page->update(array('finalized_text' => $finalizedText));
    } catch(Exception $e) {
      error_log("Caught $e");
      $error = true;
      $messages[] = 'Failed to add finalized data attributes to the ruby text. ' . $e->getMessage();
    }

    // If this is a new edition of an existing book,
    // delete the previous edition's entries from
    // the vocabulary table.
    if ($page->previous_edition_id()->exists()) {
      try {
        $vocabulary->where(array('book_id' => $page->previous_edition_id()))->delete();
      } catch (Exception $e) {
        error_log("Caught $e");
        $error = true;
        $messages[] = "Failed to delete previous edition's vocabulary entries. " . $e->getMessage();
      }
    }

    // Create page library/book-id
    try {
      if ($page->previous_edition_id()->exists()) {
        page('library')
          ->children()
          ->create(
            $this->bookId,
            'read',
            array(
            'title'               => $page->title(),
            'title_ruby'          => $page->title_ruby(),
            'title_english'       => $page->title_english(),
            'author'              => $page->author(),
            'author_english'      => $page->author_english(),
            'blurb'               => $page->blurb(),
            'premium'             => $page->premium(),
            'previous_edition_id' => $page->previous_edition_id(), // Just this is different
            'text'                => $page->finalized_text()
        ));
      } else {
        page('library')
          ->children()
          ->create(
            $this->bookId,
            'read',
            array(
            'title'          => $page->title(),
            'title_ruby'     => $page->title_ruby(),
            'title_english'  => $page->title_english(),
            'author'         => $page->author(),
            'author_english' => $page->author_english(),
            'blurb'          => $page->blurb(),
            'premium'        => $page->premium(),
            'text'           => $page->finalized_text()
        ));
      }
    } catch (Exception $e) {
      error_log("Caught $e");
      $error = true;
      $messages[] = 'The library/book-id page could not be created. ' . $e->getMessage();
    }

    // Update the book status to "Finalized"
    try {
      $page->update(array('status' => 'Finalized'));
    } catch (Exception $e) {
      error_log("Caught $e");
      $error = true;
      $messages[] = 'The page status could not be updated. ' . $e->getMessage();
    } 

    return compact('error', 'messages');
  }

  public function addSegmentToDatabase($segment, $location) {
    $error = false;
    $messages = array();

    $database = $this->database();
    $dictionary = $database->table('dictionary');
    $vocabulary = $database->table('vocabulary');

    $existing = $dictionary->where(array('english' => $segment[2]))
                           ->first();
    $dictionnaryId = null;
    if (!$existing) {
      try {
        $dictionary->insert(array(
          'dictionary_id' => null,
          'word' => $segment[0],
          'kana' => $segment[1],
          'english' => $segment[2]
        ));

        $dictionaryId = $dictionary->count(); // use the autmatically generated dictionary_id

      } catch (exception $e) {
        $error = true;
        $messages[] = 'failed to add to dictionary table. ' . $e->getmessage();
      }
    } else {
      $dictionaryId = $existing->dictionary_id();
    }

    try {
      $vocabulary->insert(array(
        'dictionary_id' => $dictionaryId, 
        'word'          => $segment[0],
        'kana'          => $segment[1],
        'book_id'       => $this->bookId,
        'location'      => $location
      ));
    } catch (exception $e) {
      $error = true;
      $messages[] = 'failed to add to vocabulary table. ' . $e->getmessage();
    }

    return compact('error', 'messages');
  }

  public function updateBoilerplate() {
    $error = false;
    $messages = array();

    $page = site()->pages()->find('publish')->children()->find($this->bookId);

    // Don't go further if this book has already been published
    if ($page->status() == "Published") {
      $error = true;
      $messages[] = "Boilerplate has already been updated for this book.";
      return compact('error', 'messages');
    }

    $database = $this->database();
    $vocabularyEntries = $database->table('vocabulary')->where(array('book_id' => $this->bookId))->all();

    try {

      // Remove duplicates
      $uniqueDictionaryIds = array();
      $uniqueVocabulary = array();
      foreach ($vocabularyEntries as $key => $word) {
        if (!in_array($word->dictionary_id(), $uniqueDictionaryIds)) {
          $uniqueDictionaryIds[] = $word->dictionary_id();
          $uniqueVocabulary[] = $word;
        }
      }

      $boilerplateDatabase = new Database(array(
        'type' => 'sqlite',
        'database' => c::get('userBoilerplateDb')
      ));

      foreach ($uniqueVocabulary as $word) {
        $boilerplateDatabase->table('vocabulary_results')->insert(array(
          'dictionary_id' => $word->dictionary_id(),
          'book_id'       => $word->book_id(),
          'location'      => $word->location(),
          'count'         => 1,
          'rep_interval'  => 1,
          'ease_factor'   => 2.5,
          'fluency'       => 5,
          'timestamp'     => time()
        ));
      }
    } catch(Exception $e) {
      error_log("Caught $e");
      $error = true;
      $messages[] = 'Failed to update the user boilerplate database. ' . $e->getMessage();
    }

    // If this is a new edition of an existing book,
    // delete the previous edition's entries from boilerplate.
    if ($page->previous_edition_id()->exists()) {
      try {
        $boilerplateDatabase->table('vocabulary_results')->where(array('book_id' => $page->previous_edition_id()))->delete();
      } catch (Exception $e) {
        error_log("Caught $e");
        $error = true;
        $messages[] = "Failed to delete previous edition's vocabulary_results entries. " . $e->getMessage();
      }
    }

    // Update the book status to "Published"
    try {
      $page->update(array('status' => 'Published'));
    } catch (Exception $e) {
      error_log("Caught $e");
      $error = true;
      $messages[] = 'The page status could not be updated. ' . $e->getMessage();
    } 

    return compact('error', 'messages');
  }

  public function locations($dictionaryId) {
    $database = $this->database();

    $records = $database->table('vocabulary')
                        ->select(array('location'))
                        ->where(array('dictionary_id' => $dictionaryId))
                        ->where(array('book_id' => $this->bookId))
                        ->all();

    // Create a string of comma separated values
    $result = '';
    foreach ($records as $record) {
      $result .= $record->location() . ',';
    }
    $result = str::substr($result, 0, -1); // remove trailing ","

    return $result;
  }

}
