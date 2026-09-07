// Espera toda a página carregar antes de executar o código
document.addEventListener("DOMContentLoaded", () => {

    // ======================================================
    // DADOS INICIAIS
    // ======================================================

    // Array com os dados ambientais
    // Cada objeto representa um local
    let dados = [
        {
            local: "Setor 1",
            temp: 28,
            ar: 70,
            ruido: 60,
            media: 40
        },
        {
            local: "Setor 2",
            temp: 25,
            ar: 90,
            ruido: 40,
            media: 30
        },
        {
            local: "setor 3",
            temp: 30,
            ar: 50,
            ruido: 80,
            media: 50
        },

        {
            local: "setor 4",
            temp: 30,
            ar: 50,
            ruido: 80,
            media: 20
        },
        {
            local: "setor 5",
            temp: 30,
            ar: 50,
            ruido: 80,
            media: 90
        },
        {
            local: "setor 6",
            temp: 30,
            ar: 50,
            ruido: 80,
            media: 60
        },
        {
            local: "setor 7",
            temp: 30,
            ar: 50,
            ruido: 80,
            media: 98
        }
    ];

    // ======================================================
    // FUNÇÕES PARA PEGAR DADOS
    // ======================================================

    // Pega somente os nomes dos locais
    // Exemplo: ["Centro", "Parque", "Industrial"]
    function getLabels() {
        return dados.map(d => d.local);
    }

    // Pega somente as temperaturas
    // Exemplo: [28, 25, 30]
    function getTemp() {
        return dados.map(d => d.temp);
    }

    // Pega somente a qualidade do ar
    // Exemplo: [70, 90, 50]
    function getAr() {
        return dados.map(d => d.ar);
    }

    // Pega somente os níveis de ruído
    // Exemplo: [60, 40, 80]
    function getRuido() {
        return dados.map(d => d.ruido);
    }

    function getMed() {
        return dados.map(d => d.media);
    }

    function getMedColors(values) {
        const menorValor = Math.min(...values);
        const maiorValor = Math.max(...values);
        const intervalo = maiorValor - menorValor || 1;

        return values.map(valor => {
            const proporcao = (valor - menorValor) / intervalo;
            const luminosidade = 78 - (proporcao * 58);

            return `hsl(160, 75%, ${luminosidade}%)`;
        });
    }


    // ======================================================
    // PEGANDO O CANVAS DOS GRÁFICOS
    // ======================================================

    // Seleciona o canvas do gráfico de pizza
    const ctxPizza = document.getElementById('pizza').getContext('2d');
    const ctxArea = document.getElementById('area').getContext('2d');
    // ======================================================
    // GRÁFICO DE PIZZA
    // ======================================================

    const pizza = new Chart(ctxPizza, {
        type: 'pie',

        data: {
            labels: getLabels(),

            datasets: [{
                label: 'Média dos setores',
                data: getMed(),
                backgroundColor: getMedColors(getMed()),
                borderColor: 'white',
                borderWidth: 2
            }]
        },

        options: {
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                title: {
                    display: true,
                    text: 'Média de cada setor'
                }
            }
        }
    });

    const area = new Chart(ctxArea, {

        // Tipo linha
        type: 'line',

        data: {

            labels: getLabels(),

            datasets: [{

                label: 'Média de todos os setores',

                data: getMed(),

                backgroundColor: 'rgba(30, 150, 114, 0.25)',
                borderColor: '#1e9672',
                borderWidth: 3,

                // Preenche a área abaixo da linha
                fill: true
            }]
        },
        options: {
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Média de cada função registrada em cada setor'
                }
            }
        }
    });

    // ======================================================
    // FUNÇÃO PARA ATUALIZAR OS GRÁFICOS
    // ======================================================

    function atualizarGraficos() {

        // Atualiza gráfico pizza
        pizza.data.labels = getLabels();
        pizza.data.datasets[0].data = getMed();
        pizza.data.datasets[0].backgroundColor = getMedColors(getMed());
        pizza.update();

        // Atualiza gráfico área
        area.data.labels = getLabels();
        area.data.datasets[0].data = getMed();
        area.update();
    }
});