document.addEventListener('DOMContentLoaded', function() {
    const heroStatic = document.querySelector('.hero-static');
    const footerStatic = document.querySelector('.footer-static');
    
    const Z_INDEX_BASE = getComputedStyle(document.documentElement).getPropertyValue('--z-base') || '1';
    const Z_INDEX_ABOVE = getComputedStyle(document.documentElement).getPropertyValue('--z-above') || '10';
    const Z_INDEX_HERO_TOP = '95'; // New z-index value for hero when at top
    
    updateElementVisibility();
    
    window.addEventListener('scroll', throttle(updateElementVisibility, 100));
    window.addEventListener('resize', throttle(updateElementVisibility, 250));
    
    function updateElementVisibility() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const windowHeight = window.innerHeight;
        const documentHeight = Math.max(
            document.body.scrollHeight, 
            document.body.offsetHeight, 
            document.documentElement.clientHeight, 
            document.documentElement.scrollHeight, 
            document.documentElement.offsetHeight
        );
        
        const distanceFromBottom = documentHeight - (scrollTop + windowHeight);
        const atTopThreshold = 10;
        const atBottomThreshold = 10;
        
        const isAtTop = scrollTop <= atTopThreshold;
        const isAtBottom = distanceFromBottom <= atBottomThreshold;
        
        if (heroStatic) {
            heroStatic.style.zIndex = isAtTop ? Z_INDEX_HERO_TOP : Z_INDEX_BASE;
        }
        
        if (footerStatic) {
            footerStatic.style.zIndex = isAtBottom ? Z_INDEX_ABOVE : Z_INDEX_BASE;
        }
    }
    
    // Rest of your code...
});
