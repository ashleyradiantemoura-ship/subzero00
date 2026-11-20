/* SUBZERO — tema escuro: menu mobile, entrada ao rolar e contagem do hero */
(function () {
  "use strict";
  document.documentElement.classList.add("js");
  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  var burger = document.getElementById("burger");
  var links = document.getElementById("siteNav");
  if (burger && links) {
    burger.addEventListener("click", function () { links.classList.toggle("is-open"); });
    links.querySelectorAll("a").forEach(function (a) {
      a.addEventListener("click", function () { links.classList.remove("is-open"); });
    });
  }

  var els = document.querySelectorAll(".reveal");
  if ("IntersectionObserver" in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        en.target.classList.add("in");
        io.unobserve(en.target);
      });
    }, { threshold: 0.12 });
    els.forEach(function (el, i) {
      el.style.transitionDelay = (i % 4) * 90 + "ms";
      io.observe(el);
    });
  } else {
    els.forEach(function (el) { el.classList.add("in"); });
  }

  document.querySelectorAll(".stat__num").forEach(function (el) {
    var txt = el.textContent.trim();
    if (!/^\d+$/.test(txt) || reduce) return;
    var n = parseInt(txt, 10), t0 = null;
    (function step(t) {
      t0 = t0 || t;
      var p = Math.min((t - t0) / 1200, 1);
      el.textContent = Math.round(n * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(step);
    })(performance.now());
  });

  /* Carrossel de times (home) */
  var track = document.getElementById("gearTrack");
  var prev = document.getElementById("gearPrev");
  var next = document.getElementById("gearNext");
  if (track && prev && next) {
    var passo = function () {
      var card = track.querySelector(".gcard");
      return card ? card.getBoundingClientRect().width + 16 : track.clientWidth * 0.8;
    };
    var atualiza = function () {
      var max = track.scrollWidth - track.clientWidth - 2;
      prev.disabled = track.scrollLeft <= 2;
      next.disabled = track.scrollLeft >= max;
    };
    prev.addEventListener("click", function () { track.scrollBy({ left: -passo(), behavior: reduce ? "auto" : "smooth" }); });
    next.addEventListener("click", function () { track.scrollBy({ left: passo(), behavior: reduce ? "auto" : "smooth" }); });
    track.addEventListener("scroll", atualiza, { passive: true });
    window.addEventListener("resize", atualiza);
    track.addEventListener("keydown", function (ev) {
      if (ev.key === "ArrowRight") { ev.preventDefault(); next.click(); }
      if (ev.key === "ArrowLeft") { ev.preventDefault(); prev.click(); }
    });
    atualiza();
  }
})();
