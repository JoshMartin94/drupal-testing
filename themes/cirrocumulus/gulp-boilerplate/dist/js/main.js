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

const increaseButton = document.querySelector('.increase-button');
const decreaseButton = document.querySelector('.decrease-button');
const score = document.querySelector('.score');

let scoreCount = 0;

function increment() {
  scoreCount += 5;
  document.querySelector('.score').innerHTML = scoreCount;
}

function decrement() {
  scoreCount--;
  document.querySelector('.score').innerHTML = scoreCount;
}

increaseButton.addEventListener('click', increment);
decreaseButton.addEventListener('click', decrement);
