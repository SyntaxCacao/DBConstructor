$('a').click(function(event) {
  if ($(this).attr('href') == '#') {
    event.preventDefault();
  }
});
