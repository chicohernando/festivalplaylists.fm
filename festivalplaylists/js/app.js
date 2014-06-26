jQuery( 'input[type=submit]' ).click(function(e) {
  e.preventDefault();
  jQuery( ".row:hidden:first" ).fadeIn( "slow" );
});