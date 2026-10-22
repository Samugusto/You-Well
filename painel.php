<?php
session_start();
require_once "conexao.php";

if (!isset($_SESSION['id_usuario'])) {
    header("Location: entrar.php");
    exit();
}

if ($_SESSION['papel'] != "gerente") {
    header("Location: entrar.php");
    exit();
}

$con = conectar();
$sql_usuario = "SELECT foto_perfil FROM usuarios WHERE id_usuario = ?";
$stmt_usuario = mysqli_prepare($con, $sql_usuario);
mysqli_stmt_bind_param($stmt_usuario, "i", $_SESSION['id_usuario']);
mysqli_stmt_execute($stmt_usuario);
$resultado_usuario = mysqli_stmt_get_result($stmt_usuario);
$usuario = mysqli_fetch_assoc($resultado_usuario);
mysqli_stmt_close($stmt_usuario);
mysqli_close($con);

$fotoPerfil = null;
if ($usuario && !empty($usuario['foto_perfil'])) {
    $fotoPerfil = 'data:image/jpeg;base64,' . base64_encode($usuario['foto_perfil']);
}

$paginas = ['inicio', 'setores', 'temperatura', 'ruido', 'qualidadear', 'alertas'];
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
    <link href="img/Sem título.png" rel="icon">
    <link href="styleP.css" rel="stylesheet">
    <link rel="stylesheet" href="loader.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="graficos.js"></script>
    <script src="menu.js?v=2"></script>
</head>

