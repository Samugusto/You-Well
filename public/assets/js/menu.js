document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.menu-toggle');
    const notificationButton = document.querySelector('.botao-alertas');
    const notificationPopup = document.getElementById('painel-notificacoes');
    const temaOpcoes = document.querySelectorAll('input[name="tema-painel"]');
    const mobile = window.matchMedia('(max-width: 700px)');

    if (notificationButton && notificationPopup) {
        const closeNotifications = () => {
            notificationPopup.hidden = true;
            notificationButton.setAttribute('aria-expanded', 'false');
            notificationButton.setAttribute('aria-label', 'Abrir avisos recentes');
        };

        notificationButton.addEventListener('click', () => {
            const opening = notificationPopup.hidden;
            notificationPopup.hidden = !opening;
            notificationButton.setAttribute('aria-expanded', String(opening));
            notificationButton.setAttribute('aria-label', opening ? 'Fechar avisos recentes' : 'Abrir avisos recentes');
        });

        document.addEventListener('click', (event) => {
            if (!notificationButton.contains(event.target) && !notificationPopup.contains(event.target)) {
                closeNotifications();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !notificationPopup.hidden) {
                closeNotifications();
                notificationButton.focus();
            }
        });
    }

    if (toggle) {
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
    }

    const preferenciaSistema = window.matchMedia('(prefers-color-scheme: dark)');
    const temaSalvo = localStorage.getItem('youwell-tema');
    const temasValidos = ['light', 'dark', 'system'];
    let temaPreferido = temasValidos.includes(temaSalvo) ? temaSalvo : 'system';

    const atualizarTema = () => {
        const escuro = temaPreferido === 'dark'
            || (temaPreferido === 'system' && preferenciaSistema.matches);

        document.body.dataset.theme = escuro ? 'dark' : 'light';
        if (window.Chart) {
            Chart.defaults.color = escuro ? '#e5e7eb' : '#666';
            Chart.defaults.borderColor = escuro ? '#475569' : '#dee2e6';
            Object.values(Chart.instances).forEach((chart) => chart.update('none'));
        }
    };

    temaOpcoes.forEach((opcao) => {
        opcao.checked = opcao.value === temaPreferido;
        opcao.addEventListener('change', () => {
            if (!opcao.checked) return;

            temaPreferido = opcao.value;
            localStorage.setItem('youwell-tema', temaPreferido);
            atualizarTema();
        });
    });

    atualizarTema();
    preferenciaSistema.addEventListener('change', () => {
        if (temaPreferido === 'system') {
            atualizarTema();
        }
    });
});
