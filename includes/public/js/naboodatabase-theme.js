/**
 * Naboo Database - Frontend Theme Interactions
 * Handles: mobile menu, back-to-top, scroll progress, header shrink, scroll animations
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* ---- Mobile Menu Toggle ---- */
        const toggle = document.querySelector('.naboo-mobile-toggle');
        const nav = document.querySelector('.naboo-main-navigation');
        if (toggle && nav) {
            toggle.addEventListener('click', function () {
                toggle.classList.toggle('active');
                nav.classList.toggle('open');
                document.body.classList.toggle('mobile-nav-open');

                // Update aria-expanded
                var isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', !isExpanded);
            });
            // Close on outside click
            document.addEventListener('click', function (e) {
                if (!toggle.contains(e.target) && !nav.contains(e.target) && nav.classList.contains('open')) {
                    toggle.classList.remove('active');
                    nav.classList.remove('open');
                    document.body.classList.remove('mobile-nav-open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        }

        /* ---- Header Shrink on Scroll ---- */
        const header = document.querySelector('.naboo-site-header');
        if (header) {
            let ticking = false;
            window.addEventListener('scroll', function () {
                if (!ticking) {
                    window.requestAnimationFrame(function () {
                        if (window.scrollY > 50) {
                            header.classList.add('scrolled');
                        } else {
                            header.classList.remove('scrolled');
                        }
                        ticking = false;
                    });
                    ticking = true;
                }
            });
        }

        /* ---- Back to Top Button ---- */
        const backToTop = document.querySelector('.naboo-back-to-top');
        if (backToTop) {
            window.addEventListener('scroll', function () {
                if (window.scrollY > 400) {
                    backToTop.classList.add('visible');
                } else {
                    backToTop.classList.remove('visible');
                }
            });
            backToTop.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        /* ---- Reading Progress Bar ---- */
        const progressBar = document.querySelector('.naboo-progress-bar');
        if (progressBar) {
            window.addEventListener('scroll', function () {
                const scrollTop = window.scrollY;
                const docHeight = document.documentElement.scrollHeight - window.innerHeight;
                const progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
                progressBar.style.width = progress + '%';
            });
        }

        /* ---- Scroll Animations (Intersection Observer) ---- */
        const animateElements = document.querySelectorAll('.naboo-animate');
        if (animateElements.length > 0 && 'IntersectionObserver' in window) {
            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animated');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -40px 0px'
            });

            animateElements.forEach(function (el, index) {
                el.style.setProperty('--naboo-stagger', index);
                observer.observe(el);
            });
        } else {
            // Fallback: just show everything
            animateElements.forEach(function (el) {
                el.classList.add('animated');
            });
        }

    });
})();
