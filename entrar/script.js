document.addEventListener('DOMContentLoaded', function () {
    const nome = document.querySelector('#nome');
    const cadastrar = document.querySelector('#cadastrar');
    const senha = document.querySelector('#senha');
    const botaoSenha = document.querySelector('#botaoSenha');
    const botao1 = document.querySelector('#icone3');
    const botao2 = document.querySelector('#icone4');
    const goToLogin = document.getElementById('goToLogin');
    const pageBg = document.getElementById('pageBg');

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

    if (cadastrar && nome && senha && botao1 && botao2) {
        cadastrar.addEventListener('click', function () {
            if (nome.value.trim() === '') {
                botao1.style.display = 'block';
                botao2.style.display = 'none';
                nome.style.borderColor = '#f50000';
            } else {
                botao1.style.display = 'none';
                botao2.style.display = 'block';
                nome.style.borderColor = '#00ffb3';
            }

            if (senha.value.trim() === '') {
                senha.style.borderColor = '#f50000';
            } else {
                senha.style.borderColor = '#00ffb3';
            }
        });
    }

    if (goToLogin && pageBg) {
        goToLogin.addEventListener('click', function (event) {
            event.preventDefault();
            document.body.classList.add('is-transitioning');
            pageBg.classList.add('animate-right');

            setTimeout(function () {
                window.location.href = goToLogin.getAttribute('href');
            }, 700);
        });
    }
});
