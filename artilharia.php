<?php
require_once __DIR__.'/config/db.php';require_once __DIR__.'/includes/functions.php';garantir_schema_expandido($pdo);exigir_login();processar_acao_favorito($pdo);
$sql="SELECT j.id,j.nome,j.numero,t.id AS time_id,t.nome AS time,
(SELECT COUNT(*) FROM confrontos cj WHERE cj.confirmado=1 AND (cj.time_casa=j.time_id OR cj.time_visitante=j.time_id)) AS jogos_confirmados,
COALESCE(SUM(CASE WHEN c.id IS NOT NULL THEN s.gols ELSE 0 END),0) AS total, COALESCE(SUM(CASE WHEN c.id IS NOT NULL THEN s.assistencias ELSE 0 END),0) AS assistencias,
(COALESCE(SUM(CASE WHEN c.id IS NOT NULL THEN s.gols ELSE 0 END),0)+COALESCE(SUM(CASE WHEN c.id IS NOT NULL THEN s.assistencias ELSE 0 END),0)+j.pontos_ajuste) AS pontos,
((SELECT COUNT(*) FROM confrontos cj WHERE cj.confirmado=1 AND (cj.time_casa=j.time_id OR cj.time_visitante=j.time_id))+j.jogos_ajuste) AS jogos_total
FROM jogadores j JOIN times t ON t.id=j.time_id
LEFT JOIN sumulas s ON s.jogador_id=j.id
LEFT JOIN confrontos c ON c.id=s.confronto_id AND c.confirmado=1
GROUP BY j.id,j.nome,j.numero,t.id,t.nome,j.pontos_ajuste,j.jogos_ajuste
ORDER BY total DESC,jogos_confirmados ASC,j.nome ASC LIMIT 10";
$ranking=$pdo->query($sql)->fetchAll();$titulo_pagina='SUBZERO · Artilharia';require __DIR__.'/includes/header.php';?>
<section class="page-hero page-hero-sm"><div class="page-hero-overlay"></div><div class="container page-hero-content"><p class="eyebrow">Estatísticas oficiais</p><h1 class="page-title">ARTI<span class="outline">LHARIA</span></h1><p class="page-lead">Os 10 goleadores com gols, assistências e pontos. Os ajustes manuais são configurados na área administrativa de jogadores.</p></div></section>
<section class="container section"><div class="card"><div class="standings-heading"><div><span class="section-kicker">TOP 10</span><h2>Goleadores da Copa</h2></div><p>Empates: menos jogos e depois ordem alfabética.</p></div>
<?php if(!$ranking):?><div class="empty-state">Ainda não há jogadores cadastrados.</div><?php else:?><div class="table-wrap"><table class="rank-table"><thead><tr><th>Pos.</th><th>Jogador</th><th>Time</th><th>Jogos</th><th>Gols</th><th>Assist.</th><th>Pontos</th></tr></thead><tbody><?php foreach($ranking as $i=>$j):?><tr><td><strong><?=($i+1)?></strong></td><td>#<?=(int)$j['numero']?> <?=e($j['nome'])?> <?=botao_favorito($pdo, 'jogador', (int)$j['id'])?></td><td><a href="time.php?id=<?=(int)$j['time_id']?>"><?=e($j['time'])?></a> <?=botao_favorito($pdo, 'time', (int)$j['time_id'])?></td><td><?=(int)$j['jogos_total']?></td><td><strong><?=(int)$j['total']?></strong></td><td><?=(int)$j['assistencias']?></td><td><strong><?=(int)$j['pontos']?></strong></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></div></section>
<?php require __DIR__.'/includes/footer.php';?>