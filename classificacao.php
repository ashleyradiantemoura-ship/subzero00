<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
garantir_schema_expandido($pdo);
exigir_login();
processar_acao_favorito($pdo);

$classificacao = calcular_classificacao($pdo);
$total_confirmados = (int) $pdo->query('SELECT COUNT(*) FROM confrontos WHERE confirmado = 1')->fetchColumn();
$total_times = count($classificacao);

require_once __DIR__ . '/includes/header.php';
?>
<section class="page-hero" data-testid="classificacao-hero">
    <div class="page-hero-overlay"></div>
    <div class="container page-hero-content">
        <p class="eyebrow reveal">Copa Interclasse · Hóquei no Gelo</p>
        <h1 class="page-title reveal">CLASSIFI<span class="outline">CAÇÃO</span></h1>
        <p class="page-lead reveal">A tabela é calculada automaticamente a partir dos resultados confirmados.</p>
        <div class="hero-stats hero-stats-row reveal">
            <div data-testid="stat-times"><strong><?= $total_times ?></strong><span>Times</span></div>
            <div data-testid="stat-jogos"><strong><?= $total_confirmados ?></strong><span>Jogos confirmados</span></div>
            <div><strong>3·2·1·0</strong><span>Pontuação</span></div>
        </div>
    </div>
</section>

<section class="container section">
    <div class="standings-heading"><div><span class="section-kicker">TABELA OFICIAL</span><h2>Ranking das equipes</h2></div><p>Atualizado conforme os resultados confirmados</p></div>
    <div class="table-wrap reveal" data-testid="classificacao-tabela">
        <table class="rank-table">
            <thead>
                <tr>
                    <th class="col-pos" title="Posição">POS</th>
                    <th class="col-team">Time</th>
                    <th title="Jogos">J</th>
                    <th title="Vitórias no tempo normal">V</th>
                    <th title="Vitórias na prorrogação/shootout">VPR</th>
                    <th title="Derrotas no tempo normal">D</th>
                    <th title="Derrotas na prorrogação/shootout">DPR</th>
                    <th title="Gols marcados">GM</th>
                    <th title="Gols sofridos">GS</th>
                    <th title="Saldo de gols">SG</th>
                    <th class="col-pts" title="Pontos">P</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$classificacao): ?>
                    <tr><td colspan="11" class="empty-state">Nenhum time cadastrado.</td></tr>
                <?php endif; ?>
                <?php foreach ($classificacao as $i => $l): ?>
                    <tr class="<?= $i < 4 ? 'is-top' : '' ?>" data-testid="rank-row-<?= (int) $l['id'] ?>">
                        <td class="col-pos"><span class="pos-badge"><?= $i + 1 ?></span></td>
                        <td class="col-team"><span class="team-name-rank"><a href="time.php?id=<?= (int)$l['id'] ?>"><?= e($l['nome']) ?></a></span><?= botao_favorito($pdo, 'time', (int)$l['id']) ?></td>
                        <td><?= $l['J'] ?></td>
                        <td><?= $l['V'] ?></td>
                        <td><?= $l['VPR'] ?></td>
                        <td><?= $l['D'] ?></td>
                        <td><?= $l['DPR'] ?></td>
                        <td><?= $l['GM'] ?></td>
                        <td><?= $l['GS'] ?></td>
                        <td class="<?= $l['SG'] > 0 ? 'sg-pos' : ($l['SG'] < 0 ? 'sg-neg' : '') ?>"><?= $l['SG'] > 0 ? '+' . $l['SG'] : $l['SG'] ?></td>
                        <td class="col-pts"><?= $l['P'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="guide-grid reveal" data-testid="legenda">
        <section class="guide-card guide-columns" aria-labelledby="guia-siglas">
            <div class="guide-header"><span class="guide-icon">≡</span><div><span class="section-kicker">GUIA RÁPIDO</span><h3 id="guia-siglas">Entenda a tabela</h3></div></div>
            <div class="abbr-grid">
                <div><b>POS</b><span>Posição da equipe na classificação</span></div>
                <div><b>J</b><span>Jogos disputados</span></div>
                <div><b>V</b><span>Vitórias no tempo normal</span></div>
                <div><b>VPR</b><span>Vitórias na prorrogação ou shootout</span></div>
                <div><b>D</b><span>Derrotas no tempo normal</span></div>
                <div><b>DPR</b><span>Derrotas na prorrogação ou shootout</span></div>
                <div><b>GM</b><span>Gols marcados</span></div>
                <div><b>GS</b><span>Gols sofridos</span></div>
                <div><b>SG</b><span>Saldo de gols (GM − GS)</span></div>
                <div><b>P</b><span>Total de pontos</span></div>
            </div>
        </section>
        <section class="guide-card guide-scoring" aria-labelledby="guia-pontos">
            <div class="guide-header"><span class="guide-icon">★</span><div><span class="section-kicker">COMO FUNCIONA</span><h3 id="guia-pontos">Pontuação dos jogos</h3></div></div>
            <div class="scoring-grid">
                <div class="score-rule"><strong>3</strong><span>Vitória no tempo normal</span></div>
                <div class="score-rule"><strong>2</strong><span>Vitória na prorrogação ou shootout</span></div>
                <div class="score-rule"><strong>1</strong><span>Derrota na prorrogação ou shootout</span></div>
                <div class="score-rule"><strong>0</strong><span>Derrota no tempo normal</span></div>
            </div>
        </section>
        <section class="guide-card guide-tiebreak" aria-labelledby="guia-desempate">
            <div class="guide-header"><span class="guide-icon">↗</span><div><span class="section-kicker">SE HOUVER EMPATE</span><h3 id="guia-desempate">Critérios de desempate</h3></div></div>
            <p>Se duas equipes tiverem os mesmos pontos, a ordem é definida pelo primeiro critério em que houver diferença:</p>
            <ol class="tiebreak-list">
                <li><span>Mais pontos</span></li><li><span>Mais vitórias no tempo normal</span></li>
                <li><span>Melhor saldo de gols</span></li><li><span>Mais gols marcados</span></li>
                <li><span>Menos gols sofridos</span></li><li><span>Ordem alfabética</span></li>
            </ol>
        </section>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
