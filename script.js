document.addEventListener('DOMContentLoaded', function () {
    const ratingWraps = document.querySelectorAll('.aps-rating-wrap');

    ratingWraps.forEach(wrap => {
        wrap.classList.add('animated');
    });
});
