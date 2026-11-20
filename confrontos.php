<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
garantir_schema_expandido($pdo);
exigir_login();
processar_acao_favorito($pdo);

$flash = '';
$flash_tipo = 'success';

/** Valida gols: inteiros não-negativos. */
function gols_validos($g): bool
{
    return $g !== '' && $g !== null && ctype_digit((string) $g) && (int) $g >= 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_admin()) {
        http_response_code(403);
        include __DIR__ . '/includes/acesso_negado.php';
        exit;
    }
    if (!verificar_csrf()) {
        $flash = 'Sessão expirada. Tente novamente.';
        $flash_tipo = 'error';
    } else {
        $acao = $_POST['acao'] ?? '';

        // --- Criar confronto ---
        if ($acao === 'criar') {
            $casa = (int) ($_POST['time_casa'] ?? 0);
            $fora = (int) ($_POST['time_visitante'] ?? 0);
            if ($casa <= 0 || $fora <= 0) {
                $flash = 'Selecione os dois times.'; $flash_tipo = 'error';
            } elseif ($casa === $fora) {
                $flash = 'Um time não pode jogar contra ele mesmo.'; $flash_tipo = 'error';
            } else {
                $dataHora = trim($_POST['data_hora'] ?? '');
                $quadra = trim($_POST['quadra'] ?? ''); $cidade=trim($_POST['cidade']??''); $estado=strtoupper(trim($_POST['estado']??''));
                if ($dataHora === '' || $quadra === '' || $cidade === '' || !preg_match('/^[A-Z]{2}$/',$estado)) {
                    $flash = 'Informe data, hora, pista, cidade e estado (UF).'; $flash_tipo = 'error';
                } else {
                    $dataSql = str_replace('T', ' ', $dataHora) . (strlen($dataHora) === 16 ? ':00' : '');
                    $conflito = $pdo->prepare('SELECT COUNT(*) FROM confrontos WHERE data_hora = ? AND (time_casa IN (?,?) OR time_visitante IN (?,?))');
                    $conflito->execute([$dataSql, $casa, $fora, $casa, $fora]);
                    if ((int)$conflito->fetchColumn() > 0) {
                        $flash = 'Este time já joga neste horário'; $flash_tipo = 'error';
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO confrontos (time_casa, time_visitante, gols_casa, gols_visitante, decisao, confirmado, data_hora, quadra, cidade, estado) VALUES (?, ?, NULL, NULL, NULL, 0, ?, ?, ?, ?)');
                        $stmt->execute([$casa, $fora, $dataSql, mb_substr($quadra, 0, 80), mb_substr($cidade,0,100), $estado]);
                        $flash = 'Confronto criado com sucesso.';
                    }
                }
            }
        }

        // --- Salvar placar (não confirma) ou Editar ---
        elseif ($acao === 'salvar_placar' || $acao === 'editar') {
            $id = (int) ($_POST['id'] ?? 0);
            $gc = $_POST['gols_casa'] ?? '';
            $gv = $_POST['gols_visitante'] ?? '';
            $decisao = $_POST['decisao'] ?? 'normal';

            if (!in_array($decisao, ['normal', 'prorrogacao', 'shootout'], true)) {
                $decisao = 'normal';
            }
            if (!gols_validos($gc) || !gols_validos($gv)) {
                $flash = 'Os gols devem ser números inteiros não-negativos.'; $flash_tipo = 'error';
            } else {
                // Verifica se já estava confirmado (edição de resultado confirmado)
                $chk = $pdo->prepare('SELECT confirmado FROM confrontos WHERE id = ?');
                $chk->execute([$id]);
                $confirmado = (int) $chk->fetchColumn();

                // Se está confirmado, não pode terminar empatado.
                if ($confirmado === 1 && (int) $gc === (int) $gv) {
                    $flash = 'Um resultado confirmado não pode terminar empatado. Defina um vencedor.'; $flash_tipo = 'error';
                } else {
                    $somas = $pdo->prepare('SELECT j.time_id, COALESCE(SUM(s.gols),0) total FROM sumulas s JOIN jogadores j ON j.id=s.jogador_id WHERE s.confronto_id=? GROUP BY j.time_id');
                    $somas->execute([$id]); $totais = [];
                    foreach ($somas->fetchAll() as $linha) $totais[(int)$linha['time_id']] = (int)$linha['total'];
                    $jogoQ=$pdo->prepare('SELECT time_casa,time_visitante FROM confrontos WHERE id=?');$jogoQ->execute([$id]);$jogo=$jogoQ->fetch();
                    if ((int)$gc < ($totais[(int)$jogo['time_casa']] ?? 0) || (int)$gv < ($totais[(int)$jogo['time_visitante']] ?? 0)) {
                        $flash = 'Ajuste a súmula antes de diminuir o placar'; $flash_tipo = 'error';
                    } else {
                        $stmt = $pdo->prepare('UPDATE confrontos SET gols_casa = ?, gols_visitante = ?, decisao = ? WHERE id = ?');
                        $stmt->execute([(int) $gc, (int) $gv, $decisao, $id]);
                        $flash = $confirmado === 1 ? 'Resultado editado. A classificação foi recalculada.' : 'Placar salvo (ainda não entra na classificação).';
                    }
                }
            }
        }

        elseif ($acao === 'atualizar_agenda') {
            $id=(int)($_POST['id']??0); $dh=trim($_POST['data_hora']??''); $quadra=trim($_POST['quadra']??''); $cidade=trim($_POST['cidade']??''); $estado=strtoupper(trim($_POST['estado']??''));
            if($dh===''||$quadra===''||$cidade===''||!preg_match('/^[A-Z]{2}$/',$estado)){ $flash='Informe data, hora, pista, cidade e estado (UF).'; $flash_tipo='error'; }
            else {
                $dataSql=str_replace('T',' ',$dh).(strlen($dh)===16?':00':'');
                $q=$pdo->prepare('SELECT time_casa,time_visitante FROM confrontos WHERE id=?');$q->execute([$id]);$jogo=$q->fetch();
                if(!$jogo){$flash='Confronto não encontrado.';$flash_tipo='error';}
                else {
                    $q=$pdo->prepare('SELECT COUNT(*) FROM confrontos WHERE id<>? AND data_hora=? AND (time_casa IN (?,?) OR time_visitante IN (?,?))');
                    $q->execute([$id,$dataSql,$jogo['time_casa'],$jogo['time_visitante'],$jogo['time_casa'],$jogo['time_visitante']]);
                    if((int)$q->fetchColumn()>0){$flash='Este time já joga neste horário';$flash_tipo='error';}
                    else{$pdo->prepare('UPDATE confrontos SET data_hora=?,quadra=?,cidade=?,estado=? WHERE id=?')->execute([$dataSql,mb_substr($quadra,0,80),mb_substr($cidade,0,100),$estado,$id]);$flash='Agenda atualizada.';}
                }
            }
        }

        // --- Confirmar resultado ---
        elseif ($acao === 'confirmar') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM confrontos WHERE id = ?');
            $stmt->execute([$id]);
            $c = $stmt->fetch();
            if (!$c) {
                $flash = 'Confronto não encontrado.'; $flash_tipo = 'error';
            } elseif ($c['gols_casa'] === null || $c['gols_visitante'] === null) {
                $flash = 'Lance o placar antes de confirmar.'; $flash_tipo = 'error';
            } elseif ((int) $c['gols_casa'] === (int) $c['gols_visitante']) {
                $flash = 'Resultado não pode ser empate. Defina um vencedor (prorrogação/shootout).'; $flash_tipo = 'error';
            } else {
                $pdo->prepare('UPDATE confrontos SET confirmado = 1 WHERE id = ?')->execute([$id]);
                $flash = 'Resultado confirmado! Classificação atualizada.';
            }
        }

        // --- Excluir confronto ---
        elseif ($acao === 'excluir') {
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('DELETE FROM confrontos WHERE id = ?')->execute([$id]);
            $flash = 'Confronto removido.';
        }
    }
    $_SESSION['flash'] = $flash;
    $_SESSION['flash_tipo'] = $flash_tipo;
    header('Location: confrontos.php');
    exit;
}

