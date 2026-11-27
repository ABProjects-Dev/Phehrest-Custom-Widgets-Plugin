(() => {
    'use strict';
    document.querySelectorAll('.pcw-carousel').forEach(carousel => {
        const track = carousel.querySelector('.pcw-carousel__track');
        const cards = [...track.querySelectorAll('.pcw-card')];
        const navigation = carousel.querySelector('.pcw-carousel__navigation');
        if (cards.length < 2) return;
        const previous = carousel.querySelector('.pcw-carousel__previous');
        const next = carousel.querySelector('.pcw-carousel__next');
        let index = 0;
        function update() { previous.disabled = index === 0; next.disabled = index === cards.length - 1; }
        function move(delta) {
            index = Math.max(0, Math.min(cards.length - 1, index + delta));
            cards[index].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
            update();
        }
        previous.addEventListener('click', () => move(-1));
        next.addEventListener('click', () => move(1));
        // Manual touch/scroll should also update navigation to the nearest card.
        track.addEventListener('scroll', () => {
            const bounds = track.getBoundingClientRect();
            const rtl = getComputedStyle(track).direction === 'rtl';
            const edge = rtl ? bounds.right : bounds.left;
            let distance = Infinity;
            cards.forEach((card, i) => {
                const rect = card.getBoundingClientRect();
                const current = Math.abs((rtl ? rect.right : rect.left) - edge);
                if (current < distance) { distance = current; index = i; }
            });
            update();
        }, { passive: true });
        navigation.hidden = false;
        update();
    });
})();
