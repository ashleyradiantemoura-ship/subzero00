<?php
/**
 * SUBZERO · Funções auxiliares, sessão, segurança e cálculo da classificação
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Escapa texto para exibição segura (anti-XSS). */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/** Usuário logado (ou null). */
function usuario_logado(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

/** True se o usuário logado for administrador. */
function is_admin(): bool
{
    $u = usuario_logado();
    return $u !== null && ($u['perfil'] ?? '') === 'admin';
}

/** Bloqueia páginas para quem não está logado. */
function exigir_login(): void
{
    if (usuario_logado() === null) {
        header('Location: login.php');
        exit;
    }
}

/** Bloqueia ações/páginas exclusivas do administrador. */
function exigir_admin(): void
{
    exigir_login();
    if (!is_admin()) {
        http_response_code(403);
        include __DIR__ . '/acesso_negado.php';
        exit;
    }
}

/** Token CSRF simples. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function verificar_csrf(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/** Retorna o status textual de um confronto. */
function status_confronto(array $c): string
{
    if ((int) $c['confirmado'] === 1) {
        return 'Confirmado';
    }
    if ($c['gols_casa'] !== null && $c['gols_visitante'] !== null) {
        return 'Placar Salvo';
    }
    return 'Criado';
}

/**
 * Calcula a tabela de classificação a partir dos confrontos CONFIRMADOS.
 * Regras (desafio escolar):
 *  - Vitória no tempo normal: 3 pts (V)
 *  - Vitória na prorrogação/shootout: 2 pts (VPR)
 *  - Derrota na prorrogação/shootout: 1 pt (DPR)
 *  - Derrota no tempo normal: 0 pt (D)
 */
function calcular_classificacao(PDO $pdo): array
{
    $times = $pdo->query('SELECT id, nome FROM times ORDER BY nome ASC')->fetchAll();

    $tab = [];
    foreach ($times as $t) {
        $tab[$t['id']] = [
            'id' => $t['id'], 'nome' => $t['nome'],
            'J' => 0, 'V' => 0, 'VPR' => 0, 'D' => 0, 'DPR' => 0,
            'GM' => 0, 'GS' => 0, 'SG' => 0, 'P' => 0,
        ];
    }

    $confrontos = $pdo->query(
        'SELECT * FROM confrontos WHERE confirmado = 1
         AND gols_casa IS NOT NULL AND gols_visitante IS NOT NULL'
    )->fetchAll();

    foreach ($confrontos as $c) {
        $casa = $c['time_casa'];
        $fora = $c['time_visitante'];
        if (!isset($tab[$casa], $tab[$fora])) {
            continue; // time removido
        }
        $gc = (int) $c['gols_casa'];
        $gv = (int) $c['gols_visitante'];
        $decisao = $c['decisao'] ?: 'normal';

        $tab[$casa]['J']++; $tab[$fora]['J']++;
        $tab[$casa]['GM'] += $gc; $tab[$casa]['GS'] += $gv;
        $tab[$fora]['GM'] += $gv; $tab[$fora]['GS'] += $gc;

        if ($gc === $gv) {
            continue; // resultado confirmado não deveria empatar
        }

        $vencedor = $gc > $gv ? $casa : $fora;
        $perdedor = $gc > $gv ? $fora : $casa;

        if ($decisao === 'normal') {
            $tab[$vencedor]['V']++;
            $tab[$perdedor]['D']++;
        } else { // prorrogacao ou shootout
            $tab[$vencedor]['VPR']++;
            $tab[$perdedor]['DPR']++;
        }
    }

    foreach ($tab as &$l) {
        $l['SG'] = $l['GM'] - $l['GS'];
        $l['P'] = $l['V'] * 3 + $l['VPR'] * 2 + $l['DPR'] * 1;
    }
    unset($l);

    $linhas = array_values($tab);
    usort($linhas, function ($a, $b) {
        return [$b['P'], $b['V'], $b['SG'], $b['GM'], $a['GS']]
            <=> [$a['P'], $a['V'], $a['SG'], $a['GM'], $b['GS']]
            ?: strcasecmp($a['nome'], $b['nome']);
    });

    return $linhas;
}

/** Nome do time pelo id (com cache). */
function nome_time(PDO $pdo, $id): string
{
    static $cache = [];
    if (!isset($cache[$id])) {
        $stmt = $pdo->prepare('SELECT nome FROM times WHERE id = ?');
        $stmt->execute([$id]);
        $cache[$id] = $stmt->fetchColumn() ?: 'Time removido';
    }
    return $cache[$id];
}

function rotulo_decisao(?string $d): string
{
    return [
        'normal' => 'Tempo normal',
        'prorrogacao' => 'Prorrogação',
        'shootout' => 'Shootout',
    ][$d] ?? '—';
}

/** Garante estruturas novas também em instalações com banco antigo. */
function garantir_schema_expandido(PDO $pdo): void
{
    $addCols = function(string $table, array $columns) use ($pdo): void {
        $existing = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ($columns as $name => $definition) {
            if (!in_array($name, $existing, true)) $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$name` $definition");
        }
    };
    $addCols('confrontos', ['data_hora'=>'DATETIME NULL','quadra'=>'VARCHAR(80) NULL','cidade'=>'VARCHAR(100) NULL','estado'=>'VARCHAR(2) NULL']);
    $pdo->exec("ALTER TABLE confrontos MODIFY quadra VARCHAR(80) NULL");
    $addCols('times', ['foto_url'=>'VARCHAR(255) NULL']);
    $pdo->exec("CREATE TABLE IF NOT EXISTS jogadores (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, time_id INT UNSIGNED NOT NULL,
        nome VARCHAR(80) NOT NULL, numero TINYINT UNSIGNED NOT NULL,
        jogos_ajuste INT UNSIGNED NOT NULL DEFAULT 0, pontos_ajuste INT UNSIGNED NOT NULL DEFAULT 0,
        UNIQUE KEY uq_jogador_numero (time_id, numero),
        CONSTRAINT fk_jogadores_time FOREIGN KEY (time_id) REFERENCES times(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    $addCols('jogadores', ['jogos_ajuste'=>'INT UNSIGNED NOT NULL DEFAULT 0','pontos_ajuste'=>'INT UNSIGNED NOT NULL DEFAULT 0']);
    $pdo->exec("CREATE TABLE IF NOT EXISTS sumulas (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, confronto_id INT UNSIGNED NOT NULL,
        jogador_id INT UNSIGNED NOT NULL, gols INT UNSIGNED NOT NULL DEFAULT 0, assistencias INT UNSIGNED NOT NULL DEFAULT 0,
        UNIQUE KEY uq_sumula_jogador (confronto_id, jogador_id),
        CONSTRAINT fk_sumula_confronto FOREIGN KEY (confronto_id) REFERENCES confrontos(id) ON DELETE CASCADE,
        CONSTRAINT fk_sumula_jogador FOREIGN KEY (jogador_id) REFERENCES jogadores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    $addCols('sumulas', ['assistencias'=>'INT UNSIGNED NOT NULL DEFAULT 0']);
    $pdo->exec("CREATE TABLE IF NOT EXISTS favoritos (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, usuario_id INT UNSIGNED NOT NULL,
        tipo ENUM('time','jogador','confronto') NOT NULL, item_id INT UNSIGNED NOT NULL,
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_favorito (usuario_id,tipo,item_id),
        CONSTRAINT fk_favoritos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
}


/** Processa ações de favoritar/remover sem interferir nos formulários das páginas. */
function processar_acao_favorito(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['acao_favorito'])) return;
    exigir_login();
    if (!verificar_csrf()) {
        $_SESSION['flash'] = 'Sessão expirada. Atualize a página e tente novamente.';
        $_SESSION['flash_tipo'] = 'error';
    } else {
        $acaoFavorito = (string)($_POST['acao_favorito'] ?? '');
        if (preg_match('/^(time|jogador|confronto):(\d+)$/', $acaoFavorito, $m)) {
            $tipo = $m[1]; $id = (int)$m[2];
        } else {
            $tipo = (string)($_POST['tipo_favorito'] ?? '');
            $id = filter_var($_POST['item_favorito'] ?? null, FILTER_VALIDATE_INT);
        }
        $usuarioId = (int)(usuario_logado()['id'] ?? 0);
        $tabelas = ['time' => 'times', 'jogador' => 'jogadores', 'confronto' => 'confrontos'];
        if (!isset($tabelas[$tipo]) || !$id || $id < 1 || !$usuarioId) {
            $_SESSION['flash'] = 'Item inválido para favoritar.'; $_SESSION['flash_tipo'] = 'error';
        } else {
            $existe = $pdo->prepare("SELECT id FROM `{$tabelas[$tipo]}` WHERE id = ?");
            $existe->execute([$id]);
            if (!$existe->fetchColumn()) {
                $_SESSION['flash'] = 'Este item não está mais disponível.'; $_SESSION['flash_tipo'] = 'error';
            } else {
                $q = $pdo->prepare('SELECT id FROM favoritos WHERE usuario_id=? AND tipo=? AND item_id=?');
                $q->execute([$usuarioId, $tipo, $id]);
                if ($q->fetchColumn()) {
                    $pdo->prepare('DELETE FROM favoritos WHERE usuario_id=? AND tipo=? AND item_id=?')->execute([$usuarioId, $tipo, $id]);
                    $_SESSION['flash'] = 'Removido dos favoritos.';
                } else {
                    $pdo->prepare('INSERT IGNORE INTO favoritos(usuario_id,tipo,item_id) VALUES(?,?,?)')->execute([$usuarioId, $tipo, $id]);
                    $_SESSION['flash'] = 'Adicionado aos favoritos.';
                }
                $_SESSION['flash_tipo'] = 'success';
            }
        }
    }
    $uri = $_SERVER['REQUEST_URI'] ?? 'classificacao.php';
    $path = parse_url($uri, PHP_URL_PATH) ?: 'classificacao.php';
    $query = parse_url($uri, PHP_URL_QUERY);
    header('Location: ' . $path . ($query ? '?' . $query : ''));
    exit;
}

/** Renderiza botão de favorito para equipe, jogador ou confronto. */
function botao_favorito(PDO $pdo, string $tipo, int $id): string
{
    $u = usuario_logado();
    if (!$u || !in_array($tipo, ['time','jogador','confronto'], true) || $id < 1) return '';
    $q = $pdo->prepare('SELECT id FROM favoritos WHERE usuario_id=? AND tipo=? AND item_id=?');
    $q->execute([(int)$u['id'], $tipo, $id]);
    $ativo = (bool)$q->fetchColumn();
    $rotulos = ['time'=>'equipe','jogador'=>'jogador','confronto'=>'confronto'];
    $texto = $ativo ? '★ Favoritado' : '☆ Favoritar';
    return '<form method="post" class="favorite-inline-form"><input type="hidden" name="csrf" value="'.e(csrf_token()).'"><input type="hidden" name="acao_favorito" value="alternar"><input type="hidden" name="tipo_favorito" value="'.e($tipo).'"><input type="hidden" name="item_favorito" value="'.$id.'"><button type="submit" class="btn btn--ghost btn-sm favorite-toggle'.($ativo?' is-favorite':'').'" aria-label="'.($ativo?'Remover ':'Adicionar ').$rotulos[$tipo].' '.($ativo?'dos':'aos').' favoritos">'.$texto.'</button></form>';
}


/** Botão para páginas em que o item já está dentro de um formulário existente (ex.: súmula). */
function botao_favorito_no_formulario(string $tipo, int $id): string
{
    $u = usuario_logado();
    if (!$u || !in_array($tipo, ['time','jogador','confronto'], true) || $id < 1) return '';
    global $pdo;
    $q = $pdo->prepare('SELECT id FROM favoritos WHERE usuario_id=? AND tipo=? AND item_id=?');
    $q->execute([(int)$u['id'], $tipo, $id]);
    $ativo = (bool)$q->fetchColumn();
    return '<button type="submit" name="acao_favorito" value="'.e($tipo).':'.$id.'" class="btn btn--ghost btn-sm favorite-toggle'.($ativo?' is-favorite':'').'" aria-label="'.($ativo?'Remover':'Adicionar').' jogador '.($ativo?'dos':'aos').' favoritos">'.($ativo?'★ Favoritado':'☆ Favoritar').'</button>';
}
