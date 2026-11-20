<?php
require_once __DIR__.'/config/db.php'; require_once __DIR__.'/includes/functions.php';
garantir_schema_expandido($pdo); exigir_admin(); processar_acao_favorito($pdo);
$flash=''; $tipo='success';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 try {
  if (!verificar_csrf()) throw new RuntimeException('Sessão expirada. Atualize a página.');
  $acao=$_POST['acao']??''; $id=(int)($_POST['id']??0); $time=(int)($_POST['time_id']??0);
  if ($acao==='salvar') {
   $nome=trim($_POST['nome']??''); $numero=filter_var($_POST['numero']??null,FILTER_VALIDATE_INT); $jogosAjuste=filter_var($_POST['jogos_ajuste']??0,FILTER_VALIDATE_INT); $pontosAjuste=filter_var($_POST['pontos_ajuste']??0,FILTER_VALIDATE_INT);
   if ($nome==='' || mb_strlen($nome)>80) throw new RuntimeException('Informe um nome com até 80 caracteres.');
   if (!$numero || $numero<1 || $numero>99) throw new RuntimeException('O número da camisa deve ser de 1 a 99.');
   if ($jogosAjuste===false || $jogosAjuste<0 || $pontosAjuste===false || $pontosAjuste<0) throw new RuntimeException('Jogos e pontos devem ser inteiros não negativos.' );
   if (!$pdo->query("SELECT id FROM times WHERE id=".(int)$time)->fetchColumn()) throw new RuntimeException('Selecione um time válido.');
   $q=$pdo->prepare('SELECT id FROM jogadores WHERE time_id=? AND numero=? AND id<>?'); $q->execute([$time,$numero,$id]);
   if ($q->fetch()) throw new RuntimeException('Esse número já está em uso neste time.');
   if ($id) $pdo->prepare('UPDATE jogadores SET time_id=?, nome=?, numero=?, jogos_ajuste=?, pontos_ajuste=? WHERE id=?')->execute([$time,$nome,$numero,$jogosAjuste,$pontosAjuste,$id]);
   else $pdo->prepare('INSERT INTO jogadores(time_id,nome,numero,jogos_ajuste,pontos_ajuste) VALUES(?,?,?,?,?)')->execute([$time,$nome,$numero,$jogosAjuste,$pontosAjuste]);
   $flash='Jogador salvo com sucesso.';
  } elseif ($acao==='excluir') {
   $pdo->prepare('DELETE FROM jogadores WHERE id=?')->execute([$id]); $flash='Jogador excluído. Seus gols também foram removidos.';
  }
 } catch(Throwable $e) { $flash=$e instanceof RuntimeException?$e->getMessage():'Não foi possível salvar. Verifique se o número já está cadastrado.'; $tipo='error'; }
}
$times=$pdo->query('SELECT id,nome FROM times ORDER BY nome')->fetchAll();
$editar=null; if (isset($_GET['editar'])) { $q=$pdo->prepare('SELECT * FROM jogadores WHERE id=?'); $q->execute([(int)$_GET['editar']]); $editar=$q->fetch()?:null; }
$lista=$pdo->query('SELECT j.*,t.nome AS time_nome FROM jogadores j JOIN times t ON t.id=j.time_id ORDER BY t.nome,j.numero')->fetchAll();
$porTime=[]; foreach($lista as $j) $porTime[$j['time_nome']][]=$j;
$titulo_pagina='SUBZERO · Jogadores'; require __DIR__.'/includes/header.php';
?>
<section class="page-hero page-hero-sm"><div class="page-hero-overlay"></div><div class="container page-hero-content"><p class="eyebrow">Área administrativa</p><h1 class="page-title">ELENCO <span class="outline">DE JOGADORES</span></h1><p class="page-lead">Gerencie atletas, números de camisa e ajustes manuais de jogos e pontos. Os totais das súmulas continuam integrados aos rankings.</p></div></section>
<section class="container section">
<?php if($flash): ?><div class="alert alert-<?= $tipo==='error'?'error':'success' ?>"><?=e($flash)?></div><?php endif; ?>
<div class="card"><h2 class="card-title"><?=$editar?'Editar jogador':'Cadastrar jogador'?></h2>
<form method="post" class="form form-inline"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="acao" value="salvar"><input type="hidden" name="id" value="<?=(int)($editar['id']??0)?>">
<label class="field"><span>Time</span><select name="time_id" required><option value="">Selecione</option><?php foreach($times as $t):?><option value="<?=(int)$t['id']?>" <?=((int)($editar['time_id']??0)===(int)$t['id'])?'selected':''?>><?=e($t['nome'])?></option><?php endforeach;?></select></label>
<label class="field field-grow"><span>Nome do jogador</span><input name="nome" maxlength="80" required value="<?=e($editar['nome']??'')?>"></label>
<label class="field"><span>Número (1–99)</span><input type="number" name="numero" min="1" max="99" required value="<?=e($editar['numero']??'')?>"></label><label class="field"><span>Jogos manuais adicionais</span><input type="number" name="jogos_ajuste" min="0" value="<?=e($editar['jogos_ajuste']??0)?>" required></label><label class="field"><span>Pontos manuais adicionais</span><input type="number" name="pontos_ajuste" min="0" value="<?=e($editar['pontos_ajuste']??0)?>" required></label><button class="btn btn-primary" type="submit">Salvar</button>
<?php if($editar):?><a class="btn btn--ghost" href="jogadores.php">Cancelar</a><?php endif;?></form></div>
<?php if(!$lista):?><div class="empty-state">Nenhum jogador cadastrado ainda.</div><?php endif;?>
<?php foreach($porTime as $nome=>$jogadores):?><div class="card"><h2 class="card-title"><?=e($nome)?> <?php $qFavTime=$pdo->prepare('SELECT id FROM times WHERE nome=? LIMIT 1');$qFavTime->execute([$nome]);echo botao_favorito($pdo, 'time', (int)$qFavTime->fetchColumn()); ?></h2><div class="table-wrap"><table class="rank-table"><thead><tr><th>Camisa</th><th>Jogador</th><th>Jogos manuais</th><th>Pontos manuais</th><th>Ações</th></tr></thead><tbody><?php foreach($jogadores as $j):?><tr><td>#<?=(int)$j['numero']?></td><td><?=e($j['nome'])?> <?=botao_favorito($pdo, 'jogador', (int)$j['id'])?></td><td><?=(int)$j['jogos_ajuste']?></td><td><?=(int)$j['pontos_ajuste']?></td><td><div class="actions-row"><a class="btn btn--ghost btn-sm" href="jogadores.php?editar=<?=(int)$j['id']?>">Editar</a><form method="post" onsubmit="return confirm('Excluir este jogador e seus gols?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?=(int)$j['id']?>"><button class="btn btn-danger btn-sm">Excluir</button></form></div></td></tr><?php endforeach;?></tbody></table></div></div><?php endforeach;?>
</section><?php require __DIR__.'/includes/footer.php'; ?>