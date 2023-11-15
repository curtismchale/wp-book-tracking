jQuery(document).ready(function($) {

  var copy_button = $('#wpbt_copy_button');
  var book_notes = $('.book-notes');


  $(document).on('click touchstart', "#wpbt_copy_button", function(e){

    // note .text() just grabs the text witohut the wrapping p tags
    var notes = $(book_notes).find( 'code' ).text();

    navigator.clipboard.writeText(notes).then(() => {
      $(copy_button).html('Copied');
        setTimeout(function() {
          $(copy_button).html('Copy Notes');
        }, 5000 );
    });

  });

});
