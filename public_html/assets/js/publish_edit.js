$(document).ready(function() {
  
// Looking up an entry
$('body').on('submit', '.lookup-form', function(e) {
  e.preventDefault();
  var data = $(this).serialize();

  $.post(window.location.pathname + "/lookup", data, function( data ) {
    $('#edict-lookup').replaceWith(data['publishEdictLookupSnippet']);
  });
})

// Clicking a word to copy
$('body').on('click', '.publish .listing .word', function(event) {
  var text = $(this).html();
  window.prompt("Copy to clipboard: Cmd+C, Enter", text);
  $(this).blur();
});

// Clicking a lookup entry to copy
$('body').on('focus', '#edict-lookup .listing input', function(event) {
  var text = $(this).val();
  window.prompt("Copy to clipboard: Cmd+C, Enter", text);
  $(this).blur();
});

// Toggle missing definition filter
$('body').on('click', '#missing-definitions-filter-toggle', function(event) {
  event.preventDefault();
  $('div.edit-list ul li').toggle();
  $('div.edit-list ul li.missing-definition').toggle();
});

});