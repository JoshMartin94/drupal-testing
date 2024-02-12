/*! JS FILE ADDED FOR THE CUSTOM SLICK GALLERY INTEGRATION. THIS IS ADDED VIA THE HTML.HTML.TWIG TEMPLATE FILE. */

$(document).ready(function(){
  $('.slider-for').slick({
	slidesToShow: 1,
	slidesToScroll: 1,
	arrows: false,
	fade: true,
	asNavFor: '.slider-nav'
  });
  $('.slider-nav').slick({
	slidesToShow: 3,
	slidesToScroll: 1,
	asNavFor: '.slider-for',
	centerMode: true,
	focusOnSelect: true
  });
});