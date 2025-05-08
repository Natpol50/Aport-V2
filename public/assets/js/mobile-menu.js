/**
 * mobile-menu.js - Mobile menu functionality
 */

document.addEventListener('DOMContentLoaded', function() {
  // Initialize the mobile menu
  initMobileMenu();
});

/**
 * Initialize mobile menu behavior with improved transitions
 */
function initMobileMenu() {
  const menuToggle = document.getElementById('mobile-menu-toggle');
  const mobileMenu = document.getElementById('mobile-menu');
  
  // Exit if elements don't exist
  if (!menuToggle || !mobileMenu) {
    console.warn('Mobile menu elements not found');
    return;
  }
  
  // Initialize menu state
  let isMenuOpen = false;
  
  // Handle toggle button click
  menuToggle.addEventListener('click', function(e) {
    e.stopPropagation(); // Prevent event bubbling
    toggleMenu();
  });
  
  // Function to toggle menu state
  function toggleMenu() {
    isMenuOpen = !isMenuOpen;
    
    // Update aria-expanded for accessibility
    menuToggle.setAttribute('aria-expanded', isMenuOpen.toString());
    
    if (isMenuOpen) {
      // Open menu
      mobileMenu.classList.remove('hidden');
      
      // Use requestAnimationFrame to ensure DOM update before animation
      requestAnimationFrame(() => {
        // Calculate the proper height for the menu based on its content
        const contentHeight = mobileMenu.scrollHeight;
        mobileMenu.style.maxHeight = `${contentHeight}px`;
        mobileMenu.classList.add('visible');
      });
    } else {
      // Close menu with animation
      mobileMenu.style.maxHeight = '0';
      mobileMenu.classList.remove('visible');
      
      // Wait for transition to complete before hiding
      setTimeout(() => {
        if (!isMenuOpen) { // Check again in case state changed
          mobileMenu.classList.add('hidden');
        }
      }, 300); // Match transition duration
    }
  }
  
  // Close menu when clicking outside
  document.addEventListener('click', function(event) {
    if (isMenuOpen && 
        !mobileMenu.contains(event.target) && 
        !menuToggle.contains(event.target)) {
      toggleMenu();
    }
  });
  
  // Handle menu links for improved UX
  const menuLinks = mobileMenu.querySelectorAll('a');
  menuLinks.forEach(link => {
    link.addEventListener('click', () => {
      // Small delay to allow for the click to register
      setTimeout(() => {
        if (isMenuOpen) {
          toggleMenu();
        }
      }, 50);
    });
  });
  
  // Handle window resize - adjust menu height if open
  window.addEventListener('resize', debounce(() => {
    if (isMenuOpen) {
      const contentHeight = mobileMenu.scrollHeight;
      mobileMenu.style.maxHeight = `${contentHeight}px`;
    }
  }, 100));
}

/**
 * Debounce function to limit how often a function is called
 * 
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @return {Function} - Debounced function
 */
function debounce(func, wait) {
  let timeout;
  
  return function() {
    const context = this;
    const args = arguments;
    
    clearTimeout(timeout);
    
    timeout = setTimeout(() => {
      func.apply(context, args);
    }, wait);
  };
}

/**
 * Initialize mobile device warning
 */
function initMobileWarning() {
  const mobileWarning = document.getElementById('mobileWarning');
  const dismissButton = document.getElementById('dismissMobileWarning');
  
  if (!mobileWarning || !dismissButton) return;
  
  // Check if the warning has been dismissed before
  const hasSeenWarning = localStorage.getItem('hasSeenMobileWarning') === 'true';
  
  // Only show warning on mobile devices that haven't seen it
  if (!hasSeenWarning && window.innerWidth < 768) {
    mobileWarning.classList.remove('hidden');
  }
  
  // Set up dismiss button
  dismissButton.addEventListener('click', function() {
    mobileWarning.classList.add('hidden');
    localStorage.setItem('hasSeenMobileWarning', 'true');
  });
}

// Call this function in your document ready handler
document.addEventListener('DOMContentLoaded', function() {
  // ... other initializations
  initMobileWarning();
});