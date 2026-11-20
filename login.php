<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Já logado? vai para a classificação.
if (usuario_logado() !== null) {
    header('Location: classificacao.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!verificar_csrf()) {
        $erro = 'Sessão expirada. Tente novamente.';
    } elseif ($email === '' || $senha === '') {
        $erro = 'Preencha e-mail e senha.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && password_verify($senha, $u['senha'])) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = [
                'id' => $u['id'], 'nome' => $u['nome'],
                'email' => $u['email'], 'perfil' => $u['perfil'],
            ];
            header('Location: classificacao.php');
            exit;
        }
        $erro = 'E-mail ou senha inválidos.';
    }
}
?>
<?php
$tema = 'sz';
$titulo_pagina = 'SUBZERO · Entrar';
require_once __DIR__ . '/includes/header.php';
?>
<section class="login-wrap" data-testid="login-hero">
    <div class="hero__bg"></div>
    <div class="hero__scrim"></div>
    <div class="login-card" data-testid="login-card">
        <h1 class="login-title">Entrar</h1>
        <p class="login-desc">Acesse como administrador ou visualizador.</p>

        <?php if ($erro): ?>
            <div class="alert alert-error" data-testid="login-error"><?= e($erro) ?></div>
        <?php endif; ?>

        <form method="post" class="form" data-testid="login-form">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <label class="field">
                <span>E-mail</span>
                <input type="email" name="email" placeholder="voce@subzero.com" required autofocus data-testid="login-email">
            </label>
            <label class="field">
                <span>Senha</span>
                <input type="password" name="senha" placeholder="••••••••" required data-testid="login-senha">
            </label>
            <button type="submit" class="btn btn--ice btn-block" data-testid="login-submit">Entrar na plataforma</button>
        </form>

        <div class="login-hint" data-testid="login-hint">
            <p><strong>Contas de demonstração</strong></p>
            <p>Admin: <code>admin@subzero.com</code> / <code>admin123</code></p>
            <p>Visualizador: <code>viewer@subzero.com</code> / <code>viewer123</code></p>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
