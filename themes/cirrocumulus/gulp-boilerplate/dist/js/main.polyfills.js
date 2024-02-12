/*! project-name v0.0.1 | (c) 2023 YOUR NAME | MIT License | http://link-to-your-git-repo.com */
// This file was Changed by C&C - VEN

(function ($, Drupal) {

  'use strict';
  
  var $hamburger = $(".hamburger");
  $hamburger.on("click", (function(e) {
    $hamburger.toggleClass("is-active");
    $("body").toggleClass("mobile-menu-open");
  }));

})(jQuery, Drupal);

/**
 * Element.matches() polyfill (simple version)
 * https://developer.mozilla.org/en-US/docs/Web/API/Element/matches#Polyfill
 */
if (!Element.prototype.matches) {
	Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
}