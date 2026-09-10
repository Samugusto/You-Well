document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const loader = document.getElementById('loader');
    const header = document.getElementById('header');
    const blockScroll = function (event) {
        event.preventDefault();
    };

    if (body) {
        body.classList.add('loading-locked');
    }

    if (header) {
        header.classList.add('loading-hidden');
    }

    ['wheel', 'touchmove'].forEach(function (eventName) {
        document.addEventListener(eventName, blockScroll, { passive: false });
    });

    function removeLoader() {
        if (loader) {
            loader.classList.add('hidden');
        }

        setTimeout(function () {
            if (body) {
                body.classList.remove('loading-locked');
            }

            if (header) {
                header.classList.remove('loading-hidden');
            }

            ['wheel', 'touchmove'].forEach(function (eventName) {
                document.removeEventListener(eventName, blockScroll);
            });

            if (loader) {
                loader.remove();
            }
        }, 800);
    }

    window.addEventListener('load', function () {
        setTimeout(removeLoader, 1000);
    });

    const nome = document.getElementById('nome');
    const cadastrar = document.getElementById('cadastrar');
    const senha = document.getElementById('senha');
    const botaoSenha = document.getElementById('botaoSenha');
    const confirm = document.getElementById('confirmar');
    const sector = document.getElementById('setor');
    const botao1 = document.getElementById('icone3');
    const botao2 = document.getElementById('icone4');
    const botao3 = document.getElementById('icone5');
    const botao4 = document.getElementById('icone6');
    const botao5 = document.getElementById('icone7');
    const botao6 = document.getElementById('icone8');

    function validatePassword(password) {
        const minLength = password.length >= 8;
        const hasUpperCase = /[A-Z]/.test(password);
        const hasSpecialChar = /[@#$%^&*()_+\-=\[\]{};':"\\|,.<>/?]/.test(password);
        return minLength && hasUpperCase && hasSpecialChar;
    }

    function validatePasswordAndToggleButton() {
        if (!senha) {
            return;
        }

        const password = senha.value;
        const isValid = validatePassword(password);
        const button = document.getElementById('cadastrar');

        if (button) {
            button.style.pointerEvents = isValid ? 'auto' : 'none';
            button.style.opacity = isValid ? '1' : '0.5';
        }

        const reqMaiuscula = document.getElementById('req-maiuscula');
        const reqCaracteres = document.getElementById('req-caracteres');
        const reqEspecial = document.getElementById('req-especial');

        if (reqMaiuscula) {
            reqMaiuscula.style.color = /[A-Z]/.test(password) ? '#145944' : '#f50000';
        }
        if (reqCaracteres) {
            reqCaracteres.style.color = password.length >= 8 ? '#145944' : '#f50000';
        }
        if (reqEspecial) {
            reqEspecial.style.color = /[@#$%^&*()_+\-=\[\]{};':"\\|,.<>/?]/.test(password) ? '#145944' : '#f50000';
        }
    }

    if (senha) {
        senha.addEventListener('keyup', function () {
            const password = this.value;
            const progressBar = document.getElementById('progress-bar');

            if (progressBar) {
                const lengthPercent = Math.min(100, Math.round((password.length / 8) * 100));
                progressBar.style.width = lengthPercent + '%';

                if (password.length === 0) {
                    progressBar.style.backgroundColor = '#ced4da';
                } else if (password.length < 8) {
                    progressBar.style.backgroundColor = '#ff0019';
                } else if (!/[A-Z]/.test(password) || !/[@#$%^&*()_+\-=\[\]{};':"\\|,.<>/?]/.test(password)) {
                    progressBar.style.backgroundColor = '#ffbf00';
                } else {
                    progressBar.style.backgroundColor = '#00ffb3';
                }
            }

            validatePasswordAndToggleButton();
        });
    }

    if (botaoSenha && senha) {
        botaoSenha.addEventListener('click', function () {
            if (senha.type === 'password') {
                senha.setAttribute('type', 'text');
                botaoSenha.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
            } else {
                senha.setAttribute('type', 'password');
                botaoSenha.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
            }
        });
    }

    if (cadastrar && nome && senha && confirm && sector) {
        cadastrar.addEventListener('click', function (event) {
            if (!validatePassword(senha.value)) {
                event.preventDefault();
                alert('A senha deve ter pelo menos 8 caracteres, uma letra maiúscula e um caracter especial como @ ou #.');
                return;
            }

            if (nome.value.trim() === '') {
                if (botao1) botao1.style.display = 'block';
                if (botao2) botao2.style.display = 'none';
                nome.style.borderColor = '#f50000';
            } else {
                if (botao1) botao1.style.display = 'none';
                if (botao2) botao2.style.display = 'block';
                nome.style.borderColor = '#00ffb3';
            }

            if (senha.value.trim() === '') {
                senha.style.borderColor = '#f50000';
            } else {
                senha.style.borderColor = '#00ffb3';
            }

            if (senha.value === '' || confirm.value === '') {
                confirm.style.borderColor = '#f50000';
                senha.style.borderColor = '#f50000';
                if (botao3) botao3.style.display = 'block';
                if (botao4) botao4.style.display = 'none';
            } else if (senha.value === confirm.value) {
                confirm.style.borderColor = '#00ffb3';
                senha.style.borderColor = '#00ffb3';
                if (botao3) botao3.style.display = 'none';
                if (botao4) botao4.style.display = 'block';
            } else {
                confirm.style.borderColor = '#f50000';
                senha.style.borderColor = '#f50000';
                if (botao3) botao3.style.display = 'block';
                if (botao4) botao4.style.display = 'none';
            }

            if (sector.value.trim() === '') {
                sector.style.borderColor = '#f50000';
                if (botao5) botao5.style.display = 'block';
                if (botao6) botao6.style.display = 'none';
            } else {
                sector.style.borderColor = '#00ffb3';
                if (botao5) botao5.style.display = 'none';
                if (botao6) botao6.style.display = 'block';
            }
        });
    }

    const inputImagem = document.getElementById('campoImg');
    const labelImagem = document.querySelector('.custom-file-label');
    const nomeArquivo = document.getElementById('file-name');
    const cropModal = document.getElementById('cropModal');
    const cropImage = document.getElementById('cropImage');
    const hiddenCropInput = document.getElementById('imagemCropada');
    const confirmCropButton = document.getElementById('confirmCrop');
    const cancelCropButton = document.getElementById('cancelCrop');
    let cropper = null;

    if (inputImagem && labelImagem && nomeArquivo) {
        inputImagem.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                const file = this.files[0];
                const reader = new FileReader();

                nomeArquivo.textContent = file.name;
                labelImagem.textContent = 'Trocar ficheiro';

                reader.onload = function (event) {
                    if (!cropModal || !cropImage || typeof Cropper === 'undefined') {
                        return;
                    }

                    cropImage.src = event.target.result;

                    if (cropper) {
                        cropper.destroy();
                    }

                    cropper = new Cropper(cropImage, {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        responsive: true,
                        background: false,
                        guides: true,
                        center: true,
                        cropBoxResizable: true,
                        zoomOnWheel: true
                    });

                    cropModal.style.display = 'flex';
                };

                reader.readAsDataURL(file);
            } else {
                nomeArquivo.textContent = 'Nenhum ficheiro selecionado';
                labelImagem.textContent = 'Escolher ficheiro';
            }
        });
    }

    if (confirmCropButton) {
        confirmCropButton.addEventListener('click', function () {
            if (!cropper || !cropModal || !hiddenCropInput) {
                return;
            }

            const croppedCanvas = cropper.getCroppedCanvas({
                width: 300,
                height: 300,
                imageSmoothingQuality: 'high'
            });

            hiddenCropInput.value = croppedCanvas.toDataURL('image/jpeg', 0.9);
            cropModal.style.display = 'none';
        });
    }

    if (cancelCropButton) {
        cancelCropButton.addEventListener('click', function () {
            if (!cropModal) {
                return;
            }

            cropModal.style.display = 'none';
            if (inputImagem) {
                inputImagem.value = '';
            }
            if (nomeArquivo) {
                nomeArquivo.textContent = 'Nenhum ficheiro selecionado';
            }
            if (labelImagem) {
                labelImagem.textContent = 'Escolher ficheiro';
            }
            if (hiddenCropInput) {
                hiddenCropInput.value = '';
            }
        });
    }

    validatePasswordAndToggleButton();
});
