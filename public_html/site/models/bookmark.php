<?php

class BookmarkPage extends Page {

  public $bookId;

  public function setBookId($bookId) {
    $this->bookId = $bookId;
  }

  public function add() {
    $error = false;
    $messages = array();

    if (!v::num(get('add_location'))) {
      $error = true;
      $messages[] = 'Empty or invalid location.';
    }

    if ($error) {
      return compact('error', 'messages');
    } else {

      $databasePath = dirname(dirname(kirby()->roots()->site())) . '/userdata/' . site()->user()->username() . '/userdata.sqlite';
      $userDatabase = new Database(array(
        'type' => 'sqlite',
        'database' => $databasePath
      ));
      $bookmarks = $userDatabase->table('bookmarks');

      // Check if there is already a bookmark at the same location
      $location = (int)get('add_location');
      $exists = $bookmarks->where(array('book_id' => $this->bookId))
                          ->where(array('location' => $location))
                          ->first();
      // If not, proceed
      if (!$exists) {

        $page = page('library')->children()->find($this->bookId);

        // Text is divided into pages so combine
        // them all in order to create excerpt.
        $completeBookText = '';
        foreach ($page->text()->toStructure() as $bookPage) {
          $completeBookText .= $bookPage->page_text();
        }
        // Get text following <ruby id='$location'>
        preg_match("/id\=\'{$location}\'.*?\>(.*)/", $completeBookText, $captured);
        // Remove ruby kana
        $unkanafied = preg_replace("/\<rt\>.*?\<\/rt\>/", '', $captured[1]);
        // Strip HTML and shorten it to 15 characters
        $excerpt = str::short(str::unhtml($unkanafied), 15);

        try {
          $userDatabase->table('bookmarks')->insert(array(
            'book_id' => $this->bookId,
            'location' => $location,
            'excerpt' => $excerpt
          ));
        } catch (Exception $e) {
          error_log("Caught $e");
          $error = true;
          $messages = array('The bookmark could not be added. ' . $e->getMessage());
        }
      }

      $bookmarks = $userDatabase->table('bookmarks')
                                ->where(array('book_id' => $this->bookId))
                                ->order('location ASC')
                                ->all();
      $bookmarksSnippet = snippet('bookmarks', array('bookmarks' => $bookmarks), true);

      return compact('bookmarksSnippet', 'error', 'messages');
    }
  }

  public function deleteBookmark() {

    $data = array(
      'delete_location' => get('delete_location'),
    );

    $rules = array(
      'delete_location' => array('required', 'num'),
    );

    $messages = array(
      'delete_location' => 'Invalid location.',
    );

    if($invalid = invalid($data, $rules, $messages)) {
      $error = true;
      return array('error' => $error, 'messages' => $invalid);
    } else {
      $databasePath = dirname(dirname(kirby()->roots()->site())) . '/userdata/' . site()->user()->username() . '/userdata.sqlite';
      $userDatabase = new Database(array(
        'type' => 'sqlite',
        'database' => $databasePath
      ));
      $bookmarks = $userDatabase->table('bookmarks');

      $location = (int)get('delete_location');

      try {
        $userDatabase->table('bookmarks')
                     ->where(array('book_id' => $this->bookId))
                     ->where(array('location' => $location))
                     ->delete();

        $bookmarks = $userDatabase->table('bookmarks')
                                  ->where(array('book_id' => $this->bookId))
                                  ->order('chapter_number ASC')
                                  ->order('location ASC')
                                  ->all();
        $bookmarksSnippet = snippet('bookmarks', array('bookmarks' => $bookmarks), true);

        return compact('bookmarksSnippet');
      } catch (Exception $e) {
        error_log("Caught $e");
        $error = true;
        $messages = array('The bookmark could not be saved. ' . $e->getMessage());
        
        return compact('error', 'messages');
      }
    }
  }
}
