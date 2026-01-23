import '../css/app.scss'
import './fontawesome'

const $ = require('jquery')
const bootstrap = require('bootstrap')

// attach jquery to window
window.$ = $

// register some basic usability functionality
$(document)
  .ready(() => {
    // give instant feedback on form submission
    $('form')
      .on('submit', () => {
        const $form = $(this)
        const $buttons = $('.btn', $form)
        if (!$buttons.hasClass('no-disable')) {
          $buttons.addClass('disabled')
        }
      })

    $('.toggle-visibility').on('click', function (e) {
      e.preventDefault()

      const target = $(this).attr('data-target')
      const $target = $(target)
      if ($target.hasClass('d-none')) {
        $target.removeClass('d-none')
      } else {
        $target.addClass('d-none')
      }
    })

    const setVisibilityChallenge = (challenge) => {
      $('.challenge-hide').addClass('d-none')
      $('.' + challenge + '-show').removeClass('d-none')
    }
    const $challenge = $('.challenge-choice')
    $challenge.each(function () {
      setVisibilityChallenge($(this).val())
    })
    $challenge.on('change', function () {
      setVisibilityChallenge($(this).val())
    })

    const setVisibilityMultiSession = (multiSession) => {
      if (multiSession) {
        $('.multi-session-show').removeClass('d-none')
      } else {
        $('.multi-session-show').addClass('d-none')
      }
    }
    const $multiSession = $('.multi-session-checkbox')
    $multiSession.each(function () {
      setVisibilityMultiSession($(this).is(':checked'))
    })
    $multiSession.on('change', function () {
      setVisibilityMultiSession($(this).is(':checked'))
    })

    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    const collapseElementList = [].slice.call(document.querySelectorAll('.collapse'))
    collapseElementList.map(function (collapseEl) {
      return new bootstrap.Collapse(collapseEl, { toggle: false })
    })

    document.querySelectorAll('a.smooth-scroll').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault()
        const targetElement = document.querySelector(this.getAttribute('href'))

        targetElement.scrollIntoView({
          behavior: 'smooth'
        })

        if (targetElement.hasAttribute('data-bs-toggle') && targetElement.getAttribute('aria-expanded') === 'false') {
          targetElement.click()

          window.setTimeout(() => {
            targetElement.scrollIntoView({
              behavior: 'smooth'
            })
          }, 100)
        }
      })
    })

    // force reload on user browser button navigation
    $(window)
      .on('popstate', () => {
        window.location.reload(true)
      })
  })
