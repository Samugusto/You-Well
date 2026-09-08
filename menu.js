document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.menu-toggle');

    if (!toggle) {
        return;
    }

    toggle.addEventListener('click', () => {
        const recolhido = document.body.classList.toggle('menu-recolhido');
        const icone = toggle.querySelector('i');

        toggle.setAttribute('aria-expanded', String(!recolhido));
        toggle.setAttribute('aria-label', recolhido ? 'Mostrar menu' : 'Esconder menu');

        if (icone) {
            icone.classList.toggle('bi-layout-sidebar-inset-reverse', recolhido);
            icone.classList.toggle('bi-layout-sidebar-inset', !recolhido);
        }
    });
});
