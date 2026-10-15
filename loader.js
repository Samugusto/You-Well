document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const loader = document.getElementById('loader');
    const header = document.getElementById('header');
    if (!loader) return;

    const blockScroll = event => event.preventDefault();
    body.classList.add('loading-locked');
    header?.classList.add('loading-hidden');

    ['wheel', 'touchmove'].forEach(eventName => {
        document.addEventListener(eventName, blockScroll, { passive: false });
    });

    const removeLoader = () => {
        loader.classList.add('hidden');

        window.setTimeout(() => {
            body.classList.remove('loading-locked');
            header?.classList.remove('loading-hidden');

            ['wheel', 'touchmove'].forEach(eventName => {
                document.removeEventListener(eventName, blockScroll);
            });

            loader.remove();
        }, 700);
    };

    if (document.readyState === 'complete') {
        window.setTimeout(removeLoader, 1000);
    } else {
        window.addEventListener('load', () => {
            window.setTimeout(removeLoader, 1000);
        }, { once: true });
    }
});
