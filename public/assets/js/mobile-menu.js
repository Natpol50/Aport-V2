/**
 * mobile-menu.js - Enhanced mobile menu functionality
 * 
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
    if (!menuToggle || !mobileMenu) return;
    
    // Initialize menu state
    let isMenuOpen = false;
    
    // Handle toggle button click
    menuToggle.addEventListener('click', function() {
      // Toggle state
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
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
      if (isMenuOpen && 
          !mobileMenu.contains(event.target) && 
          !menuToggle.contains(event.target)) {
        // Trigger the same close logic
        isMenuOpen = false;
        menuToggle.setAttribute('aria-expanded', 'false');
        mobileMenu.style.maxHeight = '0';
        mobileMenu.classList.remove('visible');
        
        setTimeout(() => {
          if (!isMenuOpen) {
            mobileMenu.classList.add('hidden');
          }
        }, 300);
      }
    });
    
    // Handle menu links for improved UX
    const menuLinks = mobileMenu.querySelectorAll('a');
    menuLinks.forEach(link => {
      link.addEventListener('click', () => {
        // Close menu when a link is clicked
        // Small delay to allow for the click to register
        setTimeout(() => {
          isMenuOpen = false;
          menuToggle.setAttribute('aria-expanded', 'false');
          mobileMenu.style.maxHeight = '0';
          mobileMenu.classList.remove('visible');
          
          setTimeout(() => {
            mobileMenu.classList.add('hidden');
          }, 300);
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
   * Essential for performance with resize events
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