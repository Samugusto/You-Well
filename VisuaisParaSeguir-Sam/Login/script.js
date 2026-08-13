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

    // Inicializar botão desabilitado e cores dos requisitos
    validatePasswordAndToggleButton();
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

/* ícone do setor */
let botao5 = document.querySelector("#icone7")
let botao6 = document.querySelector("#icone8")

/* input senha confirmar no caso */
let confirm = document.querySelector("#confirmar")

/* input setor */
let sector = document.querySelector("#setor")

// CHAMAR IMEDIATAMENTE quando digitar
document.getElementById("senha").addEventListener("keyup", function () {
    let password = this.value;
    let progressBar = document.getElementById("progress-bar");

    let lengthPercent = Math.min(100, Math.round((password.length / 8) * 100)); // Alterado para 8
    progressBar.style.width = lengthPercent + "%";

    if (password.length === 0) {
        progressBar.style.backgroundColor = "#ced4da";
    } else if (password.length < 8) {
        progressBar.style.backgroundColor = "#ff0019";
    } else if (!/[A-Z]/.test(password) || !/[@#\$%\^&\*\(\)_\+\-=\[\]\{\};':"\\|,.<>\/?]/.test(password)) {
        progressBar.style.backgroundColor = "#ffbf00";
    } else {
        progressBar.style.backgroundColor = "#00ffb3";
    }

    // Validar senha e desabilitar botão se necessário
    validatePasswordAndToggleButton();
});

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

function validatePassword(password) {
    // Pelo menos 8 caracteres, uma maiúscula e um caracter especial
    const minLength = password.length >= 8;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasSpecialChar = /[@#\$%\^&\*\(\)_\+\-=\[\]\{\};':"\\|,.<>\/?]/.test(password);
    return minLength && hasUpperCase && hasSpecialChar;
}

function validatePasswordAndToggleButton() {
    const password = senha.value;
    const isValid = validatePassword(password);
    const button = document.getElementById("cadastrar");
    if (isValid) {
        button.style.pointerEvents = 'auto';
        button.style.opacity = '1';
    } else {
        button.style.pointerEvents = 'none';
        button.style.opacity = '0.5';
    }

    // Mudar cores dos requisitos
    const reqMaiuscula = document.getElementById("req-maiuscula");
    const reqCaracteres = document.getElementById("req-caracteres");
    const reqEspecial = document.getElementById("req-especial");

    reqMaiuscula.style.color = /[A-Z]/.test(password) ? "#00ffb3" : "#f50000";
    reqCaracteres.style.color = password.length >= 8 ? "#00ffb3" : "#f50000";
    reqEspecial.style.color = /[@#\$%\^&\*\(\)_\+\-=\[\]\{\};':"\\|,.<>\/?]/.test(password) ? "#00ffb3" : "#f50000";
}

cadastrar.addEventListener("click", function () {
    // Verificar senha primeiro
    if (!validatePassword(senha.value)) {
        alert("A senha deve ter pelo menos 8 caracteres, uma letra maiúscula e um caracter especial como @ ou #.");
        return;
    }

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

    if (senha.value === "" || confirm.value === "") {
        confirm.style.borderColor = "#f50000";
        senha.style.borderColor = "#f50000";
        botao3.style.display = "block";
        botao4.style.display = "none";
    }

    else if (senha.value === confirm.value) {
        confirm.style.borderColor = "#00ffb3";
        senha.style.borderColor = "#00ffb3";
        botao4.style.display = "block";
        botao3.style.display = "none";
    }
    else {
        confirm.style.borderColor = "#f50000";
        senha.style.borderColor = "#f50000";
        botao3.style.display = "block";
        botao4.style.display = "none";
    }
    if (sector.value.trim() === "") {
        sector.style.borderColor = "#f50000"; // Vermelho
        botao5.style.display = "block";
        botao6.style.display = "none";
    } else {
        sector.style.borderColor = "#00ffb3"; // Verde
        botao6.style.display = "block";
        botao5.style.display = "none";
    }
});
