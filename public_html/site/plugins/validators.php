<?php

// Validate username is unique
v::$validators['uniqueUsername'] = function($value) {
  if (in_array(str::lower($value), site()->users()->toArray())) {
    return false;
  } else {
    return true;
  }
};
