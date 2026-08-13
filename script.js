// === LOADING COM SEU SPINNER ===
document.addEventListener('DOMContentLoaded', function() {
    // 1. BLOQUEIA TELA
    document.body.classList.add('loading-locked');
    const header = document.getElementById('header');
    if (header) header.classList.add('loading-hidden');
    
    // 2. PREVINE SCROLL
    const blockScroll = (e) => e.preventDefault();
    ['wheel', 'touchmove'].forEach(event => {
        document.addEventListener(event, blockScroll, { passive: false });
    });
    
    // 3. ESPERA TUDO E REMOVE LOADING
    window.addEventListener('load', function() {
        setTimeout(hideLoader, 1000); // 1s mínimo + fade
    });
});

function hideLoader() {
    // Remove loader
    const loader = document.getElementById('loader');
    loader.classList.add('hidden');
    
    // Desbloqueia
    setTimeout(() => {
        document.body.classList.remove('loading-locked');
        const header = document.getElementById('header');
        if (header) header.classList.remove('loading-hidden');
        
        // Remove bloqueios
        ['wheel', 'touchmove'].forEach(event => {
            document.removeEventListener(event, blockScroll);
        });
        
        loader.remove();
    }, 800);
}

let nome = document.querySelector("#nome");/* input nome */
let cadastrar = document.querySelector("#cadastrar"); /* botao cadastrar */

/* ícone de correto e incorreto do nome */
let botao1 = document.querySelector("#icone3")//verificação nome correto
let botao2 = document.querySelector("#icone4")//verificação nome incorreto

/* ícone de correto e incorreto do confirmar senha*/
let botao3 = document.querySelector("#icone5")
let botao4 = document.querySelector("#icone6")



/* botão mostrar ou ocultar senha */
botaoSenha.addEventListener("click", function () {
    if (senha.type === "password") {
        senha.setAttribute('type', 'text');
        botaoSenha.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');//troca de icone
    } else {
        senha.setAttribute('type', 'password');
        botaoSenha.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');//troca de icone
    }
});


cadastrar.addEventListener("click", function () {
    if (nome.value.trim() === "") {
        botao1.style.display = "block";
        botao2.style.display = "none";
        nome.style.borderColor = "#f50000"; // Vermelho
    } else {
        botao1.style.display = "none";
        botao2.style.display = "block";
        nome.style.borderColor = "#00ffb3"; // Verde
    }

    if (senha.value.trim() === "") {
        senha.style.borderColor = "#f50000"; // Vermelho
    } else {
        senha.style.borderColor = "#00ffb3"; // Verde
    }
});
