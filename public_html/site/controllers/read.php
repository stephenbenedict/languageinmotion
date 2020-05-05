<?php 

return function($site, $pages, $page) {

  if (!$site->user()) go('login');
  $error = false;
  $messages = array();

  // Get book pages
  $bookPages = $page->text()->toStructure();

  // If it is a recently added book
  // which the user opens for the first time,
  // the user's vocabulary_results table
  // must be updated with the new book's vocabulary.

  // User databases are located in /var/www/languageinmotion.jp/userdata/username (above web root)
  // The double "dirname()" gets the directory one level above web root
  $databasePath = dirname(dirname(kirby()->roots()->site())) . '/userdata/' . $site->user()->username() . '/userdata.sqlite';
  $userDatabase = new Database(array(
    'type' => 'sqlite',
    'database' => $databasePath
  ));

  $vocabularyResults = $userDatabase->table('vocabulary_results');
  $hasVocabularyResults = $vocabularyResults->where(array('book_id' => $page->uid()))
                                            ->first();

  if (!$hasVocabularyResults) {
      $database = new Database(array(
        'type' => 'sqlite',
        'database' => c::get('dictionaryDb')
      ));

    $vocabularyEntries = $database->table('vocabulary')->where(array('book_id' => $page->uid()))->all();

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

      foreach ($uniqueVocabulary as $word) {
        // Use existing vocabulary results data if it exists.
        $existing = $vocabularyResults->where(array('dictionary_id' => $word->dictionary_id()))->first();

        if ($existing) {
          $vocabularyResults->insert(array(
            'dictionary_id' => $word->dictionary_id(),
            'book_id'       => $word->book_id(),
            'location'      => $word->location(),
            'count'         => $existing->count(),
            'rep_interval'  => $existing->rep_interval(),
            'ease_factor'   => $existing->ease_factor(),
            'fluency'       => $existing->fluency(),
            'timestamp'     => $existing->timestamp()
          ));
        } else {
          $vocabularyResults->insert(array(
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
      } 
    } catch(Exception $e) {
      error_log("Caught $e");
      $error = true;
      $messages[] = 'Failed to populate the vocabulary_results tables. ' . $e->getMessage();
    }

    if ($page->previous_edition_id()->exists()) {
      // Delete previous edition's vocabulary_results.
      //
      // It is possible that the user does not have
      // vocabulary results for either the previous edition or the
      // new edition (e.g. if he signed up before the book was
      // first published). So only proceed if the previous edition's
      // vocabulary_results exist.
      $prevEditionHasVocabularyResults = $vocabularyResults->where(array('book_id' => $page->previous_edition_id()))
                                                           ->first();
      if ($prevEditionHasVocabularyResults) {
        try {
          $vocabularyResults->where(array('book_id' => $page->previous_edition_id()))->delete();
        } catch (Exception $e) {
          error_log("Caught $e");
          $error = true;
          $messages[] = "Failed to delete previous edition's vocabulary_results entries. " . $e->getMessage();
        }
      }

      // Transfer bookmarks from previous to new edition
      $prevEditionBookmarks = $userDatabase->table('bookmarks')
                                           ->where(array('book_id' => $page->previous_edition_id()))
                                           ->all();
      try {
        foreach ($prevEditionBookmarks as $prevBookmark) {
          // If location exists in new edition,
          // insert new entry with new edition book ID
          // and regenerate the excerpt just in case 
          // word location values have changed.
          if ($vocabularyEntries->findBy('location', $prevBookmark->location())) { 
            
            // Text is divided into pages so combine
            // them all in order to create excerpt.
            $completeBookText = '';
            foreach ($bookPages as $bookPage) {
              $completeBookText .= $bookPage->page_text();
            }
            // Get text following <ruby id='$location'>
            preg_match("/id\=\'{$prevBookmark->location()}\'.*?\>(.*)/", $completeBookText, $captured);
            // Remove ruby kana
            $unkanafied = preg_replace("/\<rt\>.*?\<\/rt\>/", '', $captured[1]);
            // Strip HTML and shorten it to 15 characters
            $excerpt = str::short(str::unhtml($unkanafied), 15);

            $userDatabase->table('bookmarks')->insert(array(
              'book_id' => $page->uid(),
              'location' => $prevBookmark->location(),
              'excerpt' => $excerpt
            ));
          }

          // Then delete all previous edition bookmarks.
          // If a bookmark's location was not found in the
          // new edition (e.g. the bookmark's location is
          // one of the last words in the book which
          // were combined into a single word, thus one less
          // location value (unlikely)), then it will be deleted
          // without being transferred to the new edition.
          $userDatabase->table('bookmarks')
                       ->where(array('book_id' => $page->previous_edition_id()))
                       ->delete();
        }
      } catch (Exception $e) {
        error_log("Caught $e");
        $error = true;
        $messages = array('Bookmarks could not be updated. ' . $e->getMessage());
      }
    }
  }

  // Get bookmarks
  $bookmarks = $userDatabase->table('bookmarks')
                            ->where(array('book_id' => $page->uid()))
                            ->order('chapter_number ASC')
                            ->order('location ASC')
                            ->all();


  return compact('error', 'messages', 'bookPages', 'bookmarks');
};
