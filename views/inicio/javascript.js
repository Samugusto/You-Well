// === LOADING REAL - ESPERA TUDO CARREGAR ===
document.addEventListener('DOMContentLoaded', function () {
    // BLOQUEIA IMEDIATAMENTE TUDO
    document.body.classList.add('loading-locked');
    document.getElementById('header').classList.add('loading-hidden');

    // PREVINE EVENTOS
    const scrollEvents = ['wheel', 'touchmove', 'keydown'];
    scrollEvents.forEach(event => {
        document.addEventListener(event, preventScroll, { passive: false });
    });

    // DESATIVA OBSERVERS DURANTE LOADING
    window.loadingObservers = window.loadingObservers || [];

    // ✅ ESPERA TUDO CARREGAR DE VERDADE
    waitForAllResources()
        .then(hideLoader)
        .catch((error) => {
            console.error('Não foi possível aguardar todos os recursos da página:', error);
            hideLoader();
        });
});

function waitForAllResources() {
    let timeoutId;
    const recursosCarregados = Promise.all([
        waitForImages(),
        waitForFonts(),
        waitForWindowLoad()
    ]);

    const limiteDeEspera = new Promise((resolve) => {
        timeoutId = window.setTimeout(() => {
            console.warn('O carregamento excedeu o tempo limite; exibindo a página mesmo assim.');
            resolve();
        }, 10000);
    });

    return Promise.race([recursosCarregados, limiteDeEspera])
        .finally(() => window.clearTimeout(timeoutId));
}

function waitForImages() {
    const images = Array.from(document.querySelectorAll('img'));

    return Promise.all(images.map((img) => new Promise((resolve) => {
        if (img.complete) {
            resolve();
            return;
        }

        const settle = () => {
            img.removeEventListener('load', settle);
            img.removeEventListener('error', settle);
            resolve();
        };

        img.addEventListener('load', settle, { once: true });
        img.addEventListener('error', settle, { once: true });

        if (img.complete) settle();
    })));
}

function waitForFonts() {
    return document.fonts ?
        document.fonts.ready :
        Promise.resolve();
}

function waitForWindowLoad() {
    return new Promise((resolve) => {
        if (document.readyState === 'complete') {
            resolve();
        } else {
            window.addEventListener('load', resolve, { once: true });
        }
    });
}

function preventScroll(e) {
    e.preventDefault();
    return false;
}

function hideLoader() {
    const loader = document.getElementById('loader');

    // DESBLOQUEIA
    document.body.classList.remove('loading-locked');
    document.getElementById('header').classList.remove('loading-hidden');

    // Remove bloqueios
    ['wheel', 'touchmove', 'keydown'].forEach(event => {
        document.removeEventListener(event, preventScroll);
    });

    // Fade out loader
    loader.classList.add('hidden');

    setTimeout(() => {
        loader.style.display = 'none';
        loader.remove();


        reactivateAnimations();
    }, 800);
}

function reactivateAnimations() {
    initHeaderObserver();
    initMotivadorObserver();
    initScrollPrevent();
}

function initHeaderObserver() {
    const header = document.getElementById("header");
    const fundo = document.querySelector(".fundo");

    if (fundo && header) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    header.style.top = "-90px";
                } else {
                    header.style.top = "0px";
                }
            });
        }, { threshold: 0.1 });

        observer.observe(fundo);
    }
}

function initMotivadorObserver() {
    const motivadorObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('ativo');

                const text3 = entry.target.querySelector('.text3');
                if (text3) {
                    const estilos = getComputedStyle(entry.target);
                    const duracao = converterTempoParaMs(estilos.transitionDuration);
                    const atraso = converterTempoParaMs(estilos.transitionDelay);

                    setTimeout(() => {
                        text3.classList.add('esticar-ativo');
                    }, duracao + atraso);
                }
                const destaque = entry.target.querySelector('.destaque');
                if (destaque) {
                    const estilos = getComputedStyle(entry.target);
                    const duracao = converterTempoParaMs(estilos.transitionDuration);
                    const atraso = converterTempoParaMs(estilos.transitionDelay);

                    setTimeout(() => {
                        destaque.classList.add('esticar-ativo');
                    }, duracao + atraso);
                }

                setTimeout(() => {
                    entry.target.style.transitionDelay = "0s";
                }, 600);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll(".motivador, .motivador2").forEach(el => {
        motivadorObserver.observe(el);
    });

    const elementos = document.querySelectorAll(
        ".botao-animado, .botao-animado-direita, .botao-animado-esquerda"
    );

    elementos.forEach((el, index) => {
        el.style.transitionDelay = `${index * 0.2}s`;
        motivadorObserver.observe(el);
    });
}

