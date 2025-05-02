/**
 * main.js - Handles fixed section heights and scroll behavior
 * 
 * This script calculates the heights of the hero and footer sections
 * and applies them as CSS custom properties. This ensures the spacers
 * in the scrollable content accurately match the fixed sections.
 * 
 * Mathematical approach:
 * - We measure the rendered heights of the hero and footer sections
 * - These measurements are then applied as CSS variables
 * - The spacers use these variables to create the exact same space
 * - This creates the illusion that content is scrolling over static backgrounds
 */

document.addEventListener('DOMContentLoaded', function() {
  // Initialize the layout
  initLayout();
  
  // Update on resize
  window.addEventListener('resize', debounce(initLayout, 200));
  
  // Initialize language switcher
  initLanguageSwitcher();
  
  // Initialize portrait warning
  initPortraitWarning();
  
  // Initialize flash messages
  initFlashMessages();
});

/**
* Initialize the layout by measuring and setting heights
*/
function initLayout() {
  // Measure the hero and footer sections
  measureFixedSections();
  
  // Initialize scroll effects
  initScrollEffects();
  
  console.log('Layout initialized');
}

/**
* Measure fixed sections and set CSS variables
* This is the core mathematical operation that makes the layout work
*/
function measureFixedSections() {
  // Get the hero and footer elements
  const heroSection = document.querySelector('.hero-section');
  const footerSection = document.querySelector('.site-footer');
  const header = document.querySelector('.main-header');
  
  // Get their computed heights
  const headerHeight = header ? header.offsetHeight : 0;
  
  // Calculate hero height
  // For visual balance, we use the full viewport minus header
  let heroHeight = heroSection ? heroSection.offsetHeight : 0;
  if (heroHeight === 0) {
      heroHeight = window.innerHeight - headerHeight;
  }
  
  // Get footer height
  const footerHeight = footerSection ? footerSection.offsetHeight : 0;
  
  // Set CSS variables for these heights
  document.documentElement.style.setProperty('--hero-height', `${heroHeight}px`);
  document.documentElement.style.setProperty('--footer-height', `${footerHeight}px`);
  document.documentElement.style.setProperty('--header-height', `${headerHeight}px`);
  
  console.log(`Fixed sections measured: Hero=${heroHeight}px, Footer=${footerHeight}px, Header=${headerHeight}px`);
  
  // Update spacers to match these heights exactly
  updateSpacers(heroHeight, footerHeight);
}

/**
* Update spacers to match fixed section heights
* 
* @param {number} heroHeight - Height of hero section
* @param {number} footerHeight - Height of footer section
*/
function updateSpacers(heroHeight, footerHeight) {
  const topSpacer = document.querySelector('.spacer-top');
  const bottomSpacer = document.querySelector('.spacer-bottom');
  
  if (topSpacer) {
      topSpacer.style.height = `${heroHeight}px`;
  }
  
  if (bottomSpacer) {
      bottomSpacer.style.height = `${footerHeight}px`;
  }
}

/**
* Initialize scroll effects
*/
function initScrollEffects() {
  // Intensity factor for parallax effect (scales movement)
  const PARALLAX_FACTOR = 0.4;
  
  window.addEventListener('scroll', throttle(function() {
      const scrollY = window.scrollY;
      const windowHeight = window.innerHeight;
      const documentHeight = document.documentElement.scrollHeight;
      
      // Apply parallax effect to hero content
      const heroContent = document.querySelector('.hero-content');
      if (heroContent) {
          // Move hero content slightly as user scrolls
          heroContent.style.transform = `translateY(${scrollY * PARALLAX_FACTOR}px)`;
          
          // Fade out as user scrolls down
          const heroHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--hero-height'));
          const opacity = Math.max(0, 1 - (scrollY / (heroHeight * 0.6)));
          heroContent.style.opacity = opacity.toString();
          
          // Hide contact button when scrolled past threshold
          const contactButton = document.getElementById('contact-button');
          if (contactButton) {
              if (scrollY > heroHeight * 0.3) {
                  contactButton.classList.add('hidden');
              } else {
                  contactButton.classList.remove('hidden');
              }
          }
      }
      
      // Handle scroll indicator visibility
      const scrollIndicator = document.querySelector('.scroll-indicator');
      if (scrollIndicator) {
          // Hide when scrolled down
          if (scrollY > windowHeight * 0.3) {
              scrollIndicator.style.opacity = '0';
              scrollIndicator.style.pointerEvents = 'none';
          } else {
              // Linear fade based on scroll position
              const indicatorOpacity = 1 - (scrollY / (windowHeight * 0.3));
              scrollIndicator.style.opacity = indicatorOpacity.toString();
              scrollIndicator.style.pointerEvents = 'auto';
          }
      }
      
      // Apply fade-in effects to elements as they enter viewport
      const fadeElements = document.querySelectorAll('.fade-in');
      fadeElements.forEach(element => {
          if (isElementInViewport(element)) {
              element.classList.add('visible');
          }
      });
      
  }, 10)); // throttle to improve performance
  
  // Initialize scroll indicator click behavior
  initScrollIndicator();
}

