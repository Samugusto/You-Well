// Gráficos do painel com dados reais.
// - Página inicial: área com as últimas medições de cada setor e pizza com a
//   média das três medidas (temperatura, ruído e qualidade do ar).
// - Páginas Temperatura, Ruído e Qualidade do ar: um par de gráficos por setor
//   (histórico das últimas leituras e distribuição por faixa).
// Os dados chegam do PHP (window.dadosGraficosIniciais) e são atualizados a cada 10 minutos.
document.addEventListener("DOMContentLoaded", () => {

    const INTERVALO_ATUALIZACAO = 10 * 60 * 1000; // 10 minutos

    const COR_PRINCIPAL = '#1e9672';
    const COR_FUNDO = 'rgba(30, 150, 114, 0.25)';
    const CORES_SENSORES = {
        temperatura: { borda: '#1e9672', fundo: 'rgba(30, 150, 114, 0.25)' },
        ruido: { borda: '#044d37', fundo: 'rgba(4, 77, 55, 0.20)' },
        qualidade_ar: { borda: '#36bd91', fundo: 'rgba(54, 189, 145, 0.20)' }
    };
    const CORES_FAIXAS = { baixo: '#1c6fbf', estavel: COR_PRINCIPAL, alto: '#bf1c1c' };
    const SENSORES = ['temperatura', 'ruido', 'qualidade_ar'];

    let dados = window.dadosGraficosIniciais || null;
    const graficos = [];

    // ======================================================
    // FUNÇÕES AUXILIARES
    // ======================================================

    function getMedColors(values) {
        if (values.length === 0) return [];
        const menorValor = Math.min(...values);
        const maiorValor = Math.max(...values);
        const intervalo = maiorValor - menorValor || 1;

        return values.map(valor => {
            const proporcao = (valor - menorValor) / intervalo;
            const luminosidade = 78 - (proporcao * 58);
            return `hsl(160, 75%, ${luminosidade}%)`;
        });
    }

    function limite(sensor) {
        return (dados && dados.limites && dados.limites[sensor]) || { nome: sensor, unidade: '' };
    }

    function formatar(valor, sensor) {
        if (valor === null || valor === undefined) return 'Aguardando';
        const casas = sensor === 'qualidade_ar' || sensor === 'ruido' ? 0 : 1;
        return Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas });
    }

    function formatarHora(dataHora) {
        const data = new Date(String(dataHora).replace(' ', 'T'));
        if (isNaN(data)) return dataHora;
        return data.toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
    }

    function buscarSetor(idSetor) {
        return ((dados && dados.setores) || []).find(s => String(s.id_setor) === String(idSetor)) || null;
    }

    function tituloGrafico(texto) {
        return { display: true, text: texto };
    }

    // ======================================================
    // CONFIGURAÇÃO DE CADA TIPO DE GRÁFICO
    // ======================================================

    // Página inicial — área: última medição de cada sensor em cada setor.
    // A qualidade do ar (ppm) usa um eixo próprio à direita por ter outra escala.
    function configInicioArea() {
        const setores = (dados && dados.setores) || [];
        return {
            labels: setores.map(s => s.nome),
            datasets: SENSORES.map(sensor => ({
                label: `${limite(sensor).nome} (${limite(sensor).unidade})`,
                data: setores.map(s => s.atual[sensor]),
                backgroundColor: CORES_SENSORES[sensor].fundo,
                borderColor: CORES_SENSORES[sensor].borda,
                borderWidth: 3,
                fill: true,
                yAxisID: sensor === 'qualidade_ar' ? 'yAr' : 'y'
            }))
        };
    }

    // Página inicial — pizza: média das três medidas entre os setores.
    function configInicioPizza() {
        const medias = (dados && dados.medias) || {};
        const sensores = SENSORES.filter(sensor => medias[sensor] !== null && medias[sensor] !== undefined);
        const valores = sensores.map(sensor => Number(Number(medias[sensor]).toFixed(1)));
        return {
            labels: sensores.map(sensor => `${limite(sensor).nome} (${limite(sensor).unidade})`),
            datasets: [{
                label: 'Média dos setores',
                data: valores,
                backgroundColor: getMedColors(valores),
                borderColor: 'white',
                borderWidth: 2
            }]
        };
    }

    // Páginas de sensor — área: últimas leituras do setor.
    function configSensorArea(idSetor, sensor) {
        const setor = buscarSetor(idSetor);
        const historico = setor ? setor.historico[sensor] : [];
        return {
            labels: historico.map(l => formatarHora(l.data_hora)),
            datasets: [{
                label: `${limite(sensor).nome} (${limite(sensor).unidade})`,
                data: historico.map(l => l.valor),
                backgroundColor: COR_FUNDO,
                borderColor: COR_PRINCIPAL,
                borderWidth: 3,
                fill: true
            }]
        };
    }

    // Páginas de sensor — pizza: quantas leituras ficaram baixas, estáveis ou altas.
    function configSensorPizza(idSetor, sensor) {
        const setor = buscarSetor(idSetor);
        const distribuicao = setor ? setor.distribuicao[sensor] : { baixo: 0, estavel: 0, alto: 0 };
        const faixas = [
            { chave: 'baixo', nome: 'Baixa' },
            { chave: 'estavel', nome: 'Estável' },
            { chave: 'alto', nome: 'Alta' }
        ].filter(f => f.chave !== 'baixo' || limite(sensor).minimo !== null);

        return {
            labels: faixas.map(f => f.nome),
            datasets: [{
                label: 'Leituras',
                data: faixas.map(f => distribuicao[f.chave] || 0),
                backgroundColor: faixas.map(f => CORES_FAIXAS[f.chave]),
                borderColor: 'white',
                borderWidth: 2
            }]
        };
    }

    function montarDados(canvas) {
        const tipo = canvas.dataset.grafico;
        const { setorId, sensor } = canvas.dataset;
        if (tipo === 'inicio-area') return configInicioArea();
        if (tipo === 'inicio-pizza') return configInicioPizza();
        if (tipo === 'sensor-area') return configSensorArea(setorId, sensor);
        return configSensorPizza(setorId, sensor);
    }

    function semDados(dadosGrafico) {
        return dadosGrafico.datasets.every(ds => ds.data.every(v => v === null || v === 0)) || dadosGrafico.labels.length === 0;
    }

    function opcoes(canvas) {
        const tipo = canvas.dataset.grafico;
        const sensor = canvas.dataset.sensor;
        const legenda = { display: true, position: 'bottom' };

        if (tipo === 'inicio-area') {
            return {
                plugins: { legend: legenda, title: tituloGrafico('Últimas medições de cada setor') },
                scales: {
                    y: { position: 'left', beginAtZero: true, title: { display: true, text: '°C / dB' } },
                    yAr: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, title: { display: true, text: 'ppm' } }
                }
            };
        }
        if (tipo === 'inicio-pizza') {
            return {
                plugins: {
                    legend: { ...legenda, labels: { usePointStyle: true, padding: 20 } },
                    title: tituloGrafico('Média das três medidas'),
                    tooltip: { callbacks: { label: ctx => ` Média: ${ctx.formattedValue}` } }
                }
            };
        }
        if (tipo === 'sensor-area') {
            return {
                plugins: { legend: legenda, title: tituloGrafico(`${limite(sensor).nome}: últimas leituras`) },
                scales: { y: { beginAtZero: sensor !== 'temperatura' } }
            };
        }
        return {
            plugins: {
                legend: { ...legenda, labels: { usePointStyle: true, padding: 20 } },
                title: tituloGrafico('Distribuição das últimas leituras')
            }
        };
    }

    // Mostra um aviso sobre o canvas quando ainda não há leituras.
    function atualizarAvisoSemDados(canvas, vazio) {
        let aviso = canvas.parentElement.querySelector('.grafico-sem-dados');
        if (vazio && !aviso) {
            aviso = document.createElement('p');
            aviso.className = 'grafico-sem-dados aparencia-descricao';
            aviso.textContent = 'Aguardando medições do ESP32.';
            canvas.parentElement.appendChild(aviso);
        } else if (!vazio && aviso) {
            aviso.remove();
        }
    }

    // ======================================================
    // CRIAÇÃO E ATUALIZAÇÃO
    // ======================================================

    function criarGraficos() {
        if (typeof Chart === 'undefined') return;
        document.querySelectorAll('canvas[data-grafico]').forEach(canvas => {
            const tipo = canvas.dataset.grafico;
            const dadosGrafico = montarDados(canvas);
            const grafico = new Chart(canvas, {
                type: tipo.endsWith('pizza') ? 'pie' : 'line',
                data: dadosGrafico,
                options: opcoes(canvas)
            });
            graficos.push({ canvas, grafico });
            atualizarAvisoSemDados(canvas, semDados(dadosGrafico));
        });
    }

    function atualizarResumo() {
        const medias = (dados && dados.medias) || {};
        document.querySelectorAll('[data-resumo]').forEach(elemento => {
            const sensor = elemento.dataset.resumo;
            const valor = medias[sensor];
            if (valor === null || valor === undefined) {
                elemento.textContent = 'Aguardando';
            } else if (sensor === 'temperatura') {
                elemento.textContent = `${formatar(valor, sensor)}°`;
            } else if (sensor === 'ruido') {
                elemento.textContent = `${formatar(valor, sensor)} db`;
            } else {
                const maximo = limite(sensor).maximo;
                elemento.textContent = maximo !== null && valor > maximo ? 'Ruim' : 'Boa';
            }
        });
    }

    function atualizarGraficos() {
        graficos.forEach(({ canvas, grafico }) => {
            const dadosGrafico = montarDados(canvas);
            grafico.data.labels = dadosGrafico.labels;
            grafico.data.datasets = dadosGrafico.datasets;
            grafico.update();
            atualizarAvisoSemDados(canvas, semDados(dadosGrafico));
        });
        atualizarResumo();
    }

    async function buscarDados() {
        const url = document.body.dataset.graficosUrl;
        if (!url) return;
        try {
            const resposta = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
            if (resposta.status === 401) {
                window.location.reload(); // sessão expirou: volta para o login
                return;
            }
            const json = await resposta.json();
            if (json && json.success) {
                dados = json;
                atualizarGraficos();
            }
        } catch (erro) {
            console.error('Não foi possível atualizar os gráficos:', erro);
        }
    }

    if (document.querySelector('canvas[data-grafico]')) {
        criarGraficos();
        setInterval(buscarDados, INTERVALO_ATUALIZACAO);
    }
});
