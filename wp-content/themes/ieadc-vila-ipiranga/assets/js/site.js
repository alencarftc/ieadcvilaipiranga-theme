/* IEADC Vila Ipiranga · comportamento do protótipo: menu do celular e carrosséis. */
(function () {
	/* Fontes reais (fase 6): o <link data-ieadc-fontes> sai com media="print" e vira "all" quando a página termina de carregar (inc/desempenho.php).
	   Quem já abriu o site neste navegador (marca no localStorage) as liga logo: estão no cache e a troca de fonte nem aparece. */
	var fl = document.querySelector('link[data-ieadc-fontes]');
	if (fl) {
		var ligaFontes = function () {
			fl.media = 'all';
			try { localStorage.setItem('ieadc-fontes', fl.getAttribute('href')); } catch (e) { /* sem armazenamento: só perde o atalho */ }
		}, jaViu = false;
		try { jaViu = localStorage.getItem('ieadc-fontes') === fl.getAttribute('href'); } catch (e) { jaViu = false; }
		if (jaViu) ligaFontes();
		else if (document.readyState === 'complete') setTimeout(ligaFontes, 150);
		else window.addEventListener('load', function () { setTimeout(ligaFontes, 150); });
	}
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

/* Fase 4: botão flutuante de WhatsApp sai de cena quando o rodapé (com telefone e endereço) está na tela; "Copiar link" do compartilhar. */
(function () {
	var w = document.querySelector('.ieadc-whats'), rodape = document.querySelector('.rodape');
	if (w && rodape && 'IntersectionObserver' in window) {
		new IntersectionObserver(function (itens) {
			w.classList.toggle('ieadc-whats--fim', itens[0].isIntersecting);
		}).observe(rodape);
	}
	document.addEventListener('click', function (e) {
		var b = e.target.closest && e.target.closest('[data-ieadc-copiar]');
		if (!b) return;
		var url = b.getAttribute('data-url') || location.href, aviso = b.parentElement.querySelector('.compartilhar__ok');
		function ok() { if (aviso) { aviso.textContent = 'Link copiado.'; setTimeout(function () { aviso.textContent = ''; }, 4000); } }
		if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(url).then(ok, function () { window.prompt('Copie o link:', url); });
		else window.prompt('Copie o link:', url);
	});
})();

/* Mapa do Google (fase 7): o iframe só entra com um clique em "Ver o mapa do Google" ou, sem clique, se a pessoa já aceitou os cookies (aviso de cookies, medicao.js). */
(function () {
	var botoes = document.querySelectorAll('.mapa [data-mapa-url]');
	if (!botoes.length) return;
	function carregar(b) {
		var caixa = b.closest('.mapa'), f = document.createElement('iframe');
		f.src = b.getAttribute('data-mapa-url');
		f.title = 'Mapa do Google: IEADC Vila Ipiranga';
		f.loading = 'lazy';
		f.referrerPolicy = 'no-referrer-when-downgrade';
		f.setAttribute('allowfullscreen', '');
		caixa.appendChild(f);
		caixa.classList.add('com-iframe');
	}
	var aceito = false;
	try { aceito = window.localStorage.getItem('ieadc_consentimento') === 'aceito'; } catch (e) { /* sem localStorage */ }
	botoes.forEach(function (b) {
		if (aceito) carregar(b); else b.addEventListener('click', function () { carregar(b); });
	});
})();
