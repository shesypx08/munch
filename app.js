// =============================
// MUNCH GENERAL UI SCRIPT
// Safe for all pages.
// =============================

(function () {
    function setupMobileNav() {
        const toggleBtn = document.querySelector('.nav-toggler');
        const linksContainer = document.querySelector('.links-container');

        if (!toggleBtn || !linksContainer || toggleBtn.dataset.navReady === 'true') return;

        toggleBtn.dataset.navReady = 'true';
        toggleBtn.addEventListener('click', function () {
            toggleBtn.classList.toggle('active');
            linksContainer.classList.toggle('active');
        });
    }

    function setupReviewSlider() {
        const reviews = document.querySelectorAll('.review-wrapper');
        if (!reviews.length) return;

        let currentReviews = [0, Math.min(2, reviews.length - 1)];

        function updateReviewSlider(cards) {
            setTimeout(function () {
                cards.forEach(function (cardIndex) {
                    if (reviews[cardIndex]) reviews[cardIndex].classList.add('active');
                });
            }, 250);
        }

        updateReviewSlider(currentReviews);

        setInterval(function () {
            currentReviews.forEach(function (currentIndex, i) {
                if (reviews[currentIndex]) reviews[currentIndex].classList.remove('active');
                currentReviews[i] = currentIndex >= reviews.length - 1 ? 0 : currentIndex + 1;
            });

            updateReviewSlider(currentReviews);
        }, 5000);
    }

    function setupFaq() {
        const faqs = document.querySelectorAll('.faq');
        faqs.forEach(function (faq) {
            const question = faq.querySelector('.question-box');
            if (!question) return;

            question.addEventListener('click', function () {
                faq.classList.toggle('active');
            });
        });
    }

    function setupDishSlider() {
        const dishSlider = document.querySelector('.dish-slider');
        if (!dishSlider) return;

        let rotationVal = 0;
        setInterval(function () {
            rotationVal += 120;
            dishSlider.style.transform = `translateY(-50%) rotate(${rotationVal}deg)`;
        }, 3000);
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupMobileNav();
        setupReviewSlider();
        setupFaq();
        setupDishSlider();

        if (typeof AOS !== 'undefined') {
            AOS.init({ duration: 800, once: true });
        }
    });
})();
