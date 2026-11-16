/* IEADC Vila Ipiranga · medição de acessos com consentimento (LGPD). Só é carregado quando há um ID em Configurações (inc/medicao.php).
   - Consent Mode v2: tudo "negado" até a pessoa aceitar o aviso. "Recusar" não carrega nada.
   - Só DEPOIS do "Aceitar": consent update (analytics_storage) e carga do Google Tag Manager (gtm.js) ou do Google Analytics 4 (gtag.js),
     do Microsoft Clarity e do Meta Pixel, conforme os IDs preenchidos. A escolha fica em localStorage e num cookie próprio (180 dias).
   - Eventos (sem dado pessoal; só o tipo): dataLayer.push({event: 'ieadc_...', ...}) no GTM, gtag('event', ...) no GA4 direto.
     Só são enviados depois do consentimento. Lista, parâmetros e passo a passo no GTM: docs/operacao/rastreamento.md. */
(function () {
	'use strict';
	var cfgEl = document.getElementById('ieadc-medicao'), aviso = document.getElementById('ieadc-cookies');
	if (!cfgEl || !aviso) return;
	var cfg;
	try { cfg = JSON.parse(cfgEl.textContent); } catch (e) { return; }

	var CHAVE = 'ieadc_consentimento', VALIDADE = 15552000; // 180 dias
	var consentido = false, carregado = false, abridor = null;

	window.dataLayer = window.dataLayer || [];
	function gtag() { window.dataLayer.push(arguments); }

	// Consent Mode v2: negado por padrão (antes de qualquer tag).
	gtag('consent', 'default', { ad_storage: 'denied', analytics_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied' });

	/* ---------- escolha guardada ---------- */
	function ler() {
		try { var v = window.localStorage.getItem(CHAVE); if (v === 'aceito' || v === 'recusado') return v; } catch (e) { /* sem localStorage */ }
		var m = document.cookie.match(new RegExp('(?:^|; )' + CHAVE + '=([^;]*)'));
		return m && (m[1] === 'aceito' || m[1] === 'recusado') ? m[1] : null;
	}
	function gravar(v) {
		try { window.localStorage.setItem(CHAVE, v); } catch (e) { /* sem localStorage */ }
		document.cookie = CHAVE + '=' + v + '; max-age=' + VALIDADE + '; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
	}

	/* ---------- carga dos serviços (só depois de aceitar) ---------- */
	function script(src) {
		var s = document.createElement('script');
		s.async = true; s.src = src;
		document.head.appendChild(s);
	}
	function carregar() {
		if (carregado) { consentido = true; return; }
		carregado = true; consentido = true;
		// Só a medição de acessos é liberada; anúncios (ad_*) seguem negados: o site não faz publicidade.
		gtag('consent', 'update', { analytics_storage: 'granted' });
		if (cfg.gtm) {
			window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
			script('https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(cfg.gtm));
		} else if (cfg.ga4) {
			script('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(cfg.ga4));
			gtag('js', new Date());
			gtag('config', cfg.ga4, { allow_google_signals: false, allow_ad_personalization_signals: false });
		}
		if (cfg.clarity) {
			(function (c, l, a, r, i) {
				c[a] = c[a] || function () { (c[a].q = c[a].q || []).push(arguments); };
				var t = l.createElement(r); t.async = 1; t.src = 'https://www.clarity.ms/tag/' + i;
				var y = l.getElementsByTagName(r)[0]; y.parentNode.insertBefore(t, y);
			})(window, document, 'clarity', 'script', cfg.clarity);
		}
		if (cfg.pixel) {
			(function (f, b, e, v) {
				if (f.fbq) return;
				var n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); };
				if (!f._fbq) f._fbq = n;
				n.push = n; n.loaded = true; n.version = '2.0'; n.queue = [];
				var t = b.createElement(e); t.async = true; t.src = v;
				var s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s);
			})(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
			window.fbq('init', cfg.pixel);
			window.fbq('track', 'PageView');
		}
		eventosDaPagina();
	}
	// Quem recusa (ou muda de ideia depois de aceitar) para de enviar eventos e perde os cookies de medição.
	function parar() {
		consentido = false;
		if (carregado) gtag('consent', 'update', { analytics_storage: 'denied' });
		var nomes = document.cookie.split('; ').map(function (c) { return c.split('=')[0]; });
		var partes = location.hostname.split('.'), dominios = ['', location.hostname];
		if (partes.length > 2) dominios.push('.' + partes.slice(-2).join('.'));
		dominios.push('.' + location.hostname);
		nomes.forEach(function (n) {
			if (!/^(_ga|_gid|_gat|_gcl|_clck|_clsk|_fbp|_fbc)/.test(n) && ['CLID', 'ANONCHK', 'MUID', 'SM', 'MR'].indexOf(n) === -1) return;
			dominios.forEach(function (d) { document.cookie = n + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/' + (d ? '; domain=' + d : ''); });
		});
	}

	/* ---------- eventos (sem dado pessoal) ---------- */
	function evento(nome, dados) {
		if (!consentido) return;
		dados = dados || {};
		if (cfg.gtm) {
			var o = { event: nome };
			for (var k in dados) { if (Object.prototype.hasOwnProperty.call(dados, k)) o[k] = dados[k]; }
			window.dataLayer.push(o);
		} else if (cfg.ga4) {
			gtag('event', nome, dados);
		}
	}
	function onde(el) {
		if (el.closest('.ieadc-whats')) return 'flutuante';
		if (el.closest('footer')) return 'rodape';
		if (el.closest('header')) return 'cabecalho';
		return 'conteudo';
	}
	document.addEventListener('click', function (e) {
		var t = e.target;
		if (!t || !t.closest) return;
		var a = t.closest('a'), b = t.closest('button');
		if (b && b.hasAttribute('data-ieadc-copiar')) { evento('ieadc_compartilhar', { rede: 'copiar_link' }); return; }
		if (!a) return;
		var h = (a.getAttribute('href') || '').toLowerCase(), rede = a.getAttribute('data-ieadc-rede');
		if (rede) { evento('ieadc_compartilhar', { rede: rede }); return; }
		if (h.indexOf('tel:') === 0) { evento('ieadc_clique_telefone', { local: onde(a) }); return; }
		if (/^https?:\/\/(wa\.me|api\.whatsapp\.com|(www\.)?whatsapp\.com)\//.test(h)) { evento('ieadc_clique_whatsapp', { local: onde(a) }); return; }
		if (a.closest('.avalie')) { evento('ieadc_avaliar_google'); return; }
		if (/\.ics(\?|$)/.test(h)) { evento('ieadc_adicionar_calendario', { tipo: 'ics' }); return; }
		if (h.indexOf('calendar.google.com') !== -1) { evento('ieadc_adicionar_calendario', { tipo: 'google_agenda' }); return; }
		if (/waze\.com\//.test(h)) { evento('ieadc_clique_como_chegar', { destino: 'waze' }); return; }
		if (/google\.[a-z.]+\/maps|maps\.app\.goo\.gl|goo\.gl\/maps/.test(h)) { evento('ieadc_clique_como_chegar', { destino: 'google_maps' }); return; }
		if (/como chegar/i.test(a.textContent || '')) { evento('ieadc_clique_como_chegar', { destino: 'pagina' }); return; }
		if (a.classList.contains('cartao') && a.classList.contains('evento')) { evento('ieadc_clique_evento'); return; }
		if (a.classList.contains('cartao') && a.querySelector('.img') && a.querySelector('.chip')) { evento('ieadc_clique_artigo'); return; }
	}, true);
	document.addEventListener('submit', function (e) {
		var f = e.target;
		if (f && f.matches && f.matches('form.form')) evento('ieadc_formulario_enviado', { tipo: f.id || 'formulario' });
	}, true);
	var sucessoEnviado = false;
	function eventosDaPagina() {
		if (sucessoEnviado) return;
		sucessoEnviado = true;
		var msgs = document.querySelectorAll('form.form .msg:not(.erro)');
		Array.prototype.forEach.call(msgs, function (m) { evento('ieadc_formulario_sucesso', { tipo: (m.closest('form') || {}).id || 'formulario' }); });
	}

	/* ---------- aviso ---------- */
	function altura() { document.documentElement.style.setProperty('--ieadc-cookies-h', aviso.hidden ? '0px' : aviso.offsetHeight + 'px'); }
	function mostrar(foco) {
		aviso.hidden = false;
		document.documentElement.classList.add('ieadc-cookies-aberto');
		altura();
		// Foco gerenciado: o aviso fica no fim do HTML (caixa de diálogo não modal); ao aparecer, leva o foco até ele, sem rolar e sem prender (o Tab sai).
		if (foco) { try { aviso.focus({ preventScroll: true }); } catch (e) { aviso.focus(); } }
	}
	function esconder() {
		aviso.hidden = true;
		document.documentElement.classList.remove('ieadc-cookies-aberto');
		altura();
		// Foco gerenciado: quem usa teclado ou leitor de tela não fica "no ar" depois que o aviso some.
		var alvo = abridor && document.contains(abridor) ? abridor : document.getElementById('main');
		abridor = null;
		if (alvo) {
			if (!alvo.hasAttribute('tabindex') && alvo.id === 'main') alvo.setAttribute('tabindex', '-1');
			try { alvo.focus({ preventScroll: true }); } catch (e) { alvo.focus(); }
		}
	}
	aviso.addEventListener('click', function (e) {
		var b = e.target.closest && e.target.closest('[data-ieadc-cookies]');
		if (!b) return;
		if (b.getAttribute('data-ieadc-cookies') === 'aceitar') { gravar('aceito'); carregar(); } else { gravar('recusado'); parar(); }
		esconder();
	});
	// Botão "Mudar minha escolha" (política de privacidade): reabre o aviso e leva o foco até ele.
	document.addEventListener('click', function (e) {
		var b = e.target.closest && e.target.closest('[data-ieadc-cookies-abrir]');
		if (!b) return;
		abridor = b;
		mostrar();
		var primeiro = aviso.querySelector('button');
		if (primeiro) primeiro.focus();
	});
	window.addEventListener('resize', function () { if (!aviso.hidden) altura(); });

	var guardada = ler();
	if (guardada === 'aceito') carregar();
	else if (guardada !== 'recusado') mostrar(true);
})();
