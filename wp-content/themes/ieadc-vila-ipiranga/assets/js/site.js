/* IEADC Vila Ipiranga · comportamento do protótipo: menu do celular e carrosséis. */
(function () {
	/* Quem pede menos movimento (prefers-reduced-motion) recebe a rolagem do carrossel sem animação. */
	function movimentoReduzido() { return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches); }
	var h = document.querySelector('.header'), b = h && h.querySelector('.menu-btn');
	if (b) b.addEventListener('click', function () {
		var o = h.classList.toggle('aberto');
		b.setAttribute('aria-expanded', o);
		b.textContent = o ? 'FECHAR' : 'MENU';
	});
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && h && h.classList.contains('aberto')) { b.click(); b.focus(); }
	});
	document.querySelectorAll('[data-carrossel]').forEach(function (c) {
		var p = c.querySelector('.pista'); if (!p) return;
		var s = p.children, d = c.querySelectorAll('.pontos button'), sec = c.closest('section') || c, i = 0;
		/* Acessibilidade (feita aqui, não no HTML, para o HTML seguir igual ao do protótipo): a pista rola, então
		   precisa receber foco do teclado (setas rolam) e ter nome; o ponto da foto atual é anunciado. */
		var t = sec.querySelector('h1,h2,h3');
		p.setAttribute('tabindex', '0');
		p.setAttribute('role', 'group');
		p.setAttribute('aria-roledescription', 'carrossel');
		p.setAttribute('aria-label', t ? t.textContent.trim() : 'Fotos');
		function ir(n) { i = (n + s.length) % s.length; p.scrollTo({ left: s[i].offsetLeft - p.offsetLeft, behavior: movimentoReduzido() ? 'auto' : 'smooth' }); }
		function marca() { d.forEach(function (x, k) { x.classList.toggle('on', k === i); if (k === i) x.setAttribute('aria-current', 'true'); else x.removeAttribute('aria-current'); }); }
		p.addEventListener('scroll', function () {
			var n = Math.round(p.scrollLeft / (s[0].offsetWidth + 13)); if (n !== i) i = n;
			marca();
		}, { passive: true });
		marca();
		d.forEach(function (x, k) { x.addEventListener('click', function () { ir(k); }); });
		var a = sec.querySelector('.car-ant'), z = sec.querySelector('.car-prox');
		if (a) a.addEventListener('click', function () { ir(i - 1); });
		if (z) z.addEventListener('click', function () { ir(i + 1); });
	});
	document.querySelectorAll('.trocar a.on').forEach(function (a) {
		var t = a.parentElement; t.scrollLeft = a.offsetLeft - t.offsetLeft - (t.clientWidth - a.offsetWidth) / 2;
	});
})();

/* Selo "Próximo": marca o próximo culto da semana pelo horário de Curitiba. */
(function () {
	var itens = document.querySelectorAll('[data-dia][data-hora]');
	if (!itens.length) return;
	var agora = new Date(new Date().toLocaleString('en-US', { timeZone: 'America/Sao_Paulo' }));
	var minAgora = agora.getDay() * 1440 + agora.getHours() * 60 + agora.getMinutes();
	var melhor = null, menor = Infinity;
	itens.forEach(function (el) {
		var h = el.dataset.hora.split(':');
		var m = (+el.dataset.dia) * 1440 + (+h[0]) * 60 + (+h[1]);
		var falta = (m - minAgora + 7 * 1440) % (7 * 1440);
		if (falta < menor) { menor = falta; melhor = el; }
	});
	var cartao = melhor.classList.contains('dia') ? melhor : melhor.closest('.dia');
	if (!cartao) return;
	cartao.classList.add('hoje');
	var tag = document.createElement('span');
	tag.className = 'tag'; tag.textContent = 'Próximo';
	cartao.insertBefore(tag, cartao.firstChild);
})();

/* Fotos de fundo abaixo da primeira seção: carregam quando a seção chega perto da tela (regra em ajustes.css, "Fase 3"). */
(function () {
	var secoes = document.querySelectorAll('.ieadc-pagina > section:not(:first-child)');
	if (!secoes.length) return;
	function mostra(s) { s.classList.add('ieadc-visivel'); }
	if (!('IntersectionObserver' in window)) { secoes.forEach(mostra); return; }
	var io = new IntersectionObserver(function (itens) {
		itens.forEach(function (i) { if (i.isIntersecting) { mostra(i.target); io.unobserve(i.target); } });
	}, { rootMargin: '0px 0px 900px 0px' });
	secoes.forEach(function (s) { io.observe(s); });
})();
