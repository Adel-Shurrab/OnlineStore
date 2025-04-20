$(document).ready(function () {
  $('.nav-item.dropdown').on('click', function (e) {
    e.stopPropagation(); // Prevent event bubbling up
    $(this).find('.dropdown-menu-category').stop(true, true).slideToggle(300); // Toggle dropdown menu
  });

  $('.filter-brand.dropdown').on('click', function (e) {
    e.stopPropagation(); // Prevent event bubbling up
    $(this).find('.dropdown-menu-filter-brand').stop(true, true).slideToggle(300); // Toggle dropdown menu
  });

  $('.filter-deals.dropdown').on('click', function (e) {
    e.stopPropagation(); // Prevent event bubbling up
    $(this).find('.dropdown-menu-filter-deals').stop(true, true).slideToggle(300); // Toggle dropdown menu
  });

  // For filter options
  $('#sortOptions').on('change', function () {
    $(this).closest('form').submit();
  });

});

function toggleDropdown(dropdownId) {
  // Close any open dropdowns except the clicked one
  document.querySelectorAll('.dropdown-content').forEach(dropdown => {
    if (dropdown.id !== dropdownId) {
      dropdown.classList.add('hide');
    }
  });
  // Toggle the clicked dropdown
  const dropdown = document.getElementById(dropdownId);
  dropdown.classList.toggle('hide');
}

// Close dropdown if clicked outside
document.addEventListener('click', function (event) {
  const isClickInsideProfile = document.querySelector('.profile-pic')?.contains(event.target);
  const isClickInsideLang = document.querySelector('.lang-img')?.contains(event.target);
  const isDropdown = event.target.closest('.dropdown-content');

  if (!isClickInsideProfile && !isClickInsideLang && !isDropdown) {
    document.querySelectorAll('.dropdown-content').forEach(dropdown => {
      dropdown.classList.add('hide');
    });
  }
});


$(function () {
  $("#inputBirthday").datepicker({
    dateFormat: "mm/dd/yy",
    changeMonth: true,
    changeYear: true,
    yearRange: "1900:2024"
  });
});

$(document).ready(function () {
  // Update icons based on initial state
  $('.accordion-button').each(function () {
    const icon = $(this).find('i');
    if ($(this).hasClass('collapsed')) {
      icon.removeClass('fa-minus').addClass('fa-plus');
    } else {
      icon.removeClass('fa-plus').addClass('fa-minus');
    }
  });

  // Change icons when a section is shown
  $('#myAccordion').on('show.bs.collapse', function (e) {
    const button = $(e.target).prev().find('.accordion-button');
    button.find('i').removeClass('fa-plus').addClass('fa-minus');
  });

  // Change icons when a section is hidden
  $('#myAccordion').on('hide.bs.collapse', function (e) {
    const button = $(e.target).prev().find('.accordion-button');
    button.find('i').removeClass('fa-minus').addClass('fa-plus');
  });
});

// edit_profile.php
$(document).ready(function () {
  // Initialize the Carousel
  $('#myCarousel-2').carousel({
    interval: 3000, // 3 seconds between slides
    ride: 'carousel'
  });

  // Tabs Activation
  $('#myTab a').on('click', function (e) {
    e.preventDefault();
    $(this).tab('show');
  });
});

// Go to Reviews tab items.php
document.getElementById('goToReviews').addEventListener('click', function () {
  var reviewsTab = new bootstrap.Tab(document.getElementById('reviews-tab'));
  reviewsTab.show(); // This will show the Reviews tab
});

// JavaScript function to toggle payment methods
function togglePaymentMethod(selectedMethod) {
  if (selectedMethod === 'credit') {
    // Enable Credit Card fields
    document.getElementById('card_number').disabled = false;
    document.getElementById('card_name').disabled = false;
    document.getElementById('expiry_date').disabled = false;
    document.getElementById('cvv').disabled = false;

    // Disable PayPal field
    document.getElementById('paypal_email').disabled = true;
  } else if (selectedMethod === 'paypal') {
    // Enable PayPal field
    document.getElementById('paypal_email').disabled = false;

    // Disable Credit Card fields
    document.getElementById('card_number').disabled = true;
    document.getElementById('card_name').disabled = true;
    document.getElementById('expiry_date').disabled = true;
    document.getElementById('cvv').disabled = true;
  }
}

// Define the login callback in login.php
function loginCallback(response) {
  const userObject = jwt_decode(response.credential);
  console.log(userObject); // You can check what data is returned
  // Perform your actions here, like sending the user data to your backend for session management
}