<?php

return function($site, $pages, $page) {

  $user = site()->user();
  if (!$user || !$user->isAdmin()) {
    go('error');
  }

  $bookId = $page->parent()->uid();

  ini_set('max_execution_time', 3600); // 3600 seconds = 60 minutes; Loading long books can take a while.

  $annotated = $page->parent()->annotated()->yaml();
  $data = array();
  foreach ($annotated as $chapter) {
    foreach ($chapter['text_array'] as $segment) {
      if (count($segment) > 1) { // More than 1 item means it has a definition
        $data[] = new Collection(array('word' => $segment[0], 'kana' => $segment[1], 'english' => $segment[2]));
      }
    }
  }

  $deck = new Collection($data);

  $missingDefinitions = 0;
  foreach ($deck as $card) {
    if ($card->english() == '') {
      $missingDefinitions += 1;
    }
  }

  return compact('deck', 'missingDefinitions');
};
