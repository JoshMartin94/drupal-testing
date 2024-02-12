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
