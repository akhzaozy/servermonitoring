import * as anime from 'animejs';
import { animate, scrambleText, stagger, createTimeline } from 'animejs';
import { animate as motionAnimate, inView, stagger as motionStagger } from 'motion';

// Export globally to window for Blade view access
window.anime = anime;
window.scrambleText = scrambleText;
window.animateAnime = animate;
window.createTimeline = createTimeline;
window.stagger = stagger;
window.motionAnimate = motionAnimate;
window.inView = inView;
window.motionStagger = motionStagger;

/**
 * Cyber Scramble Text Helper powered by Anime.js v4
 * @param {HTMLElement|string} elementOrSelector
 * @param {string} newText
 * @param {object} options
 */
window.scrambleElement = function (elementOrSelector, newText, options = {}) {
    const el = typeof elementOrSelector === 'string' ? document.querySelector(elementOrSelector) : elementOrSelector;
    if (!el) return;

    // Ensure element has starting text content for anime.js scramble
    if (!el.textContent && !el.innerText) {
        el.textContent = ' ';
    }

    try {
        animate(el, {
            innerHTML: scrambleText({
                text: String(newText),
                chars: options.chars || '0123456789#@*+=~XYZ',
                duration: options.duration || 800,
                from: options.from || 'random',
                ...options
            }),
            duration: options.duration || 800,
            ease: 'linear'
        });
    } catch (e) {
        console.warn('Anime scramble fallback:', e);
        el.textContent = String(newText);
    }
};

/**
 * Trigger entrance animations on cards and elements using Framer-Motion engine (Motion)
 */
window.initMotionCards = function () {
    const cards = document.querySelectorAll('.animate-entrance');
    if (cards.length > 0) {
        motionAnimate(
            cards,
            { opacity: [0, 1], y: [20, 0], scale: [0.98, 1] },
            { delay: motionStagger(0.06), duration: 0.6, easing: [0.16, 1, 0.3, 1] }
        );
    }
};

// Auto trigger on DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    window.initMotionCards();
});
