// Gera o relatório de alertas dos últimos 30 dias em PDF (jsPDF + AutoTable).
// Os dados vêm de controllers/relatorio_alertas.php.
document.addEventListener("DOMContentLoaded", () => {
    const botao = document.querySelector('[data-relatorio-url]');
    if (!botao) return;

    const VERDE = [30, 150, 114];
    const VERDE_ESCURO = [4, 77, 55];
    const VERMELHO_CLARO = [251, 228, 228];
    const CINZA = [110, 110, 110];
    const MARGEM = 14;

    let dados = null;

    // ======================================================
    // FORMATAÇÃO
    // ======================================================

    function data(valor, comHora = true) {
        if (!valor) return '-';
        const d = new Date(String(valor).replace(' ', 'T'));
        if (isNaN(d)) return valor;
        const opcoes = { day: '2-digit', month: '2-digit', year: 'numeric' };
        if (comHora) Object.assign(opcoes, { hour: '2-digit', minute: '2-digit' });
        return d.toLocaleString('pt-BR', opcoes);
    }

    function numero(valor, casas = 1) {
        if (valor === null || valor === undefined) return '-';
        return Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas });
    }

    function nomeSensor(sensor) {
        return dados.limites[sensor].nome;
    }

    function unidade(sensor) {
        return dados.limites[sensor].unidade;
    }

    function valorComUnidade(valor, sensor) {
        if (valor === null || valor === undefined) return '-';
        const casas = sensor === 'qualidade_ar' ? 0 : 1;
        return `${numero(valor, casas)} ${unidade(sensor)}`;
    }

    function faixa(sensor) {
        const { minimo, maximo } = dados.limites[sensor];
        const u = unidade(sensor);
        if (minimo !== null && maximo !== null) return `acima de ${numero(minimo, 0)} até ${numero(maximo, 0)} ${u}`;
        if (maximo !== null) return `até ${numero(maximo, 0)} ${u}`;
        return `acima de ${numero(minimo, 0)} ${u}`;
    }

    function situacao(texto) {
        // "Crítico: Temperatura do setor alta: 40,0°C" -> "Alta"
        const partes = String(texto).split(':');
        const meio = partes.length > 1 ? partes[1].trim().split(' ').pop() : texto;
        return meio.charAt(0).toUpperCase() + meio.slice(1);
    }

    // ======================================================
    // MONTAGEM DO PDF
    // ======================================================

    // Se não houver espaço no fim da página, continua na próxima.
    function garantirEspaco(doc, y, necessario = 30) {
        if (y + necessario > doc.internal.pageSize.getHeight() - 15) {
            doc.addPage();
            return 30;
        }
        return y;
    }

    function titulo(doc, texto, y) {
        y = garantirEspaco(doc, y);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(13);
        doc.setTextColor(...VERDE_ESCURO);
        doc.text(texto, MARGEM, y);
        doc.setDrawColor(...VERDE);
        doc.setLineWidth(0.5);
        doc.line(MARGEM, y + 2, doc.internal.pageSize.getWidth() - MARGEM, y + 2);
        return y + 8;
    }

    function texto(doc, conteudo, y, opcoes = {}) {
        y = garantirEspaco(doc, y, opcoes.negrito ? 30 : 10);
        doc.setFont('helvetica', opcoes.negrito ? 'bold' : 'normal');
        doc.setFontSize(opcoes.tamanho || 10);
        doc.setTextColor(...(opcoes.cor || [40, 40, 40]));
        doc.text(conteudo, MARGEM, y);
        return y + (opcoes.espaco || 6);
    }

    function tabela(doc, y, head, body, extra = {}) {
        doc.autoTable({
            startY: y,
            head: [head],
            body,
            theme: 'grid',
            margin: { left: MARGEM, right: MARGEM, top: 28 },
            styles: { font: 'helvetica', fontSize: 9, cellPadding: 2, textColor: [40, 40, 40] },
            headStyles: { fillColor: VERDE, textColor: 255, fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [244, 250, 248] },
            ...extra
        });
        return doc.lastAutoTable.finalY + 8;
    }

    function cabecalho(doc) {
        const largura = doc.internal.pageSize.getWidth();
        doc.setFillColor(...VERDE);
        doc.rect(0, 0, largura, 20, 'F');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(15);
        doc.setTextColor(255, 255, 255);
        doc.text('YouWell - Relatório de Alertas', MARGEM, 13);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        doc.text(`Últimos ${dados.dias} dias`, largura - MARGEM, 13, { align: 'right' });
    }

    function rodapes(doc) {
        const total = doc.getNumberOfPages();
        const largura = doc.internal.pageSize.getWidth();
        const altura = doc.internal.pageSize.getHeight();
        for (let i = 1; i <= total; i++) {
            doc.setPage(i);
            if (i > 1) cabecalho(doc);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8);
            doc.setTextColor(...CINZA);
            doc.text(`Gerado por ${dados.gerado_por.nome} (${dados.gerado_por.papel}) em ${data(dados.gerado_em)}`, MARGEM, altura - 8);
            doc.text(`Página ${i} de ${total}`, largura - MARGEM, altura - 8, { align: 'right' });
        }
    }

    function paginaResumo(doc) {
        cabecalho(doc);
        let y = 30;

        y = titulo(doc, 'Informações do relatório', y);
        y = tabela(doc, y, ['Campo', 'Informação'], [
            ['Gerado por', `${dados.gerado_por.nome} (${dados.gerado_por.papel})`],
            ['Data de geração', data(dados.gerado_em)],
            ['Período analisado', `${data(dados.periodo.inicio)} até ${data(dados.periodo.fim)}`],
            ['Setores incluídos', String(dados.resumo.setores)],
            ['Total de leituras', dados.resumo.leituras.toLocaleString('pt-BR')],
            ['Total de alertas críticos', dados.resumo.criticos.toLocaleString('pt-BR')],
            ['Setores com alertas críticos', String(dados.resumo.setores_com_alerta)]
        ], { columnStyles: { 0: { fontStyle: 'bold', cellWidth: 60 } } });

        y = titulo(doc, 'Faixas consideradas normais', y);
        y = tabela(doc, y, ['Sensor', 'Faixa estável', 'Fora da faixa'],
            Object.keys(dados.limites).map(sensor => [nomeSensor(sensor), faixa(sensor), 'Alerta crítico']));

        y = titulo(doc, 'Resumo por setor', y);
        const sensores = Object.keys(dados.limites);
        if (dados.setores.length === 0) {
            texto(doc, 'Nenhum setor disponível para este usuário.', y);
            return;
        }
        tabela(doc, y,
            ['Setor', 'Responsável', 'Leituras', ...sensores.map(s => `${nomeSensor(s)} (críticos)`), 'Total críticos'],
            dados.setores.map(setor => [
                setor.nome,
                setor.responsavel || '-',
                sensores.reduce((t, s) => t + setor.sensores[s].leituras, 0).toLocaleString('pt-BR'),
                ...sensores.map(s => String(setor.sensores[s].altas + setor.sensores[s].baixas)),
                String(setor.total_criticos)
            ]),
            {
                didParseCell: info => {
                    if (info.section === 'body' && dados.setores[info.row.index].total_criticos > 0) {
                        info.cell.styles.fillColor = VERMELHO_CLARO;
                    }
                }
            });
    }

    function paginaSetor(doc, setor) {
        doc.addPage();
        let y = 30;
        const sensores = Object.keys(dados.limites);

        y = titulo(doc, `Setor: ${setor.nome}`, y);
        y = texto(doc, `Responsável: ${setor.responsavel || '-'}    |    Alertas críticos no período: ${setor.total_criticos}`, y, { espaco: 8 });

        // Resumo do período por sensor.
        y = texto(doc, 'Resumo do período por sensor', y, { negrito: true, tamanho: 11 });
        y = tabela(doc, y, ['Sensor', 'Leituras', 'Mínimo', 'Média', 'Máximo', 'Altas', 'Baixas'],
            sensores.map(s => {
                const r = setor.sensores[s];
                return [nomeSensor(s), String(r.leituras), valorComUnidade(r.minimo, s), valorComUnidade(r.media, s), valorComUnidade(r.maximo, s), String(r.altas), String(r.baixas)];
            }));

        // Resumo por dia.
        y = texto(doc, 'Resumo por dia', y, { negrito: true, tamanho: 11 });
        if (setor.dias.length === 0) {
            y = texto(doc, 'Nenhuma leitura recebida no período.', y, { cor: CINZA, espaco: 10 });
        } else {
            y = tabela(doc, y, ['Data', 'Sensor', 'Leituras', 'Mínimo', 'Média', 'Máximo', 'Alertas críticos'],
                setor.dias.map(d => [data(d.dia, false), nomeSensor(d.sensor), String(d.leituras), valorComUnidade(d.minimo, d.sensor), valorComUnidade(d.media, d.sensor), valorComUnidade(d.maximo, d.sensor), String(d.altas + d.baixas)]),
                {
                    didParseCell: info => {
                        const dia = setor.dias[info.row.index];
                        if (info.section === 'body' && dia && dia.altas + dia.baixas > 0) info.cell.styles.fillColor = VERMELHO_CLARO;
                    }
                });
        }

        // Lista dos alertas críticos.
        y = texto(doc, 'Alertas críticos (mais recentes primeiro)', y, { negrito: true, tamanho: 11 });
        if (setor.criticos.length === 0) {
            texto(doc, 'Nenhum alerta crítico no período.', y, { cor: CINZA });
            return;
        }
        y = tabela(doc, y, ['Data e hora', 'Sensor', 'Valor', 'Situação', 'Alerta'],
            setor.criticos.map(c => [data(c.data_hora), nomeSensor(c.sensor), valorComUnidade(c.valor, c.sensor), situacao(c.texto), c.texto]),
            { columnStyles: { 4: { cellWidth: 70 } } });
        if (setor.criticos_omitidos > 0) {
            texto(doc, `+ ${setor.criticos_omitidos} alertas críticos mais antigos não listados (a tabela "Resumo por dia" inclui todos).`, y, { cor: CINZA, tamanho: 9 });
        }
    }

    function gerarPdf() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ unit: 'mm', format: 'a4' });
        paginaResumo(doc);
        dados.setores.forEach(setor => paginaSetor(doc, setor));
        rodapes(doc);
        const dia = String(dados.gerado_em || '').slice(0, 10) || new Date().toISOString().slice(0, 10);
        doc.save(`relatorio-alertas-${dia}.pdf`);
    }

    // ======================================================
    // BOTÃO
    // ======================================================

    botao.addEventListener('click', async () => {
        if (!window.jspdf || !window.jspdf.jsPDF) {
            alert('Não foi possível carregar o gerador de PDF. Verifique sua conexão e tente novamente.');
            return;
        }

        const rotulo = botao.querySelector('span');
        const textoOriginal = rotulo ? rotulo.textContent : '';
        botao.disabled = true;
        if (rotulo) rotulo.textContent = 'Gerando...';

        try {
            const resposta = await fetch(botao.dataset.relatorioUrl, { credentials: 'same-origin', cache: 'no-store' });
            if (resposta.status === 401) {
                window.location.reload();
                return;
            }
            const json = await resposta.json();
            if (!json.success) throw new Error(json.mensagem || 'Erro ao gerar relatório.');
            dados = json;
            gerarPdf();
        } catch (erro) {
            console.error(erro);
            alert('Não foi possível gerar o relatório agora. Tente novamente.');
        } finally {
            botao.disabled = false;
            if (rotulo) rotulo.textContent = textoOriginal;
        }
    });
});
