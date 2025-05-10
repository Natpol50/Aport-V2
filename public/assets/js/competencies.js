/**
 * competencies.js - Handles competency tab interactions and badge effects
 */

document.addEventListener('DOMContentLoaded', function() {
  console.log('Competencies script loaded');
  initCompetencyTabs();
  initCompetencyBadgeEffects();
});

/**
 * Initialize competency tabs functionality
 */
function initCompetencyTabs() {
  const tabButtons = document.querySelectorAll('.competency-tab-button');
  
  // Exit if no tab buttons found
  if (tabButtons.length === 0) {
    console.log('No competency tab buttons found');
    return;
  }
  
  console.log('Initializing competency tabs');
  
  // Add click event to each tab button
  tabButtons.forEach(button => {
    button.addEventListener('click', function() {
      // Get the target tab panel ID
      const tabId = this.dataset.tab;
      
      // Remove active class from all buttons and panels
      document.querySelectorAll('.competency-tab-button').forEach(btn => {
        btn.classList.remove('active');
      });
      
      document.querySelectorAll('.competency-tab-panel').forEach(panel => {
        panel.classList.remove('active');
      });
      
      // Add active class to clicked button and corresponding panel
      this.classList.add('active');
      document.getElementById(tabId).classList.add('active');
    });
  });
}

/**
 * Initialize competency badge hover effects
 */
function initCompetencyBadgeEffects() {
  // Apply hover effect on competency badges
  const badges = document.querySelectorAll('.competency-badge');
  
  if (badges.length === 0) {
    console.log('No competency badges found');
    return;
  }
  
  console.log(`Initializing ${badges.length} competency badges`);
  
  badges.forEach(badge => {
    // Make sure badge has the proper initial styling
    badge.style.transform = 'skewX(-20deg)';
    badge.style.boxShadow = '3px 3px 0 rgba(0, 0, 0, 0.2)';
    
    // Make sure text inside has proper styling
    const textElement = badge.querySelector('.trapezoid-text');
    if (textElement) {
      textElement.style.transform = 'skewX(20deg)';
    }
    
    badge.addEventListener('mouseenter', function() {
      this.style.transform = 'skewX(-20deg) translateY(-3px)';
      this.style.boxShadow = '5px 5px 0 rgba(0, 0, 0, 0.2)';
    });
    
    badge.addEventListener('mouseleave', function() {
      this.style.transform = 'skewX(-20deg)';
      this.style.boxShadow = '3px 3px 0 rgba(0, 0, 0, 0.2)';
    });
  });
}

// Force initialization when the window loads as well (backup)
window.addEventListener('load', function() {
  console.log('Window loaded, reinitializing competencies');
  initCompetencyTabs();
  initCompetencyBadgeEffects();
});