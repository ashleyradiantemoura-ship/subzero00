<?php
require_once __DIR__ . '/functions.php';
$usuario = usuario_logado();
$pagina_atual = basename($_SERVER['PHP_SELF']);
$titulo_pagina = $titulo_pagina ?? 'SUBZERO · Copa Interclasse · Hóquei no Gelo';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo_pagina) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo:wght@400;500;600;700;800&family=Archivo+Black&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sz.css?v=copa-home-ajustes-20261119-2">
</head>
<body class="<?= $pagina_atual === 'index.php' ? 'home' : ($pagina_atual === 'login.php' ? 'inner login-page' : 'inner') ?>">
<?php if ($pagina_atual === 'index.php'): ?>
<header class="nav nav--home" data-testid="main-nav">
<div class="nav__inner">
<a href="index.php" class="brand" data-testid="brand-logo"><span class="brand__name">SUBZERO</span></a>
<button class="nav__burger" id="burger" aria-label="Menu" data-testid="nav-burger"><span></span><span></span><span></span></button>
<nav class="nav__links" id="siteNav" data-testid="nav-links">
<div class="nav__group nav__group--left">
<a href="index.php" class="active">Início</a><a href="index.php#times">Times</a><a href="classificacao.php" data-testid="nav-classificacao">Classificação</a>
</div><div class="nav__group nav__group--right">
<a href="confrontos.php" data-testid="nav-confrontos">Confrontos</a><a href="artilharia.php">Artilharia</a><a href="premios.php">Favoritos</a>
<?php if (is_admin()): ?><a href="times.php" data-testid="nav-times">Gerenciar times</a><a href="jogadores.php">Jogadores</a><?php endif; ?>
<?php if ($usuario): ?><span class="auth-user" data-testid="auth-user"><span class="avatar"><?= e(mb_strtoupper(mb_substr($usuario['nome'],0,1))) ?></span><span class="auth-user__name"><?= e($usuario['nome']) ?></span></span><a href="logout.php" data-testid="nav-logout">Sair</a>
<?php else: ?><a href="login.php" class="nav__login" data-testid="nav-login-btn">Entrar</a><?php endif; ?>
</div></nav></div></header>
<?php else: ?>
<header class="nav nav--inner" data-testid="main-nav">
<div class="nav__inner">
<a href="index.php" class="brand" data-testid="brand-logo"><span class="brand__name">SUBZERO</span></a>
<button class="nav__burger" id="burger" aria-label="Menu" data-testid="nav-burger"><span></span><span></span><span></span></button>
<nav class="nav__links" id="siteNav" data-testid="nav-links">
<a href="index.php">Início</a><a href="index.php#times">Times</a>
<a href="classificacao.php" class="<?= $pagina_atual === 'classificacao.php' ? 'active' : '' ?>" data-testid="nav-classificacao">Classificação</a>
<a href="confrontos.php" class="<?= $pagina_atual === 'confrontos.php' ? 'active' : '' ?>" data-testid="nav-confrontos">Confrontos</a><a href="artilharia.php" class="<?= $pagina_atual === 'artilharia.php' ? 'active' : '' ?>">Artilharia</a><a href="premios.php" class="<?= $pagina_atual === 'premios.php' ? 'active' : '' ?>">Favoritos</a>
<?php if (is_admin()): ?><a href="times.php" class="<?= $pagina_atual === 'times.php' ? 'active' : '' ?>" data-testid="nav-times">Gerenciar times</a><?php endif; ?>
<?php if ($usuario): ?><span class="auth-user"><span class="avatar"><?= e(mb_strtoupper(mb_substr($usuario['nome'],0,1))) ?></span><span class="auth-user__name"><?= e($usuario['nome']) ?></span></span><a href="logout.php" data-testid="nav-logout">Sair</a>
<?php else: ?><a href="login.php">Entrar</a><?php endif; ?>
</nav></div></header>
<?php endif; ?>
<main class="site-main">
