/**
 * competencies.js - Handles competency tab interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    initCompetencyTabs();
  });
  
  /**
   * Initialize competency tabs functionality
   */
  function initCompetencyTabs() {
    const tabButtons = document.querySelectorAll('.competency-tab-button');
    
    // Exit if no tab buttons found
    if (tabButtons.length === 0) return;
    
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
    
    // Apply hover effect on competency badges
    const badges = document.querySelectorAll('.competency-badge');
    badges.forEach(badge => {
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