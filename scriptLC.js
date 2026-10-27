const configTabs = [...document.querySelectorAll('[data-config-tab]')];

const ativarAbaConfig = (tab) => {
    configTabs.forEach((item) => {
        const selecionada = item === tab;
        item.classList.toggle('active', selecionada);
        item.setAttribute('aria-selected', String(selecionada));
        item.tabIndex = selecionada ? 0 : -1;
    });

    document.querySelectorAll('.config-panel').forEach((panel) => {
        panel.hidden = panel.id !== tab.dataset.configTab;
    });
};

configTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
        ativarAbaConfig(tab);
    });

    tab.addEventListener('keydown', (event) => {
        const index = configTabs.indexOf(tab);
        let novoIndex = index;

        if (event.key === 'ArrowRight') {
            novoIndex = (index + 1) % configTabs.length;
        } else if (event.key === 'ArrowLeft') {
            novoIndex = (index - 1 + configTabs.length) % configTabs.length;
        } else if (event.key === 'Home') {
            novoIndex = 0;
        } else if (event.key === 'End') {
            novoIndex = configTabs.length - 1;
        } else {
            return;
        }

        event.preventDefault();
        configTabs[novoIndex].focus();
        ativarAbaConfig(configTabs[novoIndex]);
    });
});
(() => {
    const entradaFotoPerfil = document.getElementById('foto-perfil');
    const fotoPerfilRecortada = document.getElementById('foto-perfil-cropada');
    const modalRecortePerfil = document.getElementById('crop-modal-perfil');
    const imagemRecortePerfil = document.getElementById('crop-imagem-perfil');
    const confirmarRecortePerfil = document.getElementById('confirmar-recorte-perfil');
    const cancelarRecortePerfil = document.getElementById('cancelar-recorte-perfil');
    const formularioPerfil = document.querySelector('.perfil-form');
    const previaFotoPerfil = document.querySelector('.perfil-config img');
    const nomeArquivoPerfil = document.getElementById('nome-arquivo-perfil');

    if (
        !entradaFotoPerfil || !fotoPerfilRecortada || !modalRecortePerfil ||
        !imagemRecortePerfil || !confirmarRecortePerfil || !cancelarRecortePerfil ||
        !formularioPerfil || !previaFotoPerfil || !nomeArquivoPerfil
    ) {
        return;
    }

    let recortadorPerfil = null;
    let urlImagemPerfil = null;

    const fecharRecortePerfil = () => {
        modalRecortePerfil.style.display = 'none';
        recortadorPerfil?.destroy();
        recortadorPerfil = null;

        if (urlImagemPerfil) {
            URL.revokeObjectURL(urlImagemPerfil);
            urlImagemPerfil = null;
        }
    };

    entradaFotoPerfil.addEventListener('change', () => {
        const arquivo = entradaFotoPerfil.files?.[0];
        if (!arquivo) return;

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(arquivo.type)) {
            alert('Selecione uma imagem JPG, PNG ou WEBP.');
            entradaFotoPerfil.value = '';
            nomeArquivoPerfil.textContent = 'Nenhuma imagem selecionada';
            return;
        }

        if (arquivo.size > 15 * 1024 * 1024) {
            alert('A imagem original deve ter no máximo 15 MB.');
            entradaFotoPerfil.value = '';
            nomeArquivoPerfil.textContent = 'Nenhuma imagem selecionada';
            return;
        }

        nomeArquivoPerfil.textContent = arquivo.name;
        fecharRecortePerfil();
        urlImagemPerfil = URL.createObjectURL(arquivo);
        imagemRecortePerfil.onload = () => {
            if (typeof Cropper === 'undefined') {
                fecharRecortePerfil();
                alert('Não foi possível carregar a ferramenta de recorte. Tente novamente.');
                entradaFotoPerfil.value = '';
                nomeArquivoPerfil.textContent = 'Nenhuma imagem selecionada';
                return;
            }

            recortadorPerfil = new Cropper(imagemRecortePerfil, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.9,
                responsive: true,
                background: false
            });
        };
        imagemRecortePerfil.onerror = () => {
            fecharRecortePerfil();
            entradaFotoPerfil.value = '';
            nomeArquivoPerfil.textContent = 'Nenhuma imagem selecionada';
            alert('Não foi possível abrir esta imagem. Tente outro arquivo.');
        };
        imagemRecortePerfil.src = urlImagemPerfil;
        modalRecortePerfil.style.display = 'flex';
    });

    confirmarRecortePerfil.addEventListener('click', () => {
        if (!recortadorPerfil) return;

        const canvas = recortadorPerfil.getCroppedCanvas({
            width: 512,
            height: 512,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });
        fotoPerfilRecortada.value = canvas.toDataURL('image/jpeg', 0.9);
        previaFotoPerfil.src = fotoPerfilRecortada.value;
        nomeArquivoPerfil.textContent = 'Imagem ajustada e pronta para salvar';
        entradaFotoPerfil.value = '';
        fecharRecortePerfil();
    });

    cancelarRecortePerfil.addEventListener('click', () => {
        entradaFotoPerfil.value = '';
        nomeArquivoPerfil.textContent = 'Nenhuma imagem selecionada';
        fecharRecortePerfil();
    });

    modalRecortePerfil.addEventListener('click', (event) => {
        if (event.target === modalRecortePerfil) {
            entradaFotoPerfil.value = '';
            nomeArquivoPerfil.textContent = 'Nenhuma imagem selecionada';
            fecharRecortePerfil();
        }
    });

    formularioPerfil.addEventListener('submit', (event) => {
        if (entradaFotoPerfil.files.length > 0) {
            event.preventDefault();
            alert('Ajuste a foto antes de salvar as alterações.');
        }
    });
})();