/* IEADC Vila Ipiranga · comportamento do protótipo: menu do celular e carrosséis. */
(function () {
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
		function ir(n) { i = (n + s.length) % s.length; p.scrollTo({ left: s[i].offsetLeft - p.offsetLeft, behavior: 'smooth' }); }
		p.addEventListener('scroll', function () {
			var n = Math.round(p.scrollLeft / (s[0].offsetWidth + 13)); if (n !== i) i = n;
			d.forEach(function (x, k) { x.classList.toggle('on', k === i); });
		}, { passive: true });
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