/**
* Initialize scroll indicator behavior
* Smoothly scrolls to content when clicked
*/
function initScrollIndicator() {
  const scrollIndicator = document.querySelector('.scroll-indicator');
  if (!scrollIndicator) return;
  
  scrollIndicator.addEventListener('click', function() {
      // Calculate scroll target position - just below hero
      const heroHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--hero-height'));
      
      // Smooth scroll to content
      window.scrollTo({
          top: heroHeight * 0.8, // Scroll to 80% of hero height to show a bit of content
          behavior: 'smooth'
      });
  });
}

/**
* Initialize language switcher behavior
* Preserves scroll position when switching languages
*/
function initLanguageSwitcher() {
  const langLinks = document.querySelectorAll('.language-switcher .lang-link');
  
  langLinks.forEach(link => {
      link.addEventListener('click', function(e) {
          // If already on this language, don't do anything
          if (!this.classList.contains('inactive')) {
              e.preventDefault();
              return;
          }
          
          // Save scroll position to session storage before navigation
          sessionStorage.setItem('scrollPosition', window.scrollY);
      });
  });
  
  // Restore scroll position after language switch if available
  const savedScrollPosition = sessionStorage.getItem('scrollPosition');
  if (savedScrollPosition !== null) {
      window.scrollTo(0, parseInt(savedScrollPosition));
      sessionStorage.removeItem('scrollPosition');
  }
}

/**
* Initialize portrait warning for mobile devices
*/
function initPortraitWarning() {
  const portraitWarning = document.getElementById('portraitWarning');
  const dismissButton = document.getElementById('dismissPortraitWarning');
  
  if (!portraitWarning || !dismissButton) return;
  
  // Check if the warning has been dismissed before
  const hasSeenWarning = localStorage.getItem('hasSeenPortraitWarning') === 'true';
  
  // Check if device is in portrait mode
  const isPortrait = window.matchMedia("(orientation: portrait)").matches;
  
  // Only show warning on mobile devices in portrait mode
  if (isPortrait && !hasSeenWarning && window.innerWidth < 768) {
      portraitWarning.classList.remove('hidden');
  }
  
  // Set up dismiss button
  dismissButton.addEventListener('click', function() {
      portraitWarning.classList.add('hidden');
      localStorage.setItem('hasSeenPortraitWarning', 'true');
  });
  
  // Listen for orientation changes
  window.matchMedia("(orientation: portrait)").addEventListener('change', function(e) {
      if (e.matches && !hasSeenWarning && window.innerWidth < 768) {
          portraitWarning.classList.remove('hidden');
      } else {
          portraitWarning.classList.add('hidden');
      }
  });
}

/**
* Initialize auto-dismissal of flash messages
*/
function initFlashMessages() {
  const flashMessages = document.querySelectorAll('.flash-message');
  
  flashMessages.forEach(message => {
      // Auto-dismiss flash messages after 5 seconds
      setTimeout(() => {
          message.style.opacity = '0';
          setTimeout(() => {
              message.style.display = 'none';
          }, 300);
      }, 5000);
      
      // Add close button to flash messages
      const closeButton = document.createElement('button');
      closeButton.innerHTML = '&times;';
      closeButton.className = 'absolute top-2 right-2 text-lg font-bold';
      closeButton.addEventListener('click', () => {
          message.style.opacity = '0';
          setTimeout(() => {
              message.style.display = 'none';
          }, 300);
      });
      
      // Make sure the message has relative positioning for absolute positioning of the close button
      message.style.position = 'relative';
      message.appendChild(closeButton);
  });
}

/**
* Check if element is in viewport
* 
* @param {HTMLElement} element - Element to check
* @returns {boolean} - True if element is in viewport
*/
function isElementInViewport(element) {
  const rect = element.getBoundingClientRect();
  
  return (
      rect.top <= (window.innerHeight || document.documentElement.clientHeight) * 0.85 &&
      rect.bottom >= 0 &&
      rect.left <= (window.innerWidth || document.documentElement.clientWidth) &&
      rect.right >= 0
  );
}

/**
* Throttle function to limit how often a function is called
* Optimizes performance for scroll events
* 
* @param {Function} func - Function to throttle
* @param {number} limit - Limit in milliseconds
* @returns {Function} - Throttled function
*/
function throttle(func, limit) {
  let lastFunc;
  let lastRan;
  
  return function() {
      const context = this;
      const args = arguments;
      
      if (!lastRan) {
          func.apply(context, args);
          lastRan = Date.now();
      } else {
          clearTimeout(lastFunc);
          lastFunc = setTimeout(function() {
              if ((Date.now() - lastRan) >= limit) {
                  func.apply(context, args);
                  lastRan = Date.now();
              }
          }, limit - (Date.now() - lastRan));
      }
  };
}

/**
* Debounce function to limit how often a function is called
* Optimizes performance for resize events
* 
* @param {Function} func - Function to debounce
* @param {number} wait - Wait time in milliseconds
* @returns {Function} - Debounced function
*/
function debounce(func, wait) {
  let timeout;
  
  return function executedFunction() {
      const context = this;
      const args = arguments;
      
      const later = function() {
          timeout = null;
          func.apply(context, args);
      };
      
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
  };
}