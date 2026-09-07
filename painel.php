<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: entrar.php");
    exit();
}

if ($_SESSION['papel'] != "gerente") {
    header("Location: entrar.php");
    exit();
}

$paginas = ['inicio', 'setores', 'temperatura', 'ruido', 'alertas'];
$pagina = $_GET['pagina'] ?? 'inicio';

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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&display=swap" rel="stylesheet">
    <link href="img/Sem título.png" rel="icon">
    <link href="styleP.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="graficos.js"></script>
</head>

<body>
    <header id="header">
        <div class="user">
            <h1 class="nome"><?php echo $_SESSION['nome']; ?></h1> <img
                src="https://i.pinimg.com/736x/d2/19/82/d219824d3a0235f27c9e083605aac144.jpg" class="perfil">
        </div>
    </header>

    <nav class="menu-lateral">
        <ul>
            <div class="Logo">
                <img src="img/Sem título2.png" class="logo-menu">
                <h1 class="tituloY">You<span class="well">Well</span></h1>
            </div>
            <li><a href="painel.php?pagina=inicio" class="underline-desliza <?= $pagina === 'inicio' ? 'active' : '' ?>"><i class="bi bi-house-fill"></i>Início</a></li>

            <li><a href="painel.php?pagina=setores" class="underline-desliza <?= $pagina === 'setores' ? 'active' : '' ?>"><i class="bi bi-building""></i>Setores</a></li>

            <li><a href=" painel.php?pagina=temperatura" class="underline-desliza <?= $pagina === 'temperatura' ? 'active' : '' ?>"><i class="bi bi-thermometer"></i>Temperatura</a></li>

            <li><a href="painel.php?pagina=ruido" class="underline-desliza <?= $pagina === 'ruido' ? 'active' : '' ?>"><i class="bi bi-volume-up-fill"></i>Ruído</a></li>

            <li><a href="painel.php?pagina=alertas" class="underline-desliza <?= $pagina === 'alertas' ? 'active' : '' ?>"><i class="bi bi-exclamation-circle-fill"></i>Alertas</a></li>
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
        <?php else: ?>
            <h1 class="TextoBoas">Alertas</h1>
            <h1 class="TextoOla">Confira os alertas dos setores</h1>
        <?php endif; ?>

        <!-- Página início -->
        <?php if ($pagina === 'inicio'): ?>
            <div class="containerj2">
                <div class="janelinha2">
                    <div class="janelinha">
                        <div class="inicioIcons">
                            <i class="bi bi-thermometer iconIM"></i>
                            <span class="outro">Temperatura</span>
                        </div>
                        <div class="divi"></div>
                        <h1 class="info3">Temperatura: <span class="valores">β</span></h1>
                    </div>
                </div>
                <div class="janelinha2">
                    <div class="janelinha">
                        <div class="inicioIcons">
                            <i class="bi bi-volume-up-fill iconIM"></i>
                            <span class="outro">Ruído</span>
                        </div>
                        <div class="divi"></div>
                        <h1 class="info3">Ruído: <span class="valores">β</span></h1>
                    </div>
                </div>
                <div class="janelinha2">
                    <div class="janelinha">
                        <div class="inicioIcons">
                            <i class="bi bi-wind iconIM"></i>
                            <span class="outro">Qualidade do ar</span>
                        </div>
                        <div class="divi"></div>
                        <h1 class="info3">Qualidade do ar: <span class="valores">(boa?)</span></h1>
                    </div>
                </div>

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
                            <span class="outro">Setor: 1</span>
                            <div class="divi"></div>
                            <h1 class="info"><i class="bi bi-thermometer iconI2"></i>Temperatura: <span class="valores2">(valor)</span></h1>

                            <h1 class="info2"><i class="bi bi-volume-up-fill iconI"></i>Ruído: <span class="valores2">(valor)</span></h1>

                            <h1 class="info2"><i class="bi bi-wind iconI2"></i>Qualidade do ar: <span class="valores2">(valor)</span></h1>
                        </div>
                    </div>
                    <div class="janelinha2">
                        <div class="janelinha">
                            <span class="outro">Setor: 2</span>
                            <div class="divi"></div>
                            <h1 class="info"><i class="bi bi-thermometer iconI2"></i>Temperatura: <span class="valores2">(valor)</span></h1>

                            <h1 class="info2"><i class="bi bi-volume-up-fill iconI"></i>Ruído: <span class="valores2">(valor)</span></h1>

                            <h1 class="info2"><i class="bi bi-wind iconI2"></i>Qualidade do ar: <span class="valores2">(valor)</span></h1>
                        </div>
                    </div>
                    <div class="janelinha2">
                        <div class="janelinha">
                            <span class="outro">Setor: 3</span>
                            <div class="divi"></div>
                            <h1 class="info"><i class="bi bi-thermometer iconI2"></i>Temperatura: <span class="valores2">(valor)</span></h1>

                            <h1 class="info2"><i class="bi bi-volume-up-fill iconI"></i>Ruído: <span class="valores2">(valor)</span></h1>

                            <h1 class="info2"><i class="bi bi-wind iconI2"></i>Qualidade do ar: <span class="valores2">(valor)</span></h1>
                        </div>
                    </div>
                    <div class="janelinha2">
                        <div class="janelinha">
                            <span class="outro">Setor: 4</span>
                            <div class="divi"></div>
                            <h1 class="info"><i class="bi bi-thermometer iconI"></i>Temperatura: <span class="valores2">(valor)</span></h1>
                            <h1 class="info2"><i class="bi bi-volume-up-fill iconI"></i>Ruído: <span class="valores2">(valor)</span></h1>
                            <h1 class="info2"><i class="bi bi-wind iconI"></i>Qualidade do ar: <span class="valores2">(valor)</span></h1>
                        </div>
                    </div>
                </div>
            <?php elseif ($pagina === 'temperatura'): ?>
                <div class="">
                    <h1>Setor 1</h1>
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
                        <h1>Setor 3</h1>
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
                            <h1>Setor 4</h1>
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
                                <h1>Setor 5</h1>
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

</body>

</html>