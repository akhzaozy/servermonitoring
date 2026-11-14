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
 * Scramble Text Helper powered by Anime.js v4
 * @param {HTMLElement|string} elementOrSelector
 * @param {string} newText
 * @param {object} options
 */
window.scrambleElement = function (elementOrSelector, newText, options = {}) {
    const el = typeof elementOrSelector === 'string' ? document.querySelector(elementOrSelector) : elementOrSelector;
    if (!el) return;

    if (!el.textContent && !el.innerText) {
        el.textContent = ' ';
    }

    try {
        animate(el, {
            innerHTML: scrambleText({
                text: String(newText),
                chars: options.chars || '0123456789%ms.•ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                duration: options.duration || 750,
                from: options.from || 'random',
                ...options
            }),
            duration: options.duration || 750,
            ease: 'linear'
        });
    } catch (e) {
        el.textContent = String(newText);
    }
};

/**
 * Trigger smooth staggered entrance animations on cards using Framer Motion (motion)
 */
window.initMotionCards = function () {
    const cards = document.querySelectorAll('.animate-entrance, .main .card');
    if (cards.length > 0) {
        motionAnimate(
            cards,
            { opacity: [0, 1], y: [14, 0], scale: [0.99, 1] },
            { delay: motionStagger(0.045), duration: 0.5, easing: [0.16, 1, 0.3, 1] }
        );
    }
};

document.addEventListener('DOMContentLoaded', () => {
    window.initMotionCards();
});
