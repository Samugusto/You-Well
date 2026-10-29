// Esconde a tela de carregamento assim que todo a página carrega
window.addEventListener('load', () => {
    const loader = document.getElementById('loader-overlay');
    if (loader) {
        loader.classList.add('is-hidden');
        document.body.classList.remove('is-loading');
    }
});

const root = document.documentElement;

document.addEventListener('pointermove', (event) => {
    root.style.setProperty('--pointer-x', `${event.clientX}px`);
    root.style.setProperty('--pointer-y', `${event.clientY}px`);
    root.style.setProperty('--pointer-glow-opacity', '1');
}, { passive: true });

document.addEventListener('pointerleave', () => {
    root.style.setProperty('--pointer-glow-opacity', '0');
});


let userEmail = '';

function showAlert(msg, isError = true) {
    const box = document.getElementById('alertBox');
    box.innerText = msg;
    box.className = `alert ${isError ? 'alert-error' : 'alert-success'}`;
    box.style.display = 'block';
}

function clearAlert() {
    document.getElementById('alertBox').style.display = 'none';
}

// Navegação entre os caixas de 6 dígitos
function moveNext(input, index) {
    const inputs = document.querySelectorAll('.otp-input');
    input.value = input.value.replace(/[^0-9]/g, '');
    if (input.value && index < 5) {
        inputs[index + 1].focus();
    }
}

async function solicitarCodigo() {
    userEmail = document.getElementById('email').value.trim();
    if (!userEmail) return showAlert('Informe um e-mail válido.');

    clearAlert();
    const btn = document.getElementById('btnSend');
    btn.disabled = true;
    btn.innerText = 'Enviando e-mail...';

    try {
        const response = await fetch('gerar_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: userEmail })
        });
        const data = await response.json();

        if (response.ok) {
            document.getElementById('step1').classList.remove('active');
            document.getElementById('step2').classList.add('active');
            document.getElementById('step2Subtitle').innerText = `Código enviado para ${userEmail}`;
        } else {
            showAlert(data.error || 'Erro ao enviar código.');
        }
    } catch (e) {
        showAlert('Erro de conexão com o servidor PHP.');
    } finally {
        btn.disabled = false;
        btn.innerText = 'Enviar Código';
    }
}

async function validarCodigo() {
    const inputs = document.querySelectorAll('.otp-input');
    let codigo = '';
    inputs.forEach(i => codigo += i.value);

    if (codigo.length < 6) return showAlert('Digite os 6 dígitos do código.');

    clearAlert();
    const btn = document.getElementById('btnVerify');
    btn.disabled = true;
    btn.innerText = 'Validando...';

    try {
        const response = await fetch('validar_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: userEmail, codigo: codigo })
        });
        const data = await response.json();

        if (response.ok && data.success) {
            window.location.href = '../../controllers/cadastrar.php';
        } else {
            showAlert(data.error || 'Código incorreto.');
        }
    } catch (e) {
        showAlert('Erro de conexão ao validar.');
    } finally {
        btn.disabled = false;
        btn.innerText = 'Confirmar e Entrar';
    }
}

function voltarPasso1() {
    clearAlert();
    document.getElementById('step2').classList.remove('active');
    document.getElementById('step1').classList.add('active');
}