
<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
garantir_schema_expandido($pdo);
exigir_admin();
processar_acao_favorito($pdo);

$colunaFoto = $pdo->query("SHOW COLUMNS FROM times LIKE 'foto_url'")->fetch();
if (!$colunaFoto) {
    $pdo->exec("ALTER TABLE times ADD foto_url VARCHAR(255) NULL AFTER nome");
}

$flash = '';
$flash_tipo = 'success';
$pastaFotos = __DIR__ . '/uploads/times/';

if (!is_dir($pastaFotos)) {
    mkdir($pastaFotos, 0755, true);
}

function salvar_foto_time(array $arquivo, string $pasta): ?string {
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no envio da imagem.');
    }

    if (($arquivo['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('A imagem deve ter no máximo 5 MB.');
    }

    $info = @getimagesize($arquivo['tmp_name']);

    if (!$info) {
        throw new RuntimeException('O arquivo enviado não é uma imagem válida.');
    }

    $tipos = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp'
    ];

    $tipo = $info[2] ?? 0;

    if (!isset($tipos[$tipo])) {
        throw new RuntimeException('Use uma imagem JPG, PNG ou WEBP.');
    }

    $nome = bin2hex(random_bytes(16)) . '.' . $tipos[$tipo];

    if (!move_uploaded_file($arquivo['tmp_name'], $pasta . $nome)) {
        throw new RuntimeException('Não foi possível salvar a imagem no servidor.');
    }

    return 'uploads/times/' . $nome;
}

function remover_foto_antiga(?string $caminho): void {
    if (!$caminho || strpos($caminho, 'uploads/times/') !== 0) {
        return;
    }

    $arquivo = __DIR__ . '/' . $caminho;

    if (is_file($arquivo)) {
        @unlink($arquivo);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verificar_csrf()) {
            throw new RuntimeException('Sessão expirada. Atualize a página e tente novamente.');
        }

        $acao = $_POST['acao'] ?? '';

        if ($acao === 'adicionar') {

            $nome = trim($_POST['nome'] ?? '');

            if ($nome === '') {
                throw new RuntimeException('Informe o nome do time.');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM times WHERE nome = ?');
            $stmt->execute([$nome]);

            if ($stmt->fetchColumn() > 0) {
                throw new RuntimeException('Já existe um time com esse nome.');
            }

            $foto = salvar_foto_time($_FILES['foto'] ?? [], $pastaFotos);

            try {
                $pdo->prepare(
                    'INSERT INTO times (nome, foto_url) VALUES (?, ?)'
                )->execute([$nome, $foto]);

            } catch (Throwable $e) {
                remover_foto_antiga($foto);
                throw $e;
            }

            $flash = 'Time adicionado com sucesso.';

        } elseif ($acao === 'atualizar_foto') {

            $id = (int)($_POST['id'] ?? 0);

            $foto = salvar_foto_time($_FILES['foto'] ?? [], $pastaFotos);

            if (!$foto) {
                throw new RuntimeException('Selecione uma imagem para atualizar.');
            }

            $stmt = $pdo->prepare('SELECT foto_url FROM times WHERE id = ?');
            $stmt->execute([$id]);

            $antiga = $stmt->fetchColumn();

            if ($antiga === false) {
                remover_foto_antiga($foto);
                throw new RuntimeException('Time não encontrado.');
            }

            $pdo->prepare(
                'UPDATE times SET foto_url = ? WHERE id = ?'
            )->execute([$foto, $id]);

            remover_foto_antiga($antiga ?: null);

            $flash = 'Foto do time atualizada.';

        } elseif ($acao === 'excluir') {

            $id = (int)($_POST['id'] ?? 0);

            $stmt = $pdo->prepare('SELECT foto_url FROM times WHERE id = ?');
            $stmt->execute([$id]);

            $fotoAntiga = $stmt->fetchColumn();

            $pdo->prepare(
                'DELETE FROM confrontos WHERE time_casa = ? OR time_visitante = ?'
            )->execute([$id, $id]);

            $pdo->prepare('DELETE FROM times WHERE id = ?')->execute([$id]);

            remover_foto_antiga($fotoAntiga ?: null);

            $flash = 'Time e seus confrontos foram removidos.';
        }

    } catch (Throwable $e) {

        $flash = $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Ocorreu um erro ao salvar. Verifique o banco de dados.';

        $flash_tipo = 'error';
    }

    $_SESSION['flash'] = $flash;
    $_SESSION['flash_tipo'] = $flash_tipo;

    header('Location: times.php');
    exit;
}

if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    $flash_tipo = $_SESSION['flash_tipo'] ?? 'success';

    unset($_SESSION['flash'], $_SESSION['flash_tipo']);
}

