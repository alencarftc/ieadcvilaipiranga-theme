/* IEADC Vila Ipiranga · galeria (fase 7): clicar numa foto abre a foto ampliada numa janela (<dialog>), com setas na tela, setas do teclado e deslize no celular.
   O HTML das fotos não muda (continuam <div> com a foto de fundo, igual ao protótipo): o papel de botão, o foco e o rótulo entram aqui. Esc, o X e o clique fora fecham. */
(function () {
	var tiles = [].slice.call(document.querySelectorAll('.fotos > div, .galeria-faixa .mosaico > div'));
	if (!tiles.length || typeof HTMLDialogElement === 'undefined') return;

	function endereco(el) { var m = /url\(["']?([^"')]+)["']?\)/.exec(el.style.backgroundImage || ''); return m ? m[1] : ''; }
	var fotos = tiles.map(endereco), n = tiles.length, atual = 0, origem = null;
	/* As miniaturas são a versão de 768 px do WordPress (nome-768x512.webp); a janela ampliada usa a foto original (sem o -LxA do nome). */
	function grande(u) { return u.replace(/-\d+x\d+(\.[a-z0-9]+)$/i, '$1'); }

	/* Carregamento por foto: a galeria tem dezenas de fotos; só as primeiras carregam de saída e as outras quando chegam perto da tela
	   (o endereço de cada foto já está guardado em `fotos`, que também alimenta a janela ampliada). */
	if ('IntersectionObserver' in window) {
		var io = new IntersectionObserver(function (itens) {
			itens.forEach(function (i) {
				if (!i.isIntersecting) return;
				i.target.style.backgroundImage = 'url("' + fotos[tiles.indexOf(i.target)] + '")';
				io.unobserve(i.target);
			});
		}, { rootMargin: '250px 0px' });
		tiles.forEach(function (t) { t.style.backgroundImage = 'none'; io.observe(t); });
	}
	function icone(d) { return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' + d + '"/></svg>'; }

	var d = document.createElement('dialog');
	d.className = 'lightbox';
	d.setAttribute('aria-label', 'Foto ampliada');
	d.innerHTML = '<button type="button" class="lb-btn lb-fechar" aria-label="Fechar">' + icone('M6 6l12 12M18 6 6 18') + '</button>'
		+ '<button type="button" class="lb-btn lb-ant" aria-label="Foto anterior">' + icone('m15 5-7 7 7 7') + '</button>'
		+ '<figure class="lb-fig"><img alt="" decoding="async"><figcaption class="lb-cont" aria-live="polite"></figcaption></figure>'
		+ '<button type="button" class="lb-btn lb-prox" aria-label="Próxima foto">' + icone('m9 5 7 7-7 7') + '</button>';
	document.body.appendChild(d);
	var img = d.querySelector('img'), cont = d.querySelector('.lb-cont');

	function mostrar(i) {
		atual = (i + n) % n;
		img.src = grande(fotos[atual]);
		img.alt = 'Foto ' + (atual + 1) + ' de ' + n;
		cont.textContent = (atual + 1) + ' de ' + n;
		[1, -1].forEach(function (k) { var p = new Image(); p.src = grande(fotos[(atual + k + n) % n]); }); // as vizinhas já ficam carregadas
	}
	function abrir(i) {
		origem = tiles[i];
		mostrar(i);
		if (!d.open) d.showModal();
		document.documentElement.classList.add('lb-aberto');
	}
	function fechar() { if (d.open) d.close(); }
	d.addEventListener('close', function () {
		document.documentElement.classList.remove('lb-aberto');
		if (origem) { origem.focus({ preventScroll: true }); origem = null; }
	});

	tiles.forEach(function (t, i) {
		t.setAttribute('role', 'button');
		t.setAttribute('tabindex', '0');
		t.setAttribute('aria-label', 'Ampliar a foto ' + (i + 1) + ' de ' + n);
		t.addEventListener('click', function () { abrir(i); });
		t.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); abrir(i); } });
	});
	d.querySelector('.lb-fechar').addEventListener('click', fechar);
	d.querySelector('.lb-ant').addEventListener('click', function () { mostrar(atual - 1); });
	d.querySelector('.lb-prox').addEventListener('click', function () { mostrar(atual + 1); });
	d.addEventListener('click', function (e) { if (e.target === d) fechar(); }); // clique no fundo escuro
	d.addEventListener('keydown', function (e) {
		if (e.key === 'ArrowLeft') { e.preventDefault(); mostrar(atual - 1); }
		else if (e.key === 'ArrowRight') { e.preventDefault(); mostrar(atual + 1); }
	});

	/* Deslizar o dedo (ou o mouse) para os lados troca de foto. */
	var x0 = null;
	d.addEventListener('pointerdown', function (e) { if (e.target === img) x0 = e.clientX; });
	d.addEventListener('pointerup', function (e) {
		if (x0 === null) return;
		var dx = e.clientX - x0; x0 = null;
		if (Math.abs(dx) > 50) mostrar(atual + (dx < 0 ? 1 : -1));
	});
	d.addEventListener('pointercancel', function () { x0 = null; });
})();
