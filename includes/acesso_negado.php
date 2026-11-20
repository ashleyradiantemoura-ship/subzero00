<?php
$tema = 'sz';
$titulo_pagina = 'SUBZERO · Acesso negado';
require_once __DIR__ . '/header.php';
?>
<section class="container section">
    <div class="empty-state reveal" data-testid="acesso-negado">
        <h1 class="section-title">Acesso negado</h1>
        <p>Você não tem permissão para acessar esta área. Esta função é exclusiva do administrador.</p>
        <a href="classificacao.php" class="btn btn-primary">Voltar à classificação</a>
    </div>
</section>
<?php require_once __DIR__ . '/footer.php'; ?>