$times = $pdo->query('
    SELECT t.id, t.nome, t.foto_url,
    (
        SELECT COUNT(*)
        FROM confrontos c
        WHERE c.time_casa = t.id OR c.time_visitante = t.id
    ) AS jogos
    FROM times t
    ORDER BY t.nome ASC
')->fetchAll();

$tema = 'sz';
$titulo_pagina = 'SUBZERO · Gerenciar times';

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero teams-admin-hero teams-admin-hero--classificacao" data-testid="times-hero">
    <div class="page-hero-overlay"></div>
    <div class="container page-hero-content teams-admin-hero__content">
        <p class="eyebrow reveal">Copa Interclasse · Hóquei no Gelo</p>
        <h1 class="page-title reveal">GESTÃO DOS <span class="outline">TIMES</span></h1>
        <p class="page-lead reveal">Cadastre, edite e organize as equipes da competição.</p>
        <div class="hero-stats hero-stats-row reveal">
            <div data-testid="stat-times"><strong><?= count($times) ?></strong><span>Times cadastrados</span></div>
        </div>
    </div>
</section>

<section class="container section teams-admin-section">

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash_tipo === 'error' ? 'error' : 'success' ?> reveal">
            <?= e($flash) ?>
        </div>
    <?php endif; ?>

    <div class="teams-management-layout">
    <div class="team-form-panel reveal">

        <div class="team-form-panel__heading">

            <span class="team-form-panel__index">
                01 / NOVA EQUIPE
            </span>

            <h2>Adicionar time</h2>

            <p>
                Cadastre o nome e escolha a imagem que será exibida nos cards da Home.
            </p>

        </div>

        <form method="post"
              enctype="multipart/form-data"
              class="team-create-form">

            <input type="hidden" name="csrf"
                   value="<?= e(csrf_token()) ?>">

            <input type="hidden" name="acao" value="adicionar">

            <label class="field">
                <span>Nome do time</span>

                <input
                    type="text"
                    name="nome"
                    placeholder="Ex.: Boston Bruins"
                    maxlength="100"
                    required
                >
            </label>

            <label class="field">
                <span>
                    Foto oficial
                    <small>JPG, PNG ou WEBP · até 5 MB</small>
                </span>

                <input
                    type="file"
                    name="foto"
                    accept="image/jpeg,image/png,image/webp"
                >
            </label>

            <button type="submit" class="team-submit">
                Adicionar equipe <span>↗</span>
            </button>

        </form>

    </div>

    <div class="teams-management-list">
    <div class="teams-admin-heading reveal">

        <div>
            <span class="team-form-panel__index">
                02 / ELENCO
            </span>

            <h2>Equipes cadastradas</h2>
        </div>

        <p>
            Gerencie as imagens e a presença de cada equipe no campeonato.
        </p>

    </div>

    <div class="teams-admin-grid">

        <?php if (!$times): ?>
            <div class="teams-admin-empty">
                Nenhum time cadastrado. Adicione a primeira equipe acima.
            </div>
        <?php endif; ?>

        <?php foreach ($times as $i => $t): ?>

            <article class="team-admin-card reveal">

                <div
                    class="team-admin-card__visual"
                    style="<?= !empty($t['foto_url'])
                        ? "background-image:url('" . e($t['foto_url']) . "')"
                        : '' ?>"
                >

                    <div class="team-admin-card__shade"></div>

                    <span class="team-admin-card__number">
                        <?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?>
                    </span>

                    <?php if (empty($t['foto_url'])): ?>
                        <span class="team-admin-card__placeholder">
                            SUBZERO
                        </span>
                    <?php endif; ?>

                    <div class="team-admin-card__title">

                        <h3><?= e($t['nome']) ?></h3><?= botao_favorito($pdo, 'time', (int)$t['id']) ?>

                        <p>
                            <?= (int)$t['jogos'] ?> confronto(s) no campeonato
                        </p>

                    </div>

                </div>

                <div class="team-admin-card__tools">

                    <form method="post"
                          enctype="multipart/form-data"
                          class="team-photo-update">

                        <input type="hidden" name="csrf"
                               value="<?= e(csrf_token()) ?>">

                        <input type="hidden" name="acao"
                               value="atualizar_foto">

                        <input type="hidden" name="id"
                               value="<?= (int)$t['id'] ?>">

                        <label class="team-photo-update__label">

                            <span>Alterar foto</span>

                            <input
                                type="file"
                                name="foto"
                                accept="image/jpeg,image/png,image/webp"
                                required
                            >

                        </label>

                        <button type="submit" class="team-tool-button">
                            Salvar foto
                        </button>

                    </form>

                    <form method="post"
                          onsubmit="return confirm('Excluir <?= e($t['nome']) ?>? Os confrontos dele também serão removidos.');">

                        <input type="hidden" name="csrf"
                               value="<?= e(csrf_token()) ?>">

                        <input type="hidden" name="id"
                               value="<?= (int)$t['id'] ?>">

                        <input type="hidden" name="acao" value="excluir">

                        <button type="submit" class="team-delete-button">
                            Excluir equipe
                        </button>

                    </form>

                </div>

            </article>

        <?php endforeach; ?>

    </div>
    </div><!-- /teams-management-list -->
    </div><!-- /teams-management-layout -->

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
