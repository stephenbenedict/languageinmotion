<?php

$database = new Database(array(
  'type'     => c::get('dbType'),
  'host'     => c::get('dbHost'),
  'database' => c::get('dbName'),
  'user'     => c::get('dbUser'),
  'password' => c::get('dbPassword')
));

// Setup draft_dictionary table
// Only used internally for publishing, with convenience fields.
$draftDictionary = $database->table('draft_dictionary');
if (!$draftDictionary->where('dictionary_id', 'LIKE', '%')->first()) { // Try selecting a row to check if table exists
  try {
    $database->createTable('draft_dictionary', array(
      'dictionary_id' => array(
        'type'  => 'id'
      ),
      'book_id' => array( // Convenience field
        'type' => 'text'
      ),
      'word' => array(
        'type' => 'text'
      ),
      'kana' => array(
        'type' => 'text'
      ),
      'english' => array(
        'type' => 'text'
      ),
      'sentence' => array( // Convenience field
        'type' => 'text'
      ),
      'chapter' => array( // Convenience field
        'type' => 'text'
      ),
      'location' => array( // Convenience field
        'type' => 'text'
      )
    ));
  }
} catch(Exception $e) {
  $error = true;
  $messages[] = 'Failed to create draft_dictionary table. ' . $e->getMessage(); 
}

// Setup draft_vocabulary table
// Only used internally for publishing
$draftVocabulary = $database->table('draft_vocabulary');
if (!$draftVocabulary->where('dictionary_id', 'LIKE', '%')->first()) { // Try selecting a row to check if table exists
  try {
    $database->createTable('draft_vocabulary', array(
      'dictionary_id' => array(
        'type'  => 'int'
      ),
      'book_id' => array(
        'type' => 'text'
      ),
      'location' => array(
        'type' => 'text'
      )
    ));
  } catch(Exception $e) {
    $error = true;
    $messages[] = 'Failed to create draft_vocabulary table. ' . $e->getMessage();
  }
}

// Setup dictionary table
$dictionary = $database->table('dictionary');
if (!$dictionary->where('dictionary_id', 'LIKE', '%')->first()) { // Try selecting a row to check if table exists
  try {
    $database->createTable('dictionary', array(
      'dictionary_id' => array(
        'type'  => 'id'
      ),
      'word' => array(
        'type' => 'text'
      ),
      'kana' => array(
        'type' => 'text'
      ),
      'english' => array(
        'type' => 'text'
      ),
    ));
  } catch(Exception $e) {
    $error = true;
    $messages[] = 'Failed to create dictionary table. ' . $e->getMessage();
  }
}

// Setup vocabulary table
$vocabulary = $database->table('vocabulary');
if (!$vocabulary->where('book_id', 'LIKE', '%')->first()) { // Try selecting a row to check if table exists
  try {
    $database->createTable('vocabulary', array(
      'dictionary_id' => array(
        'type' => 'int'
      ),
      'book_id' => array(
        'type'  => 'text'
      ),
      'location' => array(
        'type' => 'text'
      )
    ));
  } catch(Exception $e) {
    $error = true;
    $messages[] = 'Failed to create vocabulary table. ' . $e->getMessage();
  }
}

// Setup vocabulary_results table
$vocabulary_results = $database->table('vocabulary_results');
if (!$vocabulary_results->where('user', 'LIKE', '%')->first()) { // Try selecting a row to check if table exists
  try {
    $database->createTable('vocabulary_results', array(
      'dictionary_id' => array(
        'type'  => 'int'
      ),
      'user' => array(
        'type' => 'text'
      ),
      'book_id' => array(
        'type' => 'int'
      ),
      'location' => array(
        'type' => 'int'
      ),
      'count' => array(
        'type' => 'int'
      ),
      'rep_interval' => array(
        'type' => 'int'
      ),
      'ease_factor' => array(
        'type' => 'text'
      ),
      'timestamp' => array(
        'type' => 'timestamp'
      )
    ));
  } catch(Exception $e) {
    $error = true;
    $messages[] = 'Failed to create vocabulary_results table. ' . $e->getMessage();
  }
  
}