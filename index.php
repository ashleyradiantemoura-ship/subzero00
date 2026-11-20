<?php
/** SUBZERO · Página inicial (visual do projeto sz, dados vindos do banco). */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

garantir_schema_expandido($pdo);
$tema = 'sz';
$titulo_pagina = 'SUBZERO — Copa Interclasse de Hóquei no Gelo';

$times = $pdo->query('SELECT t.id, t.nome, t.foto_url,
        (SELECT COUNT(*) FROM confrontos c WHERE c.time_casa = t.id OR c.time_visitante = t.id) AS jogos
     FROM times t ORDER BY t.nome ASC')->fetchAll();
$total_times = count($times);
$total_confirmados = (int) $pdo->query('SELECT COUNT(*) FROM confrontos WHERE confirmado = 1')->fetchColumn();

// Fotos usadas nos cartões (mesmas do projeto sz), repetidas em ciclo.
$fotos = [
    'https://images.unsplash.com/photo-1549598145-86b354509082?crop=entropy&cs=srgb&fm=jpg&q=85&w=800',
    'https://images.unsplash.com/flagged/photo-1550585477-a025700d7fce?crop=entropy&cs=srgb&fm=jpg&q=85&w=800',
    'https://images.unsplash.com/photo-1516226415502-d6624544376b?crop=entropy&cs=srgb&fm=jpg&q=85&w=800',
    'https://images.unsplash.com/photo-1496427473315-1408fac9d594?crop=entropy&cs=srgb&fm=jpg&q=85&w=800',
];

require_once __DIR__ . '/includes/header.php';
?>
<section class="hero" id="home" data-testid="hero">
    <h1 class="sr-only">SUBZERO — Copa Interclasse de Hóquei no Gelo</h1>
    <div class="hero__foot">
        <div class="hero__intro">
            <p class="hero__sub">
                Sua central da Copa Interclasse de hóquei no gelo: classificação, confrontos e times em um só lugar.
            </p>
            <a href="classificacao.php" class="hero__btn" data-testid="hero-standings-btn">Ver classificação</a>
            <a href="confrontos.php" class="hero__btn hero__btn--secondary">Agenda de jogos</a>
        </div>
        <div class="hero__stats" data-testid="hero-stats">
            <div class="stat"><span class="stat__num"><?= $total_times ?></span><span class="stat__label">Times<br>na liga</span></div>
            <div class="stat"><span class="stat__num"><?= $total_confirmados ?></span><span class="stat__label">Jogos<br>confirmados</span></div>
        </div>
    </div>
</section>

<section class="gear" id="times" data-testid="teams-section">
    <div class="gear__head">
        <h2 class="gear__title">Conheça os times<br>da Copa Interclasse</h2>
    </div>
    <div class="gear__stage">
        <button type="button" class="gear__arrow gear__arrow--prev" id="gearPrev" aria-label="Times anteriores" data-testid="teams-prev">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 12H5m6-7-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <div class="gear__track" id="gearTrack" tabindex="0" aria-label="Lista de times" data-testid="teams-grid">
            <?php if (!$times): ?>
                <p class="empty-state">Nenhum time cadastrado.</p>
            <?php endif; ?>
            <?php foreach ($times as $i => $t): ?>
                <article class="gcard" data-testid="team-<?= (int) $t['id'] ?>">
                    <div class="gcard__img" style="background-image:url('<?= e(!empty($t['foto_url']) ? $t['foto_url'] : $fotos[$i % count($fotos)]) ?>')"></div>
                    <h3 class="gcard__name"><?= e($t['nome']) ?></h3>
                    <p class="gcard__note"><?= (int) $t['jogos'] ?> confronto(s) no campeonato.</p>
                    <a href="time.php?id=<?= (int) $t['id'] ?>" class="gcard__go" aria-label="Ver confrontos de <?= e($t['nome']) ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
        <button type="button" class="gear__arrow gear__arrow--next" id="gearNext" aria-label="Próximos times" data-testid="teams-next">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-6-7 7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </div>
</section>
<section class="container section home-shortcuts">
  <div class="standings-heading">
    <div>
      <span class="section-kicker">SUBZERO · COPA INTERCLASSE</span>
      <h2>Explore o campeonato</h2>
    </div>
    <p>Tudo o que acontece na competição, reunido em um só lugar.</p>
  </div>
  <div class="shortcut-grid">
    <a class="shortcut-card" href="classificacao.php"><span>01</span><h3>Classificação</h3><p>Pontos, vitórias e saldo de gols.</p></a>
    <a class="shortcut-card" href="confrontos.php"><span>02</span><h3>Agenda e resultados</h3><p>Datas, horários, quadras e placares.</p></a>
    <a class="shortcut-card" href="artilharia.php"><span>03</span><h3>Artilharia</h3><p>Ranking dos jogadores que mais marcaram.</p></a>
    <a class="shortcut-card" href="premios.php"><span>04</span><h3>Favoritos da Copa</h3><p>Acesse rapidamente suas equipes, jogadores e jogos salvos.</p></a>
    <?php if (is_admin()): ?><a class="shortcut-card" href="jogadores.php"><span>05</span><h3>Gerenciar elenco</h3><p>Cadastre atletas e números de camisa.</p></a><?php endif; ?>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
