<?php 

class LibraryPage extends Page {

  public $database;
   
  public function userDatabase() {
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

  public function hasVocabularyResults($bookId) {
    $userDatabase = $this->userDatabase();
    return $userDatabase->table('vocabulary_results')->where(array('book_id' => $bookId))->first();
  }

  public function newBook($bookId) {
    $hasVocabularyResults = $this->hasVocabularyResults($bookId);
    $book = page('library')->children()->find($bookId);

    if (!$hasVocabularyResults && !$book->previous_edition_id()->exists()) {
      return true;
    } else {
      return false;
    }
  }

  public function newEdition($bookId) {
    $hasVocabularyResults = $this->hasVocabularyResults($bookId);
    $book = page('library')->children()->find($bookId);

    if ($book->previous_edition_id()->exists() && !$hasVocabularyResults) {
      return true;
    } else {
      return false;
    }
  }

  public function progress($bookId) {
    $userDatabase = $this->userDatabase();
    $vocabularyResults = $userDatabase->table('vocabulary_results')
                                      ->select(array('fluency'))
                                      ->where(array('book_id' => $bookId))
                                      ->all();

    $numberOfVocabulary = $vocabularyResults->count();
    $completedVocabulary = 0.0;

    foreach ($vocabularyResults as $record) {
      if ($record->fluency() == 4) {
        $completedVocabulary += 0.25;
      } elseif ($record->fluency() == 3) {
        $completedVocabulary += 0.33;
      } elseif ($record->fluency() == 2) {
        $completedVocabulary += 0.50;
      } elseif ($record->fluency() == 1) {
        $completedVocabulary += 1;
      }
    }

    return (int)(($completedVocabulary / $numberOfVocabulary) * 100);
  }

}