function converterTempoParaMs(tempo) {
    const valor = parseFloat(tempo);
    return tempo.includes('ms') ? valor : valor * 1000;
}

function initScrollPrevent() {
    window.addEventListener('wheel', function (e) {
        if (Math.abs(e.deltaX) > 0) {
            e.preventDefault();
        }
    }, { passive: false });
}
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = this.getAttribute('href');
        lenis.scrollTo(target, {
            duration: 1.5,
            offset: -50,
            immediate: false,
            lock: true
        });
    });
});
gsap.registerPlugin(ScrollTrigger);

const lenis = new Lenis({
    duration: 1.2, // Tempo da animação da rolagem
    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)), // Curva de suavização
    smoothWheel: true
});

// Sincronizar o Lenis com o ScrollTrigger do GSAP
lenis.on('scroll', ScrollTrigger.update);

// Alimentar o "ticker" do GSAP com a animação do Lenis
gsap.ticker.add((time) => {
    lenis.raf(time * 1000);
});

// Desativar a suavização de lag do GSAP para evitar conflitos
gsap.ticker.lagSmoothing(0);

document.addEventListener('DOMContentLoaded', () => {
    const linhas = document.querySelectorAll('.linha-ondulada');

    if (!linhas.length) return;

    linhas.forEach((linha, index) => {
        const comprimentoTotal = linha.getTotalLength();
        linha.style.strokeDasharray = String(comprimentoTotal);
        linha.style.strokeDashoffset = String(comprimentoTotal);
        linha.style.animationDelay = `${index * 0.25}s`;

        requestAnimationFrame(() => {
            linha.classList.add('animar-desenho');
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.querySelector('.char-wave-canvas');
    if (!canvas) return;

    const container = canvas.parentElement;
    const ctx = canvas.getContext('2d');
    if (!container || !ctx) return;

    const config = {
        symbols: ['!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '_', '+', '{', '}', '[', ']'],
        speed: 1.5,
        frequency: 0.03,
        amplitude: 35,
        angle: 45,
        fontSize: 18,
        spacing: 22
    };

    let width = 0;
    let height = 0;
    let time = 0;

    const resizeCanvas = () => {
        const bounds = container.getBoundingClientRect();
        const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);

        width = bounds.width;
        height = bounds.height;
        canvas.width = Math.round(width * pixelRatio);
        canvas.height = Math.round(height * pixelRatio);
        ctx.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
    };

    const resizeObserver = new ResizeObserver(resizeCanvas);
    resizeObserver.observe(container);
    resizeCanvas();

    const angle = (config.angle * Math.PI) / 180;
    const cosAngle = Math.cos(angle);
    const sinAngle = Math.sin(angle);

    const animate = () => {
        time += config.speed * 0.02;
        ctx.clearRect(0, 0, width, height);
        ctx.font = `600 ${config.fontSize}px monospace`;
        ctx.textAlign = 'center';

        const columns = Math.ceil(width / config.spacing);
        const rows = Math.ceil(height / config.spacing);

        for (let row = 0; row < rows; row++) {
            for (let column = 0; column < columns; column++) {
                const x = column * config.spacing;
                const y = row * config.spacing;
                const phase = (x * cosAngle + y * sinAngle) * config.frequency - time;
                const wave = Math.sin(phase) * config.amplitude;
                const normalized = (wave + config.amplitude) / (config.amplitude * 2);
                const symbolIndex = Math.floor(normalized * (config.symbols.length - 1));

                ctx.fillStyle = `rgba(0, 240, 255, ${0.2 + normalized * 0.0})`;
                ctx.fillText(config.symbols[symbolIndex], x, y + wave * 0.5);
            }
        }

        requestAnimationFrame(animate);
    };

    animate();
});