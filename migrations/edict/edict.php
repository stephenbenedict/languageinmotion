<?php

$database = new Database(array(
  'type'     => c::get('dbType'),
  'host'     => c::get('dbHost'),
  'database' => c::get('dbName'),
  'user'     => c::get('dbUser'),
  'password' => c::get('dbPassword')
));

// Setup edict table
$edict = $database->table('edict');
if (!$edict->where('dictionary_id', 'LIKE', '%')->first()) { // Try selecting a row to check if table exists
  $database->createTable('edict', array(
    'id' => array(
      'type'  => 'id'
    ),
    'kanji' => array(
      'type' => 'text',
    ),
    'kana' => array(
      'type' => 'text',
    ),
    'english' => array(
      'type' => 'text',
    )
  ));
}

$xml = xml::parse($page->batch_5());

foreach ($xml['entry'] as $key => $entry) {
 
  $pairs = array();

  $kanji = array();
  $kana = array();
  $english = array();
  $english[0] = '';
  
  // Check if entry has kanji
  if (key_exists('k_ele', $entry)) {

    // Some entries have multiple kanji readings like:
    //
    // <k_ele>
    //   <keb>明白</keb>
    //   <ke_inf>&ateji;</ke_inf>
    // </k_ele>
    // <k_ele>
    //   <keb>偸閑</keb>
    //   <ke_inf>&ateji;</ke_inf>
    // </k_ele>
    //
    // but when checking, pluck only the "keb" column
    // because some entries may have only one kanji reading
    // but have 2 array elements like:
    //
    // <k_ele>
    //   <keb>Ｔバック</keb>
    //   <ke_pri>spec1</ke_pri>
    // </k_ele> 
    //
    // This procedure is used for kana and English values as well.

    if (count(array_column($entry['k_ele'], 'keb')) > 1) {
      foreach ($entry['k_ele'] as $reading) {
        $kanji[] = $reading['keb'];
      }
    } else {
      $kanji[] = $entry['k_ele']['keb'];
    }

  }

  // Get kana
  if (count(array_column($entry['r_ele'], 'reb')) > 1) {
    foreach ($entry['r_ele'] as $reading) {
      
      // If the kana reading only applies to a subset of the 
      // kanji elements there will be a <re_restr> field. E.g.:
      //
      // <reb>シーディープレーヤー</reb>
      // <re_restr>ＣＤプレーヤー</re_restr>
      //
      // In this case, we want to include that kanji
      // with the kana to pair them up later.
      if (key_exists('re_restr', $reading)) {
        $kana[] = array('reb' => $reading['reb'], 're_restr' => $reading['re_restr']);
      } elseif (key_exists('re_nokanji', $reading)) {
        $kana[] = array('reb' => $reading['reb'], 're_nokanji' => true);
      } else {
        $kana[] = $reading['reb'];
      }
    }
  } else {
     $kana[] = $entry['r_ele']['reb'];
  }

  // Get English
  $senses = $entry['sense'];
  if (count(array_column($senses, 'gloss')) > 1) {
    $senseNumber = 0;

    foreach ($senses as $number => $sense) {

      // Some senses belong with only certain readings:
      //
      // Sense 1:
      //   Kanji: 匹
      //   Kana: ひき
      //   Sense: Counter for small animals
      // Sense 2:
      //   Kanji: 匹
      //   Kana: き
      //   Sense: Counter for horses
      // 
      // For these we need to include the whole sense
      // including reading so that we can pair them up later
      if (key_exists('stagr', $sense) || key_exists('stagk', $sense)) {
        $english[1][$number + 1] = $sense;
      } else {
        $senseNumber += 1;

        // Figure out how many senses there are that should be 
        // strung together and therefore need separating numbers like "(1)"
        $unifiedSenses = array();
        foreach ($senses as $senseA) {
          if (!key_exists('stagr', $senseA) && !key_exists('stagk', $senseA)) {
            $unifiedSenses[] = $senseA;
          }
        }

        if (count($unifiedSenses) > 1) {
           $english[0] = $english[0] . " ({$senseNumber}) "; // add the separating number
        } else {
           $english[0] = $english[0];
        }
       
        $glosses = $sense['gloss'];

        if (count($glosses) > 1) {
          foreach ($glosses as $gloss) {
            $english[0] = $english[0] . $gloss . ' ･ ';
          }
          $english[0] = str::substr($english[0], 0, -3); // remove trailing " ･ "
        } else {
          $english[0] = $english[0] . $sense['gloss'];
        }
      } 
    }
  } else {
    $glosses = $senses['gloss'];
    if (count($glosses) > 1) {
      foreach ($glosses as $gloss) {
        $english[0] = $english[0] . $gloss .  ' ･ ' ;
      }
      $english[0] = str::substr($english[0], 0, -3); // remove trailing " ･ "
    } else {
      $english[0] = $glosses;
    }
  }

  foreach ($kana as $kanaReading) {
    if ($kanji) {
      if (is_array($kanaReading) && key_exists('re_restr', $kanaReading)) {
        insert($edict, $kanaReading['re_restr'], $kanaReading['reb'], $english[0]);
      } elseif (is_array($kanaReading) && key_exists('re_nokanji', $kanaReading)) {
        insert($edict, '', $kanaReading['reb'], $english[0]);
      } else {
        
        foreach ($kanji as $kanjiReading) {

          if (!empty($english[1])) {
            foreach ($english[1] as $restrictedSense) {
              if (key_exists('stagk', $restrictedSense)) {
                if ($restrictedSense['stagk'] == $kanjiReading) {
                  $glosses = $restrictedSense['gloss'];
                 
                  if (count($glosses) > 1) {
                    $restrictedGloss = '';
                    foreach ($glosses as $gloss) {
                      $restrictedGloss = $restrictedGloss . $gloss . ' ･ ';
                    }
                    $restrictedGloss = str::substr($restrictedGloss, 0, -3); // remove trailing " ･ "
                  } else {
                    $restrictedGloss = $restrictedGloss . $gloss;
                  }

                  insert($edict, $restrictedSense['stagk'], $kanaReading, $restrictedGloss);
                }
              }
            }
          }
          insert($edict, $kanjiReading, $kanaReading, $english[0]);
        }

        if (!empty($english[1])) {
          foreach ($english[1] as $restrictedSense) {
            if (key_exists('stagr', $restrictedSense)) {
              if ($restrictedSense['stagr'] == $kanaReading) {
                insert($edict, $kanjiReading, $restrictedSense['stagr'], $restrictedSense['gloss']);
              }
            }
          }
        }
        
      }
    } else {
      insert($edict, '', $kanaReading, $english[0]);
    }
  }

}

function insert($table, $kanji, $kana, $english) {
  $table->insert(array(
    'id'      => null,
    'kanji'   => $kanji,
    'kana'    => $kana,
    'english' => $english
  ));

  // echo 'kanji: ' . $kanji;
  // echo '<br>';
  // echo 'kana: ' . $kana;
  // echo '<br>';
  // echo 'english: ' . $english;
  // echo '<hr>';
}