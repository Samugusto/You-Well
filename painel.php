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

$paginas = ['inicio', 'temperatura', 'ruido', 'alertas'];
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
    <title>YouWell - Dash</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&display=swap" rel="stylesheet">
    <link href="img/Sem título.png" rel="icon">
    <link href="styleP.css" rel="stylesheet">
</head>

<body>
    <header id="header">
        <div class="user">
            <h1 class="nome"><?php echo $_SESSION['nome']; ?></h1> <img
                src="https://i1-e.pinimg.com/736x/7c/fb/92/7cfb9240b1337cf4642ecc7945d69bf2.jpg" class="perfil">
        </div>
    </header>

    <nav class="menu-lateral">
        <ul>
            <div class="Logo">
                <img src="img/Sem título2.png" class="logo-menu">
                <h1 class="tituloY">YouWell</h1>
            </div>
            <li><a href="painel.php?pagina=inicio" class="underline-desliza"><i class="bi bi-house-fill"></i>Início</a></li>
            <li><a href="painel.php?pagina=temperatura" class="underline-desliza"><i class="bi bi-thermometer"></i>Temperatura</a></li>
            <li><a href="painel.php?pagina=ruido" class="underline-desliza"><i class="bi bi-volume-up-fill"></i>Ruído</a></li>
            <li><a href="painel.php?pagina=alertas" class="underline-desliza"><i class="bi bi-exclamation-circle-fill"></i>Alertas</a></li>
        </ul>
    </nav>

    <!-- Conteúdo Principal -->
    <div class="conteudo">
        <?php if ($pagina === 'inicio'): ?>
            <h1 class="TextoBoas">Bem-vindo, <?php echo $_SESSION['nome']; ?>!</h1>
            <h1 class="TextoOla">Vigie os principais setores da empresa</h1>
        <?php elseif ($pagina === 'temperatura'): ?>
            <h1 class="TextoBoas">Temperatura</h1>
            <h1 class="TextoOla">Acompanhe a temperatura dos setores</h1>
        <?php elseif ($pagina === 'ruido'): ?>
            <h1 class="TextoBoas">Ruído</h1>
            <h1 class="TextoOla">Acompanhe o nível de ruído dos setores</h1>
        <?php else: ?>
            <h1 class="TextoBoas">Alertas</h1>
            <h1 class="TextoOla">Confira os alertas dos setores</h1>
        <?php endif; ?>

        <?php if ($pagina === 'inicio'): ?>
            <div class="containerj">
            <div class="janelinha2">
                <div class="janelinha">
                    <span class="outro">Setor: 1</span>
                    <div class="divi"></div>
                    <h1 class="info"><i class="bi bi-thermometer iconI"></i>Temperatura: (valor)</h1>
                    <h1 class="info2"><i class="bi bi-volume-up-fill iconI"></i>Ruído: (valor)</h1>
                    <h1 class="info2"><i class="bi bi-wind iconI"></i>Qualidade do ar: (boa?)</h1>
                </div>
            </div>
            <div class="janelinha2">
                <div class="janelinha">
                    <span class="outro">Setor: 1</span>
                    <div class="divi"></div>
                    <h1 class="info"><i class="bi bi-thermometer iconI"></i>Temperatura: (valor)</h1>
                    <h1 class="info2"><i class="bi bi-volume-up-fill iconI"></i>Ruído: (valor)</h1>
                    <h1 class="info2"><i class="bi bi-wind iconI"></i>Qualidade do ar: (boa?)</h1>
                </div>
            </div>
            <div class="janelinha2">
                <div class="janelinha">
                    <span class="outro">Setor: 1</span>
                    <div class="divi"></div>
                    <h1 class="info"><i class="bi bi-thermometer iconI"></i>Temperatura: (valor)</h1>
                    <h1 class="info2"><i class="bi bi-volume-up-fill iconI"></i>Ruído: (valor)</h1>
                    <h1 class="info2"><i class="bi bi-wind iconI"></i>Qualidade do ar: (boa?)</h1>
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