if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    $flash_tipo = $_SESSION['flash_tipo'] ?? 'success';
    unset($_SESSION['flash'], $_SESSION['flash_tipo']);
}

$times = $pdo->query('SELECT id, nome FROM times ORDER BY nome ASC')->fetchAll();
$confrontos = $pdo->query('SELECT * FROM confrontos ORDER BY (data_hora IS NULL), data_hora ASC, id ASC')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-hero page-hero-sm" data-testid="confrontos-hero">
    <div class="page-hero-overlay"></div>
    <div class="container page-hero-content">
        <p class="eyebrow reveal">Copa Interclasse</p>
        <h1 class="page-title reveal">CON<span class="outline">FRONTOS</span></h1>
        <p class="page-lead reveal">Jogos do campeonato. Somente resultados <strong>confirmados</strong> entram na classificação.</p>
    </div>
</section>

<section class="container section">
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash_tipo === 'error' ? 'error' : 'success' ?> reveal" data-testid="flash-message"><?= e($flash) ?></div>
    <?php endif; ?>

    <?php if (is_admin()): ?>
    <div class="card reveal" data-testid="criar-confronto-card">
        <h2 class="card-title">Novo confronto</h2>
        <form method="post" class="form form-inline" data-testid="criar-confronto-form">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="acao" value="criar">
            <label class="field">
                <span>Mandante</span>
                <select name="time_casa" required data-testid="select-casa">
                    <option value="">Selecione…</option>
                    <?php foreach ($times as $t): ?>
                        <option value="<?= (int) $t['id'] ?>"><?= e($t['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <span class="vs">VS</span>
            <label class="field">
                <span>Visitante</span>
                <select name="time_visitante" required data-testid="select-fora">
                    <option value="">Selecione…</option>
                    <?php foreach ($times as $t): ?>
                        <option value="<?= (int) $t['id'] ?>"><?= e($t['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field"><span>Data e hora</span><input type="datetime-local" name="data_hora" required></label>
            <label class="field"><span>Quadra / pista</span><input type="text" name="quadra" maxlength="80" placeholder="Arena principal" required></label><label class="field"><span>Cidade</span><input name="cidade" maxlength="100" placeholder="Caçapava" required></label><label class="field"><span>Estado (UF)</span><input name="estado" maxlength="2" pattern="[A-Za-z]{2}" placeholder="SP" required></label>
            <button type="submit" class="btn btn-primary" data-testid="criar-confronto-submit">Criar confronto</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="confronto-list" data-testid="confronto-list">
        <?php if (!$confrontos): ?>
            <div class="empty-state reveal">Nenhum confronto cadastrado ainda.</div>
        <?php endif; ?>
        <?php foreach ($confrontos as $c): $status = status_confronto($c); ?>
            <article class="confronto-card reveal" data-testid="confronto-<?= (int) $c['id'] ?>">
                <div class="confronto-head">
                    <span class="match-meta"><?= !empty($c['data_hora']) ? e(date('d/m/Y H:i', strtotime($c['data_hora']))) : 'Data a definir' ?><?= !empty($c['quadra']) ? ' · ' . e($c['quadra']) : '' ?><?= !empty($c['cidade']) ? ' · ' . e($c['cidade']).'/'.e($c['estado']??'') : '' ?></span>
                    <span class="status-tag status-<?= strtolower(str_replace(' ', '-', $status)) ?>"><?= e($status) ?></span>
                    <?= botao_favorito($pdo, 'confronto', (int)$c['id']) ?>
                    <?php if ($status === 'Confirmado'): ?>
                        <span class="decisao-tag"><?= e(rotulo_decisao($c['decisao'])) ?></span>
                    <?php endif; ?>
                </div>
                <div class="confronto-body">
                    <div class="side side-home">
                        <a class="team-name" href="time.php?id=<?= (int)$c['time_casa'] ?>"><?= e(nome_time($pdo, $c['time_casa'])) ?></a><?= botao_favorito($pdo, 'time', (int)$c['time_casa']) ?>
                    </div>
                    <div class="score">
                        <span class="score-num"><?= $c['gols_casa'] === null ? '–' : (int) $c['gols_casa'] ?></span>
                        <span class="score-sep">:</span>
                        <span class="score-num"><?= $c['gols_visitante'] === null ? '–' : (int) $c['gols_visitante'] ?></span>
                    </div>
                    <div class="side side-away">
                        <a class="team-name" href="time.php?id=<?= (int)$c['time_visitante'] ?>"><?= e(nome_time($pdo, $c['time_visitante'])) ?></a><?= botao_favorito($pdo, 'time', (int)$c['time_visitante']) ?>
                    </div>
                </div>

                <?php if (is_admin()): ?>
                <details class="confronto-actions">
                    <summary class="btn btn-ghost btn-sm" data-testid="toggle-acoes-<?= (int) $c['id'] ?>">Gerenciar</summary>
                    <div class="actions-panel">
                        <form method="post" class="form form-inline">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="acao" value="atualizar_agenda">
                            <label class="field"><span>Data e hora</span><input type="datetime-local" name="data_hora" value="<?= !empty($c['data_hora']) ? e(date('Y-m-d\TH:i',strtotime($c['data_hora']))) : '' ?>" required></label>
                            <label class="field"><span>Quadra / pista</span><input name="quadra" maxlength="80" value="<?= e($c['quadra'] ?? '') ?>" required></label><label class="field"><span>Cidade</span><input name="cidade" maxlength="100" value="<?= e($c['cidade'] ?? '') ?>" required></label><label class="field"><span>Estado (UF)</span><input name="estado" maxlength="2" pattern="[A-Za-z]{2}" value="<?= e($c['estado'] ?? '') ?>" required></label><button class="btn btn--ghost btn-sm">Atualizar agenda</button>
                        </form>
                        <form method="post" class="form form-score" data-testid="placar-form-<?= (int) $c['id'] ?>">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                            <input type="hidden" name="acao" value="<?= $status === 'Confirmado' ? 'editar' : 'salvar_placar' ?>">
                            <div class="score-inputs">
                                <input type="number" min="0" step="1" name="gols_casa" value="<?= $c['gols_casa'] === null ? '' : (int) $c['gols_casa'] ?>" placeholder="0" required data-testid="gols-casa-<?= (int) $c['id'] ?>">
                                <span>x</span>
                                <input type="number" min="0" step="1" name="gols_visitante" value="<?= $c['gols_visitante'] === null ? '' : (int) $c['gols_visitante'] ?>" placeholder="0" required data-testid="gols-fora-<?= (int) $c['id'] ?>">
                                <select name="decisao" data-testid="decisao-<?= (int) $c['id'] ?>">
                                    <option value="normal" <?= $c['decisao'] === 'normal' ? 'selected' : '' ?>>Tempo normal</option>
                                    <option value="prorrogacao" <?= $c['decisao'] === 'prorrogacao' ? 'selected' : '' ?>>Prorrogação</option>
                                    <option value="shootout" <?= $c['decisao'] === 'shootout' ? 'selected' : '' ?>>Shootout</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-outline btn-sm" data-testid="salvar-placar-<?= (int) $c['id'] ?>">
                                <?= $status === 'Confirmado' ? 'Editar resultado' : 'Salvar placar' ?>
                            </button>
                        </form>

                        <div class="actions-row">
                            <?php if ($c['gols_casa'] !== null && $c['gols_visitante'] !== null): ?><a class="btn btn--ghost btn-sm" href="sumula.php?id=<?= (int)$c['id'] ?>">Súmula</a><?php endif; ?>
                            <?php if ($status !== 'Confirmado'): ?>
                            <form method="post" onsubmit="return confirm('Confirmar este resultado? Ele entrará na classificação.');">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <input type="hidden" name="acao" value="confirmar">
                                <button type="submit" class="btn btn-primary btn-sm" data-testid="confirmar-<?= (int) $c['id'] ?>">Confirmar</button>
                            </form>
                            <?php endif; ?>
                            <form method="post" onsubmit="return confirm('Excluir este confronto?');">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <input type="hidden" name="acao" value="excluir">
                                <button type="submit" class="btn btn-danger btn-sm" data-testid="excluir-confronto-<?= (int) $c['id'] ?>">Excluir</button>
                            </form>
                        </div>
                    </div>
                </details>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
