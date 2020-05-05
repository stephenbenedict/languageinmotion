$(document).ready(function() { 

var stackLimit = 20;
var stackIndex = 0;

// Clicking book page nav
$('#text nav a').click(function(event) {
  event.preventDefault();

  var pageNumber = $(this).data('page');
  openPageWithAnimation(pageNumber, true);
});

// Clicking card toggle link
// (i.e. "Study" or "Close")
$('#card-toggle').click(function(event) {
  event.preventDefault();
  
  // A new study session so load card data
  if (!$('#card').hasClass('visible')) {
     
    stackIndex += 1;

    $.get(window.location.pathname + "/vocabulary/start", function( data ) {
      loadCard(data['dictionaryId'], data['locations'], data['targetLocation']);
      highlightWordAndCenterWithFurigana(data['targetLocation'], true, false);
      toggleCardAndShowDefinition(false);
    });
  } else {
    toggleCardAndShowDefinition(false);
  }
});

// Clicking "Show Definition" link
$('#card').on('click', 'a.show-definition', function(event) {
  event.preventDefault();
  $('#card .show-definition').removeClass('visible');
  $('#card .definition').addClass('visible');
  $('#text ruby.highlighted rt').css('visibility', 'visible');
})

// Clicking form quality links
$('#card').on('click', 'a.quality', function(event) {
  event.preventDefault();

  //  Update the hidden input with the clicked quality
  var quality = $(this).data('quality');
  var form = $(this).parents('form');
  form.children('.quality').val(quality);

  if ($(this).html() == 'Know It') {
    form.children('.know-it').val('true');
  }

  var data = form.serialize();

  if ($('#card').hasClass('studying')) {
    stackIndex += 1;

    if (stackIndex > stackLimit) {
      data = data + '&reached_stack_limit=true&stack_limit=' + stackLimit;
    }
  }

  $.post(window.location.pathname + "/vocabulary/update", data, function( data ) {

    if (data['error']) {
      for (var i = 0; i < data['messages'].length; i++) {
        $('#toolbar .notif').append('<div class="error">' + data['messages'][i] + '</div>');
      };
    } else {

      // Update the fluency value for all words that have the dictionary ID of the word submitted
      $("#text ruby[data-dictionary-id='" + data['dictionaryId'] + "']").attr('data-fluency', data['fluency']);

      // Hide the furigana of the word submitted
      $('#text ruby.highlighted rt').css('visibility', 'hidden');

      // If the card is open as part of a study session
      // then check some things and show the next card
      if ($('#card').hasClass('studying')) {
        if (data['completedVocabulary'] == true) {
          $('#toolbar .notif').append('<div>' + data['message'] + '</div>').delay(1200).fadeOut('400');
          toggleCardAndShowDefinition(false);
          return true;
        }

        if (data['increaseStackLimit'] == true) {
          stackLimit += stackLimit;
        }

        loadCard(data['nextDictionaryId'], data['nextLocations'], data['nextTargetLocation']);
        highlightWordAndCenterWithFurigana(data['nextTargetLocation'], true, false);
      } else { // otherwise close the card
        toggleCardAndShowDefinition(false);
      }
    }

  });
})

// Clicking vocabulary word in book text
$("#text").on("click", "ruby", function (event) {
  var dictionaryId = $(this).data('dictionary-id');
  var locationsString = $(this).data('locations').toString();

  var locations = [];
  if (locationsString.indexOf(',') > -1) { // Check if there is a comma, which indicates more than one item
    locations = locationsString.split(',')
  } else {
    locations = [locationsString];
  }

  var targetLocation = $(this).attr('id');

  loadCard(dictionaryId, locations, targetLocation);
  highlightWordAndCenterWithFurigana(targetLocation, false, true);
  toggleCardAndShowDefinition(true);
});

// Clicking vocabulary uses navigation
$('#card').on('click', '.uses a', function(event) {
  event.preventDefault();
  $('#card .uses').addClass('navigating');

  var navSet = $(this).parent();
  var setIndex = $(this).data('location');

  navSet.removeClass('current');
  $("#card .uses div[data-set-index='" + setIndex + "']").addClass('current');
 
  if ($('#card').hasClass('studying')) {
    highlightWordAndCenterWithFurigana($(this).data('location'), true, false);
  } else {
    highlightWordAndCenterWithFurigana($(this).data('location'), true, true);
  }
});

// Clicking bookmarks toggle link
$('#bookmarks-toggle').click(function(event) {
  event.preventDefault();
  toggleBookmarks();
});

// Adding a bookmark
$('#add-bookmark').click(function(event) {
  event.preventDefault();

  // Get the first vocabulary word visible in the window
  var vocabularyWord = $('#text div.current ruby').filter(function() {
    var docViewTop = $(window).scrollTop();
    var docViewBottom = docViewTop + $(window).height();

    var elemTop = $(this).offset().top;
    var elemBottom = elemTop + $(this).height();

    return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
  }).first()

  var currentLocation = vocabularyWord.attr('id');
  
  var data = { add_location: currentLocation };
  $.post(window.location.pathname + "/bookmark/add", data, function(data) {
    if (data['error']) {
      for (var i = 0; i < data['messages'].length; i++) {
        $('#toolbar .errors').append('<div>' + data['messages'][i] + '</div>');
      };
    } else {
      $('#bookmarks').html(data['bookmarksSnippet']);
      loadBookmarkRibbons();
    }
  });
});

// Deleting a bookmark
$('#bookmarks').on('click', '.bookmark .delete', function(event) {
  event.preventDefault();

  if (window.confirm("Are you sure you want to delete this bookmark?")) {
    var bookmark = $(this).parents('.bookmark');
    var data = { delete_location: bookmark.data('location') };
    $.post(window.location.pathname + "/bookmark/delete", data, function(data) {
      if (data['error']) {
        for (var i = 0; i < data['messages'].length; i++) {
          $('#toolbar .errors').append('<div>' + data['messages'][i] + '</div>');
        };
      } else {
        $('#bookmarks').html(data['bookmarksSnippet']);
        loadBookmarkRibbons();
      }
    });
  }
});

// Opening a bookmark
$('#bookmarks').on('click', '.bookmark .open', function(event) {
  event.preventDefault();
  scrollToLocation($(this).parents('li').data('location'));
  toggleBookmarks();
});

// Clicking fluency toggle
$('#fluency-toggle').click(function(event) {
  event.preventDefault();
  $('#text').toggleClass('fluency');
  $(this).toggleClass('active');
});

function highlightWordAndCenterWithFurigana(location, center, furigana) {
  $('#text').addClass('highlighted');
  // Hide furigana on previously highlighted word
  $('#text ruby.highlighted rt').css('visibility', 'hidden');
  // Remove "highlighted" class from previously highlighted word
  $('#text ruby').removeClass('highlighted');
  // Add "highlighted" class to new word
  $('#' + location).addClass('highlighted');
  
  if (center === true) {
    scrollToLocation(location);
  }

  if (furigana === true) {
    $('#' + location + ' rt').css('visibility', 'visible');
  }

  redrawBookText();
}

function toggleBookmarks() {
  if ($('#bookmarks').hasClass('visible')) {
    $('body').removeClass('noscroll');
    $('#bookmarks').removeClass('visible');
    $('#bookmarks-toggle').html('Bmks.');
    $('#add-bookmark').show();
    $('#card-toggle').show();
    $('#fluency-toggle').show();
    $('#bookmarks-toggle').removeClass('single');
  } else {
    $('body').addClass('noscroll');
    $('#bookmarks').addClass('visible');
    $('#bookmarks').scrollTop(0);
    $('#bookmarks-toggle').html("Close");
    $('#add-bookmark').hide();
    $('#card-toggle').hide();
    $('#fluency-toggle').hide();
    $('#bookmarks-toggle').addClass('single');
  }
}

function loadCard(dictionaryId, locations, targetLocation) {
  
  // Clear any existing data
  $('#card .uses').html('');
  $('#card div.english').html('');
  $("#card form input[name='dictionary_id']").val('');
  $("#card form input[name='location']").val('');
  $("#card form input[name='know-it']").val('false');

  // Reset definition to be hidden
  $('#card div.definition').removeClass('visible')
  $('#card a.show-definition').addClass('visible');

  // Create the "Uses" nav bar if there is more than one use of the word
  if (locations.length > 1) {
    $('#card .uses').addClass('visible');
    for (var i = 0; i < locations.length; i++) {
      if (i == 0) { // If first
        $('#card .uses').append("<div class='wrapper' data-set-index='" + locations[i] + "'><a href='#' data-location='" + locations[i + 1] + "' class='next'>Next</a><span>Uses</span></div>");
      } else if (i == locations.length - 1) { // If last
        $('#card .uses').append("<div class='wrapper' data-set-index='" + locations[i] + "'><a href='#' data-location='" + locations[i - 1] + "' class='prev'>Prev</a><span>Uses</span></div>");
      } else { // If middle
        $('#card .uses').append("<div class='wrapper' data-set-index='" + locations[i] + "'><a href='#' data-location='" + locations[i - 1] + "' class='prev'>Prev</a><span>Uses</span><a href='#' data-location='" + locations[i + 1] + "' class='next'>Next</a></div>");
      }
    };

    $("#card .uses div[data-set-index='" + targetLocation + "']").addClass('current');
  }

  var english = $('#' + targetLocation).data('english');

  // Fill in English, dictionary ID, and location
  $('#card div.dictionary div.definition div.english').html(english);
  $("#card div.dictionary div.definition form input[name='dictionary_id']").val(dictionaryId);
  $("#card div.dictionary div.definition form input[name='location']").val(targetLocation);
}

function toggleCardAndShowDefinition(showDefinition) {

  if ($('#card').hasClass('visible')) {
    $('#card').removeClass('visible');
    $('#card').removeClass('studying');
    $('#card-toggle').html('Study');
    $('#text').removeClass('highlighted');
    $('#text ruby.highlighted rt').css('visibility', 'hidden');
    $('#text ruby.highlighted').removeClass('highlighted');
    $('#add-bookmark').show();
    $('#bookmarks-toggle').show();
    $('#fluency-toggle').show();
    $('#card-toggle').removeClass('single');
    $('#card div.dictionary div.definition').removeClass('visible');
    stackLimit = 20;

    // Closing after navigating uses requires
    // a more stringent redrawing
    if ($('#card .uses').hasClass('navigating')) {
      $('#text').hide().show(0);
    } else {
      redrawBookText();
    }

    $('#card .uses').removeClass('visible navigating');
  } else {

    if (showDefinition == true) {
      $('#card div.dictionary div.definition').addClass('visible');
      $('#card div.dictionary a.show-definition').removeClass('visible');
    } else {
      $('#card div.dictionary a.show-definition').addClass('visible');
      $('#text ruby.highlighted rt').css('visible', 'hidden');
      $('#card').addClass('studying'); // Used to determine if card should be automatically closed after submission 
    }

    $('#card').addClass('visible');
    $('#card-toggle').html("Close");
    $('#add-bookmark').hide();
    $('#bookmarks-toggle').hide();
    $('#fluency-toggle').hide();
    $('#card-toggle').addClass('single');
  }
}

// Toggle card if clicking outside of it
$('#card div.overlay').click(function(event) {
  toggleCardAndShowDefinition(false);
});

function scrollToLocation(location) {
  var point = $('#' + location);

  // Location could be on another page
  // so check and open page if needed.
  var firstWord = $('#text div.current ruby:first-child').first().attr('id');
  var lastWord = $('#text div.current ruby:last-child').last().attr('id');
  if (location < parseInt(firstWord) || location > parseInt(lastWord)) {
    $('#text div').each(function(index) {
      var first = $("#text div[data-page='" + index + "'] ruby:first-child").first().attr('id');
      var last = $("#text div[data-page='" + index + "'] ruby:last-child").last().attr('id');
      if (location >= parseInt(first) && location <= parseInt(last)) {
        openPageWithAnimation(index, false);
      }
    });
  }

  window.scrollTo(0, point.offset().top - (window.innerHeight / 3));
}

function openPageWithAnimation(number, animate) {
  $('#text div.current').removeClass('current');
  $("#text div[data-page='" + number + "']").addClass('current');
  $('#text nav a').removeClass('current');
  $("#text nav a[data-page='" + number + "']").addClass('current');

  if (animate == true) {
    window.scrollTo(0, $('#text nav').offset().top); // Keep nav stationary when text changes height
    $("html, body").delay(1000).animate({scrollTop: $('#text').offset().top - 11.52}, 500);
  }
}

function scrollToChapter(number) {
  var chapterHeading = $('#text h2').filter(function() {
    return $(this).data('number') == number;
  }).first()
  window.scrollTo(0, chapterHeading.offset().top - 25.327);
}

function addBottomPaddingToBook() {
  var bottomPadding = window.innerHeight / 2;
  $('#text').css('padding-bottom', bottomPadding);
  $('#text .owari').css('margin-top', bottomPadding / 2);
}

function loadBookmarkRibbons() {
  $('#text ruby.bookmark rb').children('span').remove();
  $('#bookmarks .bookmark').each(function(index) {
    var word = $('#' + $(this).data('location'));
    if (!word.children('span').length) { // don't add if it already has a bookmark
      $('#' + $(this).data('location')).addClass('bookmark');
      $('#' + $(this).data('location') + ' rb').append("<span class='ribbon'></span>");
    }
  });

  // Redrawing the text using redrawBookText()
  // does not prevent text shifting when
  // adding bookmarks. So, it takes more overhead,
  // but hide and show whole text container
  // to force redrawing.
  $('#text').hide().show(0);
}

function loadFluency() {
  $.get(window.location.pathname + "/vocabulary/fluency", function( data ) {
    for (var i = 0; i < data['vocabulary'].length; i++) {
      $("#text ruby[data-dictionary-id='" + data['vocabulary'][i]['dictionaryId'] + "']").attr('data-fluency', data['vocabulary'][i]['fluency']);
    };
  });
}

function redrawBookText() {
  // Force webkit browsers to redraw
  // to prevent words from shifting and
  // getting cut off. The 'redraw-fix' class
  // changes the text color 1% in brightness.
  // Switching that on and off
  // is enough to force a redraw.
  $('#text').addClass('redraw-fix');
  setTimeout(function() {
    $('#text').removeClass('redraw-fix');
  }, 0);
}

addBottomPaddingToBook();
loadBookmarkRibbons();
loadFluency();

});