<body>
    <?php if (!$semLoader): ?>
        <div id="loader" role="status" aria-live="polite">
            <div class="loader-content">
                <div class="loader-emblem">
                    <span class="loader-ring" aria-hidden="true"></span>
                    <img src="img/Sem título2.png" alt="" class="spinner-img">
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
            <div class="infos">
                <h1 class="nome"><?php echo $_SESSION['nome']; ?></h1>
                <span class="papel-usuario">
                    <?php echo htmlspecialchars($_SESSION['papel'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>
            <?php if (!empty($fotoPerfil)): ?>
                <img src="<?php echo $fotoPerfil; ?>" class="perfil" alt="Foto do perfil">
            <?php else: ?>
                <img src="https://i.pinimg.com/736x/96/1e/3e/961e3e38d18a0010f64986dc18fdf320.jpg" class="perfil" alt="Sem foto de perfil">
            <?php endif; ?>
        </div>
    </header>

    <nav class="menu-lateral">
        <ul>
            <div class="Logo">
                <img src="img/Sem título2.png" class="logo-menu">
                <h1 class="tituloY">You<span class="well">Well</span></h1>
            </div>
            <li><a href="painel.php?pagina=inicio&amp;sem_loader=1" class="underline-desliza <?= $pagina === 'inicio' ? 'active' : '' ?>"><i class="bi bi-house-fill"></i>Início</a></li>

            <li><a href="painel.php?pagina=setores&amp;sem_loader=1" class="underline-desliza <?= $pagina === 'setores' ? 'active' : '' ?>"><i class="bi bi-building""></i>Setores</a></li>

            <li><a href=" painel.php?pagina=temperatura&amp;sem_loader=1" class="underline-desliza <?= $pagina === 'temperatura' ? 'active' : '' ?>"><i class="bi bi-thermometer"></i>Temperatura</a></li>

            <li><a href="painel.php?pagina=ruido&amp;sem_loader=1" class="underline-desliza <?= $pagina === 'ruido' ? 'active' : '' ?>"><i class="bi bi-volume-up-fill"></i>Ruído</a></li>

            <li><a href="painel.php?pagina=qualidadear&amp;sem_loader=1" class="underline-desliza <?= $pagina === 'qualidadear' ? 'active' : '' ?>"><i class="bi bi-wind"></i>Qualidade do Ar</a></li>

            <li><a href="painel.php?pagina=alertas&amp;sem_loader=1" class="underline-desliza <?= $pagina === 'alertas' ? 'active' : '' ?>"><i class="bi bi-exclamation-circle-fill"></i>Alertas</a></li>
        </ul>
    </nav>

    <!-- Conteúdo Principal -->
    <div class="conteudo">
        <?php if ($pagina === 'inicio'): ?>
            <h1 class="TextoBoas">Olá,<span class="nomeUser"><?php echo $_SESSION['nome']; ?></span>!</h1>
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
                <a href=" painel.php?pagina=temperatura">
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
                <a href=" painel.php?pagina=ruido">
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
                <a href=" painel.php?pagina=qualidadear">
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
                    <div class="janelinha2">
                        <div class="janelinha">
                            <div class="containerQIIO">
                                <div class="quadradoIcon">
                                    <i class="bi bi-building iconS"></i>
                                </div>
                                <span class="outro">Setor: 1</span>
                            </div>
                            <div class="divi"></div>
                            <div class="quadradoInfo">
                                <h1 class="textoInfom">Temperatura:</h1>
                                <h1 class="textoInfomD">14º</h1>
                            </div>
                            <div class="quadradoInfo">
                                <h1 class="textoInfom">Ruído:</h1>
                                <h1 class="textoInfomD">14db</h1>
                            </div>
                            <div class="quadradoInfo">
                                <h1 class="textoInfom">Ar:</h1>
                                <h1 class="textoInfomD">Boa</h1>
                            </div>
                        </div>
                    </div>
                    <div class="janelinha2">
                        <div class="janelinha">
                            <div class="containerQIIO">
                                <div class="quadradoIcon">
                                    <i class="bi bi-building iconS"></i>
                                </div>
                                <span class="outro">Setor: 2</span>
                            </div>
                            <div class="divi"></div>
                            <div class="quadradoInfo">
                                <h1 class="textoInfom">Temperatura:</h1>
                                <h1 class="textoInfomD">14º</h1>
                            </div>
                            <div class="quadradoInfo">
                                <h1 class="textoInfom">Ruído:</h1>
                                <h1 class="textoInfomD">14db</h1>
                            </div>
                            <div class="quadradoInfo">
                                <h1 class="textoInfom">Ar:</h1>
                                <h1 class="textoInfomD">boa</h1>
                            </div>
                        </div>
                    </div>
                    <div class="janelinha2">
                        <div class="janelinha">
                            <div class="containerQIIO">
                                <div class="quadradoIcon">
                                    <i class="bi bi-building iconS"></i>
                                </div>
                                <span class="outro">Setor: 3</span>
                            </div>
                            <div class="divi"></div>
                            <div class="quadradoInfo">
                                <h1 class="textoInfom">Temperatura:</h1>
                                <h1 class="textoInfomD">14º</h1>
                            </div>
                            <div class="quadradoInfo">
                                <h1 class="textoInfom">Ruído:</h1>
                                <h1 class="textoInfomD">14db</h1>
                            </div>
                            <div class="quadradoInfo">
                                <h1 class="textoInfom">Ar:</h1>
                                <h1 class="textoInfomD">Boa</h1>
                            </div>
                        </div>
                    </div>
                    <div class="janelinha2">
                        <div class="janelinha">
                            <div class="containerQIIO">
                                <div class="quadradoIcon">
                                    <i class="bi bi-building iconS"></i>
                                </div>
                                <span class="outro">Setor: 4</span>
                            </div>
                            <div class="divi"></div>
                            <div class="quadradoInfo">
                                <h1 class="textoInfom">Temperatura:</h1>
                                <h1 class="textoInfomD">14º</h1>
                            </div>
                            <div class="quadradoInfo">
                                <h1 class="textoInfom">Ruído:</h1>
                                <h1 class="textoInfomD">14db</h1>
                            </div>
                            <div class="quadradoInfo">
                                <h1 class="textoInfom">Ar:</h1>
                                <h1 class="textoInfomD">Boa</h1>
                            </div>
                        </div>
                    </div>
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
            <?php else: ?>
                <div class="janelinha2">
                    <div class="janelinha">
                        <span class="outro"><img src="https://i.pinimg.com/1200x/70/e5/74/70e574d2cbd8d4270e98177d747b1bdb.jpg"></span>
                    </div>
                </div>
            <?php endif; ?>

            </div>
    </div>

    <script src="loader.js"></script>
</body>

</html>