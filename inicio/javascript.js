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
    waitForAllResources().then(hideLoader);
});

function waitForAllResources() {
    return Promise.all([
        // 1. Espera imagens carregarem
        waitForImages(),

        // 2. Espera fontes carregarem
        waitForFonts(),

        // 3. Espera window.load (todos recursos)
        waitForWindowLoad(),

        // 4. Espera CSS customizado carregar (se usar)
        waitForCustomCSS()
    ]);
}

function waitForImages() {
    return new Promise((resolve) => {
        const images = document.querySelectorAll('img');
        if (images.length === 0) return resolve();

        let loadedCount = 0;

        images.forEach((img, index) => {
            if (img.complete && img.naturalHeight !== 0) {
                loadedCount++;
            } else {
                img.onload = img.onerror = () => {
                    loadedCount++;
                    if (loadedCount === images.length) resolve();
                };
            }
        });

        // Se todas já carregaram
        if (loadedCount === images.length) resolve();
    });
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

function waitForCustomCSS() {
    // Para CSS carregado via JS ou link externo
    return new Promise((resolve) => {
        const links = document.querySelectorAll('link[rel="stylesheet"]');
        let loadedCount = 0;

        if (links.length === 0) return resolve();

        links.forEach(link => {
            if (link.sheet) {
                loadedCount++;
            } else {
                link.onload = () => {
                    loadedCount++;
                    if (loadedCount === links.length) resolve();
                };
                link.onerror = () => {
                    loadedCount++;
                    if (loadedCount === links.length) resolve();
                };
            }
        });

        if (loadedCount === links.length) resolve();
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