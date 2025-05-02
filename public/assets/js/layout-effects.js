/**
 * layout-effects.js - Handles scrolling effects with fixed hero and footer
 * 
 * This script:
 * 1. Measures the height of hero and footer sections
 * 2. Sets CSS variables for those heights
 * 3. Adjusts the scroll wrapper padding to match those heights
 * 4. Updates visibility of elements based on scroll position
 * 5. Handles responsive adjustments
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize layout effects
    initLayoutEffects();
    
    // Update on resize for responsive changes
    window.addEventListener('resize', debounce(initLayoutEffects, 250));
    
    // Update on orientation change
    window.addEventListener('orientationchange', debounce(initLayoutEffects, 250));
  });
  
  /**
   * Initialize all layout effects
   */
  function initLayoutEffects() {
    // Measure and set heights for fixed sections
    setFixedSectionHeights();
    
    // Initialize scrolling effects
    initScrollingEffects();
    
    // Set CSS variables for responsive adjustments
    setCSSVariables();
  }
  
  /**
   * Measure and set heights for fixed sections
   */
  function setFixedSectionHeights() {
    // Get elements
    const heroSection = document.querySelector('.hero-section');
    const footerSection = document.querySelector('.main-footer');
    const scrollWrapper = document.querySelector('.scroll-wrapper');
    
    // Only proceed if all elements exist
    if (!heroSection || !footerSection || !scrollWrapper) {
      console.warn('Some elements not found for fixed section measurements');
      return;
    }
    
    // Measure heights
    const heroHeight = heroSection.offsetHeight;
    const footerHeight = footerSection.offsetHeight;
    
    // Set CSS variables for heights
    document.documentElement.style.setProperty('--hero-height', `${heroHeight}px`);
    document.documentElement.style.setProperty('--footer-height', `${footerHeight}px`);
    
    // Set padding on scroll wrapper to match fixed section heights
    scrollWrapper.style.paddingTop = `${heroHeight}px`;
    scrollWrapper.style.paddingBottom = `${footerHeight}px`;
    
    console.log(`Fixed sections measured: Hero height = ${heroHeight}px, Footer height = ${footerHeight}px`);
  }
  
  /**
   * Initialize scrolling effects
   */
  function initScrollingEffects() {
    // Add scroll event listener for parallax and visibility effects
    window.addEventListener('scroll', throttle(handleScroll, 10));
    
    // Initial call to set positions
    handleScroll();
  }
  
  /**
   * Handle scroll events
   */
  function handleScroll() {
    const scrollPosition = window.scrollY;
    const windowHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight;
    
    // Update hero visibility as user scrolls down
    updateHeroVisibility(scrollPosition);
    
    // Update footer visibility as user approaches bottom
    updateFooterVisibility(scrollPosition, windowHeight, documentHeight);
    
    // Update parallax effects
    updateParallaxEffects(scrollPosition);
    
    // Check for elements coming into view
    checkElementsInView();
  }
  
  /**
   * Update hero section visibility based on scroll position
   * @param {number} scrollPosition - Current scroll position
   */
  function updateHeroVisibility(scrollPosition = window.scrollY) {
    const heroContent = document.querySelector('.hero-content');
    const contactButton = document.getElementById('contact-button');
    const heroHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--hero-height'));
    
    if (!heroContent) return;
    
    // Calculate opacity (fade out as we scroll down)
    // Start fading when we're 20% into the hero
    const startFade = heroHeight * 0.2;
    const endFade = heroHeight * 0.8;
    const fadeRange = endFade - startFade;
    
    let opacity = 1;
    if (scrollPosition > startFade) {
      opacity = Math.max(0, 1 - ((scrollPosition - startFade) / fadeRange));
    }
    
    // Apply opacity to hero content
    heroContent.style.opacity = opacity.toString();
    
    // Update contact button separately
    if (contactButton) {
      if (scrollPosition > startFade) {
        contactButton.classList.add('hidden');
      } else {
        contactButton.classList.remove('hidden');
      }
    }
  }
  
  /**
   * Update footer visibility based on scroll position
   * @param {number} scrollPosition - Current scroll position
   * @param {number} windowHeight - Window inner height
   * @param {number} documentHeight - Total document height
   */
  function updateFooterVisibility(scrollPosition = window.scrollY, 
                                windowHeight = window.innerHeight,
                                documentHeight = document.documentElement.scrollHeight) {
    const footerContent = document.querySelector('.footer-content');
    const contactLinks = document.getElementById('contact-links');
    const footerHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--footer-height'));
    
    if (!footerContent && !contactLinks) return;
    
    // Calculate when we're approaching the footer
    // The threshold is the point where the bottom of the viewport reaches the top of the footer
    const footerStart = documentHeight - windowHeight - footerHeight;
    const footerEnd = documentHeight - windowHeight;
    const fadeRange = footerEnd - footerStart;
    
    let opacity = 0;
    if (scrollPosition > footerStart) {
      opacity = Math.min(1, (scrollPosition - footerStart) / fadeRange);
    }
    
    // Apply opacity to footer content
    if (footerContent) {
      footerContent.style.opacity = opacity.toString();
    }
    
    // Apply opacity to contact links separately
    if (contactLinks) {
      contactLinks.style.opacity = opacity.toString();
    }
  }
  
  /**
   * Update parallax effects based on scroll position
   * @param {number} scrollPosition - Current scroll position
   */
  function updateParallaxEffects(scrollPosition = window.scrollY) {
    // Get all parallax layers
    const parallaxLayers = document.querySelectorAll('.parallax-layer');
    
    parallaxLayers.forEach(layer => {
      // Get parallax speed from CSS variable (or default to 0.2)
      const speed = parseFloat(getComputedStyle(layer).getPropertyValue('--parallax-speed')) || 0.2;
      
      // Calculate offset based on scroll position and speed
      const offset = scrollPosition * speed;
      
      // Apply transform to create parallax effect
      layer.style.transform = `translateY(-${offset}px)`;
    });
  }
  
  /**
   * Check for elements coming into view to trigger animations
   */
  function checkElementsInView() {
    // Get all elements with fade-in class
    const fadeElements = document.querySelectorAll('.fade-in:not(.footer-content)');
    
    fadeElements.forEach(element => {
      // Check if element is in viewport
      if (isElementInViewport(element)) {
        element.classList.add('visible');
      } else {
        element.classList.remove('visible');
      }
    });
  }
  
  /**
   * Set CSS variables for responsive adjustments
   */
  function setCSSVariables() {
    const windowHeight = window.innerHeight;
    const headerHeight = document.querySelector('.main-header')?.offsetHeight || 0;
    
    // Set variables
    document.documentElement.style.setProperty('--window-height', `${windowHeight}px`);
    document.documentElement.style.setProperty('--header-height', `${headerHeight}px`);
  }
  
  /**
   * Check if element is in viewport
   * @param {HTMLElement} element - Element to check
   * @returns {boolean} - True if element is in viewport
   */
  function isElementInViewport(element) {
    const rect = element.getBoundingClientRect();
    const windowHeight = window.innerHeight || document.documentElement.clientHeight;
    
    // Element is in viewport if its top or bottom edge is within the viewport
    return (
      (rect.top <= windowHeight && rect.top >= 0) ||
      (rect.bottom <= windowHeight && rect.bottom >= 0) ||
      (rect.top <= 0 && rect.bottom >= windowHeight)
    );
  }
  
  /**
   * Handle click on scroll indicator
   * @param {Event} event - Click event
   */
  function scrollIndicatorClick(event) {
    const heroHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--hero-height'));
    
    // Scroll to just below the hero section
    window.scrollTo({
      top: heroHeight,
      behavior: 'smooth'
    });
  }
  
  /**
   * Debounce function to limit how often a function is called
   * @param {Function} func - Function to debounce
   * @param {number} wait - Wait time in milliseconds
   * @returns {Function} - Debounced function
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
   * Throttle function to limit the rate at which a function is called
   * @param {Function} func - Function to throttle
   * @param {number} limit - Limit in milliseconds
   * @returns {Function} - Throttled function
   */
  function throttle(func, limit) {
    let inThrottle;
    return function() {
      const context = this;
      const args = arguments;
      if (!inThrottle) {
        func.apply(context, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  }
  
  // Add event listener to scroll indicator if it exists
  document.addEventListener('DOMContentLoaded', function() {
    const scrollIndicator = document.querySelector('.scroll-indicator');
    if (scrollIndicator) {
      scrollIndicator.addEventListener('click', scrollIndicatorClick);
    }
  });