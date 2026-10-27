<?php
session_start();
require_once __DIR__ . "/../config/conexao.php";

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../controllers/entrar.php");
    exit();
}

if ($_SESSION['papel'] != "gerente") {
    header("Location: ../controllers/entrar.php");
    exit();
}

$mensagemPerfil = '';
$tipoMensagemPerfil = '';
$mensagemSetor = '';
$tipoMensagemSetor = '';
$nomeSetorFormulario = '';
if (!isset($_SESSION['setores_painel']) || !is_array($_SESSION['setores_painel'])) {
    $_SESSION['setores_painel'] = [];
}
$abaAtiva = 'panel-informacoes';
$nomePerfilFormulario = (string) $_SESSION['nome'];
if (empty($_SESSION['token_configuracoes'])) {
    $_SESSION['token_configuracoes'] = bin2hex(random_bytes(32));
}

$con = conectar();
$idUsuario = (int) $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar_perfil') {
    $abaAtiva = 'panel-perfil';
    $nome = isset($_POST['nome']) && is_string($_POST['nome']) ? trim($_POST['nome']) : '';
    $nomePerfilFormulario = $nome;
    $token = isset($_POST['token_configuracoes']) && is_string($_POST['token_configuracoes'])
        ? $_POST['token_configuracoes']
        : '';
    $dadosFoto = null;
    $erroValidacao = '';
    $imagemCropada = isset($_POST['foto_perfil_cropada']) && is_string($_POST['foto_perfil_cropada'])
        ? $_POST['foto_perfil_cropada']
        : '';

    if (!hash_equals($_SESSION['token_configuracoes'], $token)) {
        $erroValidacao = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } elseif ($nome === '' || strlen($nome) > 255) {
        $erroValidacao = 'Informe um nome válido com até 255 bytes.';
    } elseif ($imagemCropada !== '') {
        if (!preg_match('#^data:image/(jpeg|png|webp);base64,([A-Za-z0-9+/]*={0,2})$#D', $imagemCropada, $partesImagem)) {
            $erroValidacao = 'A imagem ajustada não está em um formato válido.';
        } else {
            $dadosFoto = base64_decode($partesImagem[2], true);
            $infoImagem = $dadosFoto === false ? false : @getimagesizefromstring($dadosFoto);
            $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];

            if ($dadosFoto === false || strlen($dadosFoto) > 5 * 1024 * 1024) {
                $erroValidacao = 'A foto ajustada deve ter no máximo 5 MB.';
            } elseif (
                $infoImagem === false
                || !in_array($infoImagem['mime'], $tiposPermitidos, true)
                || $infoImagem['mime'] !== 'image/' . $partesImagem[1]
            ) {
                $erroValidacao = 'Selecione uma imagem válida nos formatos JPG, PNG ou WEBP.';
            }
        }
    }

    if ($erroValidacao === '') {
        $sql_nome_existente = "SELECT id_usuario FROM usuarios WHERE nome = ? AND id_usuario <> ?";
        $stmt_nome_existente = mysqli_prepare($con, $sql_nome_existente);
        mysqli_stmt_bind_param($stmt_nome_existente, "si", $nome, $idUsuario);
        mysqli_stmt_execute($stmt_nome_existente);
        mysqli_stmt_store_result($stmt_nome_existente);
        $nomeEmUso = mysqli_stmt_num_rows($stmt_nome_existente) > 0;
        mysqli_stmt_close($stmt_nome_existente);

        if ($nomeEmUso) {
            $erroValidacao = 'Este nome já está sendo usado por outra conta.';
        }
    }

    if ($erroValidacao === '') {
        if ($dadosFoto !== null) {
            $sql_atualizar = "UPDATE usuarios SET nome = ?, foto_perfil = ? WHERE id_usuario = ?";
            $stmt_atualizar = mysqli_prepare($con, $sql_atualizar);
            $fotoBanco = null;
            mysqli_stmt_bind_param($stmt_atualizar, "sbi", $nome, $fotoBanco, $idUsuario);
            mysqli_stmt_send_long_data($stmt_atualizar, 1, $dadosFoto);
        } else {
            $sql_atualizar = "UPDATE usuarios SET nome = ? WHERE id_usuario = ?";
            $stmt_atualizar = mysqli_prepare($con, $sql_atualizar);
            mysqli_stmt_bind_param($stmt_atualizar, "si", $nome, $idUsuario);
        }

        if (mysqli_stmt_execute($stmt_atualizar)) {
            $_SESSION['nome'] = $nome;
            $mensagemPerfil = 'Perfil atualizado com sucesso.';
            $tipoMensagemPerfil = 'sucesso';
        } else {
            $mensagemPerfil = 'Não foi possível salvar as alterações. Tente novamente.';
            $tipoMensagemPerfil = 'erro';
        }
        mysqli_stmt_close($stmt_atualizar);
    } else {
        $mensagemPerfil = $erroValidacao;
        $tipoMensagemPerfil = 'erro';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'criar_setor') {
    $abaAtiva = 'panel-setores';
    $nomeSetorFormulario = isset($_POST['nome_setor']) && is_string($_POST['nome_setor'])
        ? trim($_POST['nome_setor'])
        : '';

    if ($nomeSetorFormulario === '' || strlen($nomeSetorFormulario) > 255) {
        $mensagemSetor = 'Informe um nome válido com até 255 caracteres.';
        $tipoMensagemSetor = 'erro';
    } else {
        $nomeDuplicado = false;
        foreach ($_SESSION['setores_painel'] as $setorExistente) {
            if (strcasecmp($setorExistente['nome'], $nomeSetorFormulario) === 0) {
                $nomeDuplicado = true;
                break;
            }
        }

        if ($nomeDuplicado) {
            $mensagemSetor = 'Já existe um setor com esse nome.';
            $tipoMensagemSetor = 'erro';
        } else {
            $_SESSION['setores_painel'][] = [
                'id_setor' => count($_SESSION['setores_painel']) + 1,
                'nome' => $nomeSetorFormulario,
                'temperatura' => null,
                'ruido' => null,
                'qualidade_ar' => null,
            ];
            $mensagemSetor = 'Setor criado com sucesso.';
            $tipoMensagemSetor = 'sucesso';
            $nomeSetorFormulario = '';
        }
    }
}

