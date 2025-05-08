/**
 * css-override-fix.js - Remove CSS rules that block 3D transformations
 * 
 * This script:
 * 1. Identifies CSS rules that prevent 3D transformations from working
 * 2. Disables those rules by setting a higher-priority override
 * 3. Ensures our 3D effects can work properly
 * 
 * The mathematics here involve CSS specificity calculations and
 * style priority determination.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Fix CSS overrides that block 3D effects
    fixCssOverrides();
  });
  
  /**
   * Fix CSS overrides that block 3D transformations
   */
  function fixCssOverrides() {
    try {
      // Create a style element to hold our fixes
      const fixStyle = document.createElement('style');
      fixStyle.id = 'css-override-fixes';
      
      // Add rules to override any transform: none!important rules
      // We use higher specificity and !important to ensure our rules win
      fixStyle.textContent = `
        /* Override transform:none rules */
        html body .project-card,
        html body .project-card.fade-in,
        html body .projects-container .project-card,
        html body main .project-card,
        html body .content-section .project-card,
        html body .fade-in.project-card,
        html body .project-card.fade-in:not(.visible),
        html body .project-card.visible,
        body .project-card[class],
        .project-card[class][class] {
          transform: translateZ(0) !important; /* Initial transform that can be overridden */
          transition: transform 0.3s ease, box-shadow 0.3s ease !important;
          opacity: 1 !important;
          visibility: visible !important;
        }
        
        /* Allow hover transform to work */
        html body .project-card:hover,
        .project-card:hover[class],
        html .project-card.hover-active {
          transform: translateY(-5px) rotateX(2deg) rotateY(2deg) !important;
          box-shadow: -2px 10px 25px rgba(0, 0, 0, 0.15) !important;
        }
        
        /* Reset important flags when using dynamic 3D hover */
        html body .project-card.hover-active {
          transform: none !important; /* Will be set dynamically by JavaScript */
          transition: transform 0.1s ease, box-shadow 0.3s ease !important;
        }
      `;
      
      // Add the style element to the document head
      document.head.appendChild(fixStyle);
      
      console.log('Applied CSS override fixes for 3D transformations');
    } catch (err) {
      console.error('Error fixing CSS overrides:', err);
    }
  }