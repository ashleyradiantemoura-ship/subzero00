/* SUBZERO — efeitos de rolagem */
(function () {
  document.documentElement.classList.add('js');
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Barra de progresso de leitura
  var bar = document.createElement('div');
  bar.id = 'scrollBar';
  document.body.appendChild(bar);

  var header = document.getElementById('siteHeader');
  var hero = document.querySelector('.page-hero-overlay, .login-hero-overlay');
  var title = document.querySelector('.page-title, .hero-title');
  var ticking = false;

  function onScroll() {
    var y = window.scrollY;
    var max = document.documentElement.scrollHeight - window.innerHeight;
    bar.style.width = (max > 0 ? (y / max) * 100 : 0) + '%';
    if (header) header.classList.toggle('scrolled', y > 40);
    if (!reduce && y < 900) {
      if (hero) hero.style.transform = 'translateY(' + y * 0.35 + 'px)';
      if (title) { title.style.transform = 'translateY(' + y * 0.12 + 'px)'; title.style.opacity = Math.max(0, 1 - y / 700); }
    }
    ticking = false;
  }
  window.addEventListener('scroll', function () {
    if (!ticking) { requestAnimationFrame(onScroll); ticking = true; }
  }, { passive: true });
  onScroll();

  // Menu mobile
  var toggle = document.getElementById('navToggle');
  var nav = document.getElementById('siteNav');
  if (toggle && nav) toggle.addEventListener('click', function () { nav.classList.toggle('open'); });

  // Revelar elementos ao rolar
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (!en.isIntersecting) return;
      en.target.classList.add('in');
      io.unobserve(en.target);
    });
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal, .rank-table').forEach(function (el, i) {
    if (el.classList.contains('reveal')) el.style.transitionDelay = (i % 4) * 90 + 'ms';
    io.observe(el);
  });

  // Linhas da tabela entram em sequência
  document.querySelectorAll('.rank-table tbody tr').forEach(function (tr, i) {
    tr.style.transitionDelay = (i * 70) + 'ms';
  });

  // Contagem animada nos números do hero
  document.querySelectorAll('.hero-stats strong').forEach(function (el) {
    var n = parseInt(el.textContent, 10);
    if (!/^\d+$/.test(el.textContent.trim()) || reduce) return;
    var t0 = null;
    (function step(t) {
      t0 = t0 || t;
      var p = Math.min((t - t0) / 1200, 1);
      el.textContent = Math.round(n * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(step);
    })(performance.now());
  });

  // Flocos de neve no hero
  var host = document.querySelector('.page-hero, .login-hero');
  if (host && !reduce) {
    for (var i = 0; i < 16; i++) {
      var f = document.createElement('span');
      f.className = 'flake';
      f.textContent = '❄';
      f.style.left = Math.random() * 100 + '%';
      f.style.top = '-30px';
      f.style.fontSize = (10 + Math.random() * 16) + 'px';
      f.style.animationDuration = (8 + Math.random() * 10) + 's';
      f.style.animationDelay = (-Math.random() * 14) + 's';
      host.appendChild(f);
    }
  }
})();
