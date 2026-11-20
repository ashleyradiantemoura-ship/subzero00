<?php
require_once __DIR__.'/config/db.php'; require_once __DIR__.'/includes/functions.php';
garantir_schema_expandido($pdo); exigir_admin(); processar_acao_favorito($pdo);
$id=(int)($_GET['id']??0); $q=$pdo->prepare('SELECT * FROM confrontos WHERE id=?');$q->execute([$id]);$c=$q->fetch();
if(!$c){http_response_code(404);$titulo_pagina='Súmula não encontrada';require __DIR__.'/includes/header.php';echo '<section class="container section"><div class="alert alert-error">Confronto não encontrado.</div><a class="btn btn--ghost" href="confrontos.php">Voltar</a></section>';require __DIR__.'/includes/footer.php';exit;}
$flash='';$tipo='success';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  if(!verificar_csrf())throw new RuntimeException('Sessão expirada.');
  if($c['gols_casa']===null||$c['gols_visitante']===null)throw new RuntimeException('Lance o placar antes de preencher a súmula.');
  $ids=[];$somas=[(int)$c['time_casa']=>0,(int)$c['time_visitante']=>0];$somasAssist=[(int)$c['time_casa']=>0,(int)$c['time_visitante']=>0];
  $q=$pdo->prepare('SELECT id,time_id FROM jogadores WHERE time_id IN (?,?)');$q->execute([$c['time_casa'],$c['time_visitante']]);foreach($q as $j)$ids[(int)$j['id']]=(int)$j['time_id'];
  foreach($ids as $jid=>$tid){$v=$_POST['gols'][$jid]??'0';$a=$_POST['assistencias'][$jid]??'0';if(!ctype_digit((string)$v)||!ctype_digit((string)$a))throw new RuntimeException('Gols e assistências devem ser inteiros não negativos.');$somas[$tid]+=(int)$v;$somasAssist[$tid]+=(int)$a;}
  if($somas[(int)$c['time_casa']]>(int)$c['gols_casa']||$somas[(int)$c['time_visitante']]>(int)$c['gols_visitante'])throw new RuntimeException('A soma dos gols dos jogadores não pode ultrapassar o placar do time. Confira também possíveis gols contra.');
  if($somasAssist[(int)$c['time_casa']]>2*(int)$c['gols_casa']||$somasAssist[(int)$c['time_visitante']]>2*(int)$c['gols_visitante'])throw new RuntimeException('Cada gol pode ter no máximo duas assistências. Confira as assistências lançadas.');
  $pdo->beginTransaction();$pdo->prepare('DELETE FROM sumulas WHERE confronto_id=?')->execute([$id]);$ins=$pdo->prepare('INSERT INTO sumulas(confronto_id,jogador_id,gols,assistencias) VALUES(?,?,?,?)');
  foreach($ids as $jid=>$tid){$g=(int)($_POST['gols'][$jid]??0);$a=(int)($_POST['assistencias'][$jid]??0);if($g>0||$a>0)$ins->execute([$id,$jid,$g,$a]);}$pdo->commit();$flash='Súmula salva. A artilharia considera este jogo somente após a confirmação.';
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$flash=$e instanceof RuntimeException?$e->getMessage():'Não foi possível salvar a súmula.';$tipo='error';}
}
$q=$pdo->prepare('SELECT j.*,t.nome AS time_nome,COALESCE(s.gols,0) AS gols,COALESCE(s.assistencias,0) AS assistencias FROM jogadores j JOIN times t ON t.id=j.time_id LEFT JOIN sumulas s ON s.jogador_id=j.id AND s.confronto_id=? WHERE j.time_id IN (?,?) ORDER BY t.nome,j.numero');$q->execute([$id,$c['time_casa'],$c['time_visitante']]);$jogadores=$q->fetchAll();
$titulo_pagina='SUBZERO · Súmula';require __DIR__.'/includes/header.php';
?>
<section class="page-hero page-hero-sm"><div class="page-hero-overlay"></div><div class="container page-hero-content"><p class="eyebrow">Registro oficial</p><h1 class="page-title">SÚMULA <span class="outline">DO JOGO</span></h1><?=botao_favorito($pdo, 'confronto', $id)?> <p class="page-lead"><?=e(nome_time($pdo,$c['time_casa']))?> <?=botao_favorito($pdo, 'time', (int)$c['time_casa'])?> <?= $c['gols_casa']===null?'—':(int)$c['gols_casa']?> × <?= $c['gols_visitante']===null?'—':(int)$c['gols_visitante']?> <?=e(nome_time($pdo,$c['time_visitante']))?> <?=botao_favorito($pdo, 'time', (int)$c['time_visitante'])?></p></div></section>
<section class="container section">
<?php if($flash):?><div class="alert alert-<?=$tipo==='error'?'error':'success'?>"><?=e($flash)?></div><?php endif;?>
<?php if($c['gols_casa']===null||$c['gols_visitante']===null):?><div class="alert alert-error">Lance o placar antes de preencher a súmula.</div><?php else:?>
<p class="page-lead" style="margin-bottom:1.2rem"><?=!empty($c['data_hora'])?e(date('d/m/Y H:i',strtotime($c['data_hora']))):'Data a definir'?> · <?=e($c['quadra']??'Pista a definir')?><?=!empty($c['cidade'])?' · '.e($c['cidade']).'/'.e($c['estado']??''):''?> · <?=((int)$c['confirmado']===1)?'Confirmado':'Aguardando confirmação'?></p>
<form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><div class="score-roster-grid"><?php foreach($jogadores as $j):?><div class="roster-row"><span><b>#<?=(int)$j['numero']?></b> <?=e($j['nome'])?><small><?=e($j['time_nome'])?></small><?=botao_favorito_no_formulario('jogador', (int)$j['id'])?></span><span class="field"><small>Gols</small><input type="number" min="0" max="99" name="gols[<?=(int)$j['id']?>]" value="<?=(int)$j['gols']?>" aria-label="Gols de <?=e($j['nome'])?>"></span><span class="field"><small>Assistências</small><input type="number" min="0" max="99" name="assistencias[<?=(int)$j['id']?>]" value="<?=(int)$j['assistencias']?>" aria-label="Assistências de <?=e($j['nome'])?>"></span></div><?php endforeach;?></div><p class="muted-note">Os pontos do jogador correspondem a gols + assistências. A soma dos gols individuais pode ser menor que o placar do time, por exemplo em caso de gol contra.</p><button class="btn btn-primary" type="submit">Salvar súmula</button> <a class="btn btn--ghost" href="confrontos.php">Voltar aos confrontos</a></form>
<?php endif;?></section><?php require __DIR__.'/includes/footer.php'; ?>