$sql_usuario = "SELECT nome, foto_perfil FROM usuarios WHERE id_usuario = ?";
$stmt_usuario = mysqli_prepare($con, $sql_usuario);
mysqli_stmt_bind_param($stmt_usuario, "i", $idUsuario);
mysqli_stmt_execute($stmt_usuario);
$resultado_usuario = mysqli_stmt_get_result($stmt_usuario);
$usuario = mysqli_fetch_assoc($resultado_usuario);
mysqli_stmt_close($stmt_usuario);

$setores = $_SESSION['setores_painel'];
mysqli_close($con);

$fotoPerfil = null;
if ($usuario && !empty($usuario['foto_perfil'])) {
    $infoFoto = @getimagesizefromstring($usuario['foto_perfil']);
    if ($infoFoto !== false && in_array($infoFoto['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
        $fotoPerfil = 'data:' . $infoFoto['mime'] . ';base64,' . base64_encode($usuario['foto_perfil']);
    }
}
if ($usuario && isset($usuario['nome'])) {
    $_SESSION['nome'] = $usuario['nome'];
}

$paginas = ['inicio', 'setores', 'temperatura', 'ruido', 'qualidadear', 'alertas', 'configuracoes'];
$pagina = $_GET['pagina'] ?? 'inicio';
$semLoader = ($_GET['sem_loader'] ?? null) === '1';

if (!in_array($pagina, $paginas, true)) {
    $pagina = 'inicio';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouWell - DashBoard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;600&display=swap" rel="stylesheet">
    <link href="../public/assets/img/Sem título.png" rel="icon">
    <link href="../public/assets/css/styleP.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/assets/css/loader.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../public/assets/js/graficos.js"></script>
    <script src="../public/assets/js/menu.js?v=4"></script>
</head>

<body>
    <?php if (!$semLoader): ?>
        <div id="loader" role="status" aria-live="polite">
            <div class="loader-content">
                <div class="loader-emblem">
                    <span class="loader-ring" aria-hidden="true"></span>
                    <img src="../public/assets/img/Sem título2.png" alt="" class="spinner-img">
                </div>
                <p class="loader-label">Preparando sua experiência<span class="loader-dots" aria-hidden="true"></span></p>
            </div>
        </div>
    <?php endif; ?>

    <header id="header">
        <div class="user">
            <button class="menu-toggle" type="button" aria-label="Esconder menu" aria-expanded="true">
                <i class="bi bi-layout-sidebar-inset"></i>
            </button>

            <button class="menu-toggle" type="button" aria-label="Esconder menu" aria-expanded="true">
                <i class="bi bi-bell"></i>
            </button>
            <div class="infos">
                <h1 class="nome"><?php echo htmlspecialchars((string) $_SESSION['nome'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <span class="papel-usuario">
                    <?php echo htmlspecialchars($_SESSION['papel'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>
            <?php if (!empty($fotoPerfil)): ?>
                <img src="<?php echo $fotoPerfil; ?>" class="perfil" alt="Foto do perfil">
            <?php else: ?>
                <img src="../public/assets/img/PFP.png" class="perfil" alt="Sem foto de perfil">
            <?php endif; ?>
        </div>
    </header>

    <nav class="menu-lateral">
        <ul>
            <div class="Logo">
                <img src="../public/assets/img/Sem título2.png" class="logo-menu">
                <h1 class="tituloY">You<span class="well">Well</span></h1>
            </div>
            <li><a href="../controllers/painel.php?pagina=inicio&amp;sem_loader=1" class="underline-desliza <?= $pagina === 'inicio' ? 'active' : '' ?>"><i class="bi bi-house-fill"></i>Início</a></li>

            <li><a href="../controllers/painel.php?pagina=setores&amp;sem_loader=1" class="underline-desliza <?= $pagina === 'setores' ? 'active' : '' ?>"><i class="bi bi-building""></i>Setores</a></li>

            <li><a href=" ../controllers/painel.php?pagina=temperatura&amp;sem_loader=1" class="underline-desliza <?= $pagina === 'temperatura' ? 'active' : '' ?>"><i class="bi bi-thermometer"></i>Temperatura</a></li>

            <li><a href="../controllers/painel.php?pagina=ruido&amp;sem_loader=1" class="underline-desliza <?= $pagina === 'ruido' ? 'active' : '' ?>"><i class="bi bi-volume-up-fill"></i>Ruído</a></li>

            <li><a href="../controllers/painel.php?pagina=qualidadear&amp;sem_loader=1" class="underline-desliza <?= $pagina === 'qualidadear' ? 'active' : '' ?>"><i class="bi bi-wind"></i>Qualidade do Ar</a></li>

            <li><a href="../controllers/painel.php?pagina=alertas&amp;sem_loader=1" class="underline-desliza <?= $pagina === 'alertas' ? 'active' : '' ?>"><i class="bi bi-exclamation-circle-fill"></i>Alertas</a></li>

            <li><a href="../controllers/painel.php?pagina=configuracoes&amp;sem_loader=1" class="underline-desliza <?= $pagina === 'configuracoes' ? 'active' : '' ?>"><i class="bi bi-gear-fill"></i>Configurações</a></li>
        </ul>
    </nav>

    <!-- Conteúdo Principal -->
    <div class="conteudo">
        <?php if ($pagina === 'inicio'): ?>
            <h1 class="TextoBoas">Olá,<span class="nomeUser"><?php echo htmlspecialchars((string) $_SESSION['nome'], ENT_QUOTES, 'UTF-8'); ?></span>!</h1>
            <h1 class="TextoOla">Vigie as médias de todos os setores em gráfico da empresa</h1>
        <?php elseif ($pagina === 'temperatura'): ?>
            <h1 class="TextoBoas">Temperatura</h1>
            <h1 class="TextoOla">Acompanhe a temperatura dos setores</h1>
        <?php elseif ($pagina === 'setores'): ?>
            <h1 class="TextoBoas">Setores</h1>
            <h1 class="TextoOla">Consulte os setores da empresa</h1>
        <?php elseif ($pagina === 'ruido'): ?>
            <h1 class="TextoBoas">Ruído</h1>
            <h1 class="TextoOla">Acompanhe o nível de ruído dos setores</h1>
        <?php elseif ($pagina === 'qualidadear'): ?>
            <h1 class="TextoBoas">Qualidade do Ar</h1>
            <h1 class="TextoOla">Acompanhe o nível do ar dos setores</h1>
        <?php elseif ($pagina === 'configuracoes'): ?>
            <h1 class="TextoBoas">Configurações</h1>
        <?php else: ?>
            <div class="linhaflex">
                <div class="coluna">
                    <h1 class="TextoBoas">Alertas</h1>
                    <h1 class="TextoOla">Confira os alertas dos setores e faça downloads dos relatórios semanais</h1>
                </div>
                <button class="relatorio">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    <span>Baixar Relatório</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Página início -->
        <?php if ($pagina === 'inicio'): ?>
            <div class="containerj2">
                <a href=" ../controllers/painel.php?pagina=temperatura">
                    <div class="janelinha2">
                        <div class="janelinhaW">
                            <div class="inicioIcons">
                                <i class="bi bi-thermometer iconIM"></i>
                                <span class="outro">Temperatura média</span>
                            </div>
                            <div class="divi"></div>
                            <h1 class="info3">Temperatura geral: <span class="valores">23°</span></h1>
                        </div>
                    </div>
                </a>
                <a href=" ../controllers/painel.php?pagina=ruido">
                    <div class="janelinha2">
                        <div class="janelinhaW">
                            <div class="inicioIcons">
                                <i class="bi bi-volume-up-fill iconIM"></i>
                                <span class="outro">Ruído médio</span>
                            </div>
                            <div class="divi"></div>
                            <h1 class="info3">Ruído geral: <span class="valores">24 db</span></h1>
                        </div>
                    </div>
                </a>
                <a href=" ../controllers/painel.php?pagina=qualidadear">
                    <div class="janelinha2">
                        <div class="janelinhaW">
                            <div class="inicioIcons">
                                <i class="bi bi-wind iconIM"></i>
                                <span class="outro">Média do ar</span>
                            </div>
                            <div class="divi"></div>
                            <h1 class="info3">Qualidade geral: <span class="valores">Boa</span></h1>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Gráfico -->
            <div class="graficos">
                <div class="graficoArea">
                    <div class="col-md-12">
                        <div class="card p-3">
                            <canvas id="area"></canvas>
                        </div>
                    </div>
                </div>

                <div class="graficoPizza">
                    <div class="col-md-6">
                        <div class="row g-4">
                            <div class="card p-3">
                                <canvas id="pizza"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Página setores -->
            <?php elseif ($pagina === 'setores'): ?>
                <div class="containerj">
                    <?php foreach ($setores as $setor): ?>
                    <div class="janelinha2">
                        <div class="janelinha">
                            <div class="containerQIIO">
                                <div class="quadradoIcon">
                                    <i class="bi bi-building iconS"></i>
                                </div>
                                <span class="outro"><?= htmlspecialchars($setor['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="divi"></div>
                            <div class="quadradoInfo">
                                <h1 class="textoInfom">Temperatura:</h1>
                                <h1 class="textoInfomD"><?= $setor['temperatura'] !== null ? htmlspecialchars($setor['temperatura'], ENT_QUOTES, 'UTF-8') . 'º' : 'Aguardando' ?></h1>
                            </div>
                            <div class="quadradoInfo">
                                <h1 class="textoInfom">Ruído:</h1>
                                <h1 class="textoInfomD"><?= $setor['ruido'] !== null ? htmlspecialchars($setor['ruido'], ENT_QUOTES, 'UTF-8') . ' db' : 'Aguardando' ?></h1>
                            </div>
                            <div class="quadradoInfo">
                                <h1 class="textoInfom">Ar:</h1>
                                <h1 class="textoInfomD"><?= $setor['qualidade_ar'] !== null ? htmlspecialchars($setor['qualidade_ar'], ENT_QUOTES, 'UTF-8') : 'Aguardando' ?></h1>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($setores) === 0): ?>
                        <p class="aparencia-descricao">Nenhum setor cadastrado ainda.</p>
                    <?php endif; ?>
                </div>
            <?php elseif ($pagina === 'temperatura'): ?>
                <div class="colunaG">
                    <h1 class="tituloSetor">Setor 1</h1>
                    <div class="graficos">
                        <div class="graficoArea">
                            <div class="col-md-12">
                                <div class="card p-3">
                                    <canvas id="area-setor-1"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="graficoPizza">
                            <div class="col-md-6">
                                <div class="row g-4">
                                    <div class="card p-3">
                                        <canvas id="pizza-setor-1"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="linha"></div>
                    <h1 class="tituloSetor">Setor 2</h1>
                    <div class="graficos">
                        <div class="graficoArea">
                            <div class="col-md-12">
                                <div class="card p-3">
                                    <canvas id="area-setor-3"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="graficoPizza">
                            <div class="col-md-6">
                                <div class="row g-4">
                                    <div class="card p-3">
                                        <canvas id="pizza-setor-3"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="linha"></div>
                    <h1 class="tituloSetor">Setor 3</h1>
                    <div class="graficos">
                        <div class="graficoArea">
                            <div class="col-md-12">
                                <div class="card p-3">
                                    <canvas id="area-setor-4"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="graficoPizza">
                            <div class="col-md-6">
                                <div class="row g-4">
                                    <div class="card p-3">
                                        <canvas id="pizza-setor-4"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="linha"></div>
                    <h1 class="tituloSetor">Setor 4</h1>
                    <div class="graficos">
                        <div class="graficoArea">
                            <div class="col-md-12">
                                <div class="card p-3">
                                    <canvas id="area-setor-5"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="graficoPizza">
                            <div class="col-md-6">
                                <div class="row g-4">
                                    <div class="card p-3">
                                        <canvas id="pizza-setor-5"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($pagina === 'ruido'): ?>
                <div class="colunaG">
                    <h1 class="tituloSetor">Setor 1</h1>
                    <div class="graficos">
                        <div class="graficoArea">
                            <div class="col-md-12">
                                <div class="card p-3">
                                    <canvas id="area-setor-1"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="graficoPizza">
                            <div class="col-md-6">
                                <div class="row g-4">
                                    <div class="card p-3">
                                        <canvas id="pizza-setor-1"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="linha"></div>
                    <h1 class="tituloSetor">Setor 2</h1>
                    <div class="graficos">
                        <div class="graficoArea">
                            <div class="col-md-12">
                                <div class="card p-3">
                                    <canvas id="area-setor-3"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="graficoPizza">
                            <div class="col-md-6">
                                <div class="row g-4">
                                    <div class="card p-3">
                                        <canvas id="pizza-setor-3"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="linha"></div>
                    <h1 class="tituloSetor">Setor 3</h1>
                    <div class="graficos">
                        <div class="graficoArea">
                            <div class="col-md-12">
                                <div class="card p-3">
                                    <canvas id="area-setor-4"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="graficoPizza">
                            <div class="col-md-6">
                                <div class="row g-4">
                                    <div class="card p-3">
                                        <canvas id="pizza-setor-4"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="linha"></div>
                    <h1 class="tituloSetor">Setor 4</h1>
                    <div class="graficos">
                        <div class="graficoArea">
                            <div class="col-md-12">
                                <div class="card p-3">
                                    <canvas id="area-setor-5"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="graficoPizza">
                            <div class="col-md-6">
                                <div class="row g-4">
                                    <div class="card p-3">
                                        <canvas id="pizza-setor-5"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($pagina === 'alertas') : ?>
                <div class="janelinhaA">
                    <h1>Setor 1</h1>
                    <h1 class="deta">Crítico: Problemas relacionados a temperatura do setor: 40°C</h1>
                </div>
                <div class="janelinhaA">
                    <h1>Setor 2</h1>
                    <h1 class="deta">Crítico: Problemas relacionados ao ruído do setor: 180 db</h1>
                </div>
                <div class="janelinhaM">
                    <h1>Setor 3</h1>
                    <h1 class="deta">Avisos: Ajustes relacionados a qualidade do ar do setor: Estável</h1>
                </div>
            <?php elseif ($pagina === 'configuracoes'): ?>
                <div class="config">
                    <div class="configdiv" role="tablist" aria-label="Opções de configurações">
                        <button class="optionsC1 <?= $abaAtiva === 'panel-informacoes' ? 'active' : '' ?>" id="tab-informacoes" type="button" role="tab"
                            aria-selected="<?= $abaAtiva === 'panel-informacoes' ? 'true' : 'false' ?>" aria-controls="panel-informacoes" data-config-tab="panel-informacoes"
                            tabindex="<?= $abaAtiva === 'panel-informacoes' ? '0' : '-1' ?>">
                            Informações salvas
                        </button>
                        <button class="optionsC3" id="tab-aparencia" type="button" role="tab"
                            aria-selected="false" aria-controls="panel-aparencia" data-config-tab="panel-aparencia" tabindex="-1">
                            Aparência
                        </button>
                        <button class="optionsC2 <?= $abaAtiva === 'panel-perfil' ? 'active' : '' ?>" id="tab-perfil" type="button" role="tab"
                            aria-selected="<?= $abaAtiva === 'panel-perfil' ? 'true' : 'false' ?>" aria-controls="panel-perfil" data-config-tab="panel-perfil"
                            tabindex="<?= $abaAtiva === 'panel-perfil' ? '0' : '-1' ?>">
                            Nome e foto de perfil
                        </button>
                        <button class="optionsC2 <?= $abaAtiva === 'panel-setores' ? 'active' : '' ?>" id="tab-setores" type="button" role="tab"
                            aria-selected="<?= $abaAtiva === 'panel-setores' ? 'true' : 'false' ?>" aria-controls="panel-setores" data-config-tab="panel-setores"
                            tabindex="<?= $abaAtiva === 'panel-setores' ? '0' : '-1' ?>">
                            Novos Setores
                        </button>
                    </div>
                    <div class="windowMod" aria-live="polite">
                        <section class="config-panel" id="panel-informacoes" role="tabpanel"
                            aria-labelledby="tab-informacoes" <?= $abaAtiva === 'panel-informacoes' ? '' : 'hidden' ?>>
                            <h2>Informações salvas</h2>
                            <dl class="config-info">
                                <div>
                                    <dt>Nome</dt>
                                    <dd><?php echo htmlspecialchars((string) $_SESSION['nome'], ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                                <div>
                                    <dt>Tipo de conta</dt>
                                    <dd><?php echo htmlspecialchars((string) $_SESSION['papel'], ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                            </dl>
                        </section>
                        <section class="config-panel" id="panel-perfil" role="tabpanel"
                            aria-labelledby="tab-perfil" <?= $abaAtiva === 'panel-perfil' ? '' : 'hidden' ?>>
                            <h2>Nome e foto de perfil</h2>
                            <?php if ($mensagemPerfil !== ''): ?>
                                <p class="perfil-mensagem <?= $tipoMensagemPerfil === 'sucesso' ? 'sucesso' : 'erro' ?>" role="status">
                                    <?= htmlspecialchars($mensagemPerfil, ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            <?php endif; ?>
                            <div class="perfil-config">
                                <img src="<?php echo htmlspecialchars($fotoPerfil ?? '../public/assets/img/PFP.png', ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="Foto de perfil de <?php echo htmlspecialchars((string) $_SESSION['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                <div>
                                    <h3><?php echo htmlspecialchars((string) $_SESSION['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p>Altere o nome ou escolha uma nova foto para sua conta.</p>
                                </div>
                            </div>
                            <form class="perfil-form" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="acao" value="atualizar_perfil">
                                <input type="hidden" name="token_configuracoes"
                                    value="<?= htmlspecialchars($_SESSION['token_configuracoes'], ENT_QUOTES, 'UTF-8') ?>">
                                <label for="nome-perfil">Nome</label>
                                <input id="nome-perfil" name="nome" type="text" maxlength="255" required
                                    value="<?= htmlspecialchars($nomePerfilFormulario, ENT_QUOTES, 'UTF-8') ?>">
                                <label for="foto-perfil">Foto de perfil <span>(opcional, JPG, PNG ou WEBP)</span></label>
                                <input id="foto-perfil" class="perfil-file-input" type="file"
                                    accept="image/jpeg,image/png,image/webp" aria-describedby="nome-arquivo-perfil">
                                <div class="perfil-file-picker">
                                    <label class="perfil-file-botao" for="foto-perfil">
                                        <i class="bi bi-image" aria-hidden="true"></i>
                                        Escolher foto
                                    </label>
                                    <span id="nome-arquivo-perfil" class="perfil-file-nome" aria-live="polite">
                                        Nenhuma imagem selecionada
                                    </span>
                                </div>
                                <input id="foto-perfil-cropada" name="foto_perfil_cropada" type="hidden">
                                <div id="crop-modal-perfil" class="crop-modal perfil-crop-modal" style="display: none;"
                                    role="dialog" aria-modal="true" aria-labelledby="crop-titulo-perfil">
                                    <div class="crop-modal-content">
                                        <h3 id="crop-titulo-perfil">Ajustar foto de perfil</h3>
                                        <div class="cropper-box">
                                            <img id="crop-imagem-perfil" src="" alt="Prévia para ajustar a foto de perfil">
                                        </div>
                                        <div class="crop-actions">
                                            <button class="perfil-salvar2" id="confirmar-recorte-perfil" type="button">Usar esta foto</button>
                                            <button class="perfil-cancelar" id="cancelar-recorte-perfil" type="button">Cancelar</button>
                                        </div>
                                    </div>
                                </div>
                                <button class="perfil-salvar" type="submit"><i class="bi bi-check-circle-fill"></i>Salvar alterações</button>
                            </form>
                        </section>
                        <section class="config-panel" id="panel-aparencia" role="tabpanel"
                            aria-labelledby="tab-aparencia" hidden>
                            <h2>Aparência</h2>
                            <p class="aparencia-descricao">Escolha como o painel deve ser exibido.</p>
                            <fieldset class="tema-opcoes" aria-label="Tema do painel">
                                <label class="tema-opcao">
                                    <img src="../public/assets/img/icon-normal.png" class="img-theme">
                                    <input type="radio" name="tema-painel" value="light">
                                    <span><i class="bi bi-sun-fill" aria-hidden="true"></i> Claro</span>
                                </label>
                                <label class="tema-opcao">
                                    <img src="../public/assets/img/icon-night.png" class="img-theme">
                                    <input type="radio" name="tema-painel" value="dark">
                                    <span><i class="bi bi-moon-stars-fill" aria-hidden="true"></i> Escuro</span>
                                </label>
                                <label class="tema-opcao">
                                    <img src="../public/assets/img/icon-system.png" class="img-theme">
                                    <input type="radio" name="tema-painel" value="system">
                                    <span><i class="bi bi-display" aria-hidden="true"></i> Sistema</span>
                                </label>
                            </fieldset>
                        </section>
                        <section class="config-panel" id="panel-setores" role="tabpanel"
                            aria-labelledby="tab-setores" hidden>
                            <h2>Novos Setores</h2>
                            <p class="aparencia-descricao">Cadastre um novo setor informando apenas o nome.</p>
                            <?php if ($mensagemSetor !== ''): ?>
                                <p class="perfil-mensagem <?= $tipoMensagemSetor === 'sucesso' ? 'sucesso' : 'erro' ?>" role="status">
                                    <?= htmlspecialchars($mensagemSetor, ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            <?php endif; ?>
                            <form class="setor-form" method="post">
                                <input type="hidden" name="acao" value="criar_setor">
                                <label for="nome-setor">Nome do setor</label>
                                <input id="nome-setor" name="nome_setor" type="text" maxlength="255"
                                    placeholder="Ex.: Produção" autocomplete="off" value="<?= htmlspecialchars($nomeSetorFormulario, ENT_QUOTES, 'UTF-8') ?>" required>
                                <button type="submit" class="perfil-salvar">
                                    <i class="bi bi-plus-circle-fill" aria-hidden="true"></i>
                                    Criar setor
                                </button>
                            </form>
                        </section>
                    </div>
                </div>
            <?php else: ?>
                <div class="colunaG">
                    <h1 class="tituloSetor">Setor 1</h1>
                    <div class="graficos">
                        <div class="graficoArea">
                            <div class="col-md-12">
                                <div class="card p-3">
                                    <canvas id="area-setor-1"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="graficoPizza">
                            <div class="col-md-6">
                                <div class="row g-4">
                                    <div class="card p-3">
                                        <canvas id="pizza-setor-1"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="linha"></div>
                    <h1 class="tituloSetor">Setor 2</h1>
                    <div class="graficos">
                        <div class="graficoArea">
                            <div class="col-md-12">
                                <div class="card p-3">
                                    <canvas id="area-setor-3"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="graficoPizza">
                            <div class="col-md-6">
                                <div class="row g-4">
                                    <div class="card p-3">
                                        <canvas id="pizza-setor-3"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="linha"></div>
                    <h1 class="tituloSetor">Setor 3</h1>
                    <div class="graficos">
                        <div class="graficoArea">
                            <div class="col-md-12">
                                <div class="card p-3">
                                    <canvas id="area-setor-4"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="graficoPizza">
                            <div class="col-md-6">
                                <div class="row g-4">
                                    <div class="card p-3">
                                        <canvas id="pizza-setor-4"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="linha"></div>
                    <h1 class="tituloSetor">Setor 4</h1>
                    <div class="graficos">
                        <div class="graficoArea">
                            <div class="col-md-12">
                                <div class="card p-3">
                                    <canvas id="area-setor-5"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="graficoPizza">
                            <div class="col-md-6">
                                <div class="row g-4">
                                    <div class="card p-3">
                                        <canvas id="pizza-setor-5"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            </div>
    </div>

    <script src="../public/assets/js/loader.js"></script>
    <script src="../public/assets/js/scriptLC.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
</body>

</html>

