/* Landing page interactions and accessibility */
document.addEventListener('DOMContentLoaded', function() {
    // Enhance CTA button accessibility
    const ctaButtons = document.querySelectorAll('.landing-ctas .btn');
    ctaButtons.forEach(btn => {
        btn.setAttribute('tabindex', '0');
        
        // Keyboard focus handling for mouse users
        btn.addEventListener('click', function(e) {
            this.blur(); // Remove focus after click for cleaner look
        });
        
        btn.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });

    // Log A/B variant for analytics (optional—replace with your analytics service)
    const params = new URLSearchParams(window.location.search);
    const variant = params.get('variant') || 'signup_first';
    console.log('[Landing] A/B Variant:', variant);
    // Example: if (window.gtag) gtag('event', 'landing_variant', { variant });

    // Intersection observer for fade-in animations on scroll (progressive enhancement)
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.feature').forEach(el => {
            el.style.opacity = '0.9'; // Start slightly visible
            observer.observe(el);
        });
    }
});