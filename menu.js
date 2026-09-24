document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.menu-toggle');
    const mobile = window.matchMedia('(max-width: 700px)');

    if (!toggle) {
        return;
    }

    const atualizarEstado = (recolhido) => {
        const icone = toggle.querySelector('i');

        toggle.setAttribute('aria-expanded', String(!recolhido));
        toggle.setAttribute('aria-label', recolhido ? 'Mostrar menu' : 'Esconder menu');

        if (icone) {
            icone.classList.toggle('bi-layout-sidebar-inset-reverse', recolhido);
            icone.classList.toggle('bi-layout-sidebar-inset', !recolhido);
        }
    };

    if (mobile.matches) {
        document.body.classList.add('menu-recolhido');
    }

    atualizarEstado(document.body.classList.contains('menu-recolhido'));

    toggle.addEventListener('click', () => {
        const recolhido = document.body.classList.toggle('menu-recolhido');
        atualizarEstado(recolhido);
    });

    mobile.addEventListener('change', ({ matches }) => {
        document.body.classList.toggle('menu-recolhido', matches);
        atualizarEstado(matches);
    });
});
