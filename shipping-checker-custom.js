/**
 * WooCommerce Shipping Checker Custom HTML Integration
 *
 * This script detects if the custom HTML structure for shipping checking exists on the page
 * and initializes the WooCommerce shipping checking functionality on it.
 */

jQuery(document).ready(function ($) {
  // Detect if the custom HTML structure exists on the page
  if (
    !$('#zipInput').length ||
    !$('.zip-btn').length ||
    !$('#resultMessage').length
  ) {
    return // Exit early if the custom HTML structure is not found
  }

  // Add hidden fields
  const $zipInputField = $('.zip-input-field')
  const $resultMessage = $('#resultMessage')

  // Setup hidden fields
  $zipInputField.append(
    $('<input>', {
      type: 'hidden',
      id: 'zip_code_security',
      name: 'zip_code_security',
      value: ''
    }),
    $('<input>', {
      type: 'hidden',
      id: 'country_input',
      value: 'US'
    }),
    $('<input>', {
      type: 'hidden',
      id: 'state_input',
      value: ''
    })
  )

  // Create restriction element
  const $restrictionElement = $('<div>', {
    id: 'shipping_restrictions',
    class: 'shipping-restrictions'
  }).css('display', 'none')

  // Append restriction notice after the result message
  $resultMessage.after($restrictionElement)

  // Fetch the nonce value
  $.ajax({
    url: wc_shipping_checker.ajax_url,
    type: 'POST',
    data: { action: 'get_shipping_nonce' },
    success: function (response) {
      if (response.success) {
        $('#zip_code_security').val(response.data.nonce)
      }
    }
  })

  /**
   * Display error message
   */
  function showError (message) {
    $resultMessage.html(message).css('color', '#d83131')
  }

  /**
   * Display California restrictions if applicable
   */
  function showCaliforniaRestrictions () {
    const restrictionHtml = `
      <h3>RESTRICTED SHIPPING & STATE REGULATIONS:</h3>
      <p>*ATTENTION CALIFORNIA CUSTOMERS: CALIFORNIA SHIPPING IS ONLY AVAILABLE FOR:</p>
      <p>
        <a href="https://vapesocietysupplies.com/product-tag/tobacco-flavor/" target="_blank">TOBACCO FLAVORS</a> | 
        <a href="https://vapesocietysupplies.com/collections/devices/" target="_blank">VAPE HARDWARE</a>
      </p>
    `

    $('#shipping_restrictions').html(restrictionHtml).css({
      color: '#d83131',
      'margin-top': '15px',
      display: 'block'
    })
    
    // For California, clear the error message since we're showing the specific restriction instead
    $resultMessage.html('').css('color', '')
  }

  /**
   * Check shipping with state information
   */
  function checkShippingWithState (zip_code, country, state) {
    const security = $('#zip_code_security').val()

    // Hide any previous restrictions
    $('#shipping_restrictions').hide()

    // Show California restrictions if applicable
    if (state === 'CA') {
      showCaliforniaRestrictions()
      return // Exit early for California - no need to check further
    }

    $resultMessage.html('Checking availability...')

    // Make AJAX request to validate shipping
    $.ajax({
      url: wc_shipping_checker.ajax_url,
      type: 'POST',
      data: {
        action: 'validate_shipping_zip_code',
        zip_code: zip_code,
        country: country,
        state: state,
        security: security
      },
      success: function (response) {
        if (
          response.success &&
          response.data.methods &&
          response.data.methods.length > 0
        ) {
          $resultMessage
            .html('Great news! We do ship to your Zip Code!')
            .css('color', 'green')
        } else {
          showError("We are sorry. We currently don't serve your Zip Code.")
        }
      },
      error: function () {
        showError('An error occurred. Please try again.')
      }
    })
  }

  /**
   * Handler for ZIP code checking
   */
  window.checkZip = function () {
    const zip_code = $('#zipInput').val()
    const security = $('#zip_code_security').val()

    // Hide any previous restrictions
    $('#shipping_restrictions').hide()

    if (!zip_code) {
      showError('Please enter a ZIP code.')
      return
    }

    $resultMessage.html('Checking availability...')

    // First get the state from ZIP code
    $.ajax({
      url: wc_shipping_checker.ajax_url,
      type: 'POST',
      data: {
        action: 'get_state_from_zip',
        zip_code: zip_code,
        security: security
      },
      success: function (response) {
        if (response.success) {
          const state_id = response.data.state_id
          $('#state_input').val(state_id)

          // Check shipping availability with state and ZIP
          checkShippingWithState(zip_code, 'US', state_id)
        } else {
          showError(response.data)
        }
      },
      error: function () {
        showError('Error retrieving location data. Please try again.')
      }
    })
  }

  // Add enter key press handler to the zip input field
  $('#zipInput').on('keypress', function (e) {
    if (e.which === 13) {
      // Enter key
      e.preventDefault()
      checkZip()
    }
  })
})