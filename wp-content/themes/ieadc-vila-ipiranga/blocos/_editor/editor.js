/**
 * Editor genérico dos blocos ieadc/* (spec M1). JavaScript puro, sem build.
 *
 * Para cada bloco ieadc/* registrado no servidor (block.json + render.php):
 * - no centro, a prévia é o próprio render.php (ServerSideRender), igual ao site;
 * - no painel lateral, os campos saem dos atributos do block.json e da chave "ieadc"
 *   de cada atributo (vocabulário em docs/specs/00-convencoes.md).
 */
(function (wp) {
	'use strict';
	if (!wp || !wp.blocks || !wp.element) return;

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var RawHTML = wp.element.RawHTML;
	var be = wp.blockEditor;
	var c = wp.components;
	var useSelect = wp.data.useSelect;
	var ServerSideRender = wp.serverSideRender;
	var __ = (wp.i18n && wp.i18n.__) || function (t) { return t; };

	var BLOCOS = window.ieadcBlocos || {};
	var ICONES = window.ieadcIcones || {};
	var AVANCADO = ['secao', 'secaoEstilo', 'ancora'];
	var ROTULOS_AVANCADO = { secao: 'Classes da seção', secaoEstilo: 'Estilo da seção', ancora: 'Âncora (id)' };

	/* ---------- utilidades ---------- */

	// "botaoTexto" → "Botão texto"; "eyebrow" → "Eyebrow"
	function humanizar(nome) {
		var t = String(nome).replace(/([a-z0-9])([A-Z])/g, '$1 $2').replace(/[-_]+/g, ' ').toLowerCase();
		return t.charAt(0).toUpperCase() + t.slice(1);
	}

	function cfg(def) { return (def && def.ieadc) || {}; }

	// Tipo do campo: "ieadc.campo" ou o padrão pelo tipo do atributo.
	function campoDe(def) {
		var c0 = cfg(def).campo;
		if (c0) return c0;
		var tipo = def && def.type;
		if (Array.isArray(tipo)) tipo = tipo[0];
		if (tipo === 'boolean') return 'sim-nao';
		if (tipo === 'number' || tipo === 'integer') return 'numero';
		if (tipo === 'array') return cfg(def).itens ? 'lista' : 'imagens';
		return 'texto';
	}

	function rotuloDe(nome, def) { return cfg(def).rotulo || ROTULOS_AVANCADO[nome] || humanizar(nome); }

	function padraoDe(def) {
		if (def && def['default'] !== undefined) return JSON.parse(JSON.stringify(def['default']));
		var tipo = def && def.type;
		if (tipo === 'array') return [];
		if (tipo === 'object') return {};
		if (tipo === 'number' || tipo === 'integer') return 0;
		if (tipo === 'boolean') return false;
		return '';
	}

	// "lista": itens com sub-atributos ({titulo: {...}, texto: {...}}) ou de um valor só ({type: "string"}).
	function itensSaoSimples(itens) { return itens && typeof itens.type === 'string' && !itens.type.ieadc; }

	function novoItem(itens) {
		if (itensSaoSimples(itens)) return padraoDe(itens);
		var o = {};
		Object.keys(itens || {}).forEach(function (k) { o[k] = padraoDe(itens[k]); });
		return o;
	}

	// Link do site gravado como caminho ("/contato/"), para continuar valendo depois da migração.
	function linkRelativo(url) {
		if (!url) return '';
		var origem = window.location.origin;
		if (url.indexOf(origem) === 0) return url.slice(origem.length) || '/';
		return url;
	}

	function resumo(valor) {
		if (valor == null) return '';
		if (typeof valor !== 'object') return String(valor);
		for (var k in valor) {
			if (typeof valor[k] === 'string' && valor[k].trim()) return valor[k];
		}
		return '';
	}

	function curto(t) {
		t = String(t || '').replace(/<[^>]+>/g, '').trim();
		return t.length > 40 ? t.slice(0, 38) + '…' : t;
	}

	/* ---------- campos ---------- */

	function Miniatura(props) {
		var midia = useSelect(function (select) {
			return props.id ? select('core').getMedia(props.id, { context: 'view' }) : null;
		}, [props.id]);
		if (!props.id) return null;
		var url = midia && ((midia.media_details && midia.media_details.sizes && midia.media_details.sizes.thumbnail && midia.media_details.sizes.thumbnail.source_url) || midia.source_url);
		return el('span', { className: 'ieadc-mini' }, url ? el('img', { src: url, alt: '' }) : el(c.Spinner));
	}

	function CampoImagem(props) {
		var valor = props.valor || 0;
		return el(c.BaseControl, { label: props.rotulo, help: props.ajuda, __nextHasNoMarginBottom: true },
			el('div', { className: 'ieadc-imagem' },
				el(Miniatura, { id: valor }),
				el(be.MediaUploadCheck, null,
					el(be.MediaUpload, {
						allowedTypes: ['image'],
						value: valor,
						onSelect: function (m) { props.muda(m && m.id ? m.id : 0); },
						render: function (o) {
							return el(c.Button, { variant: 'secondary', onClick: o.open }, valor ? 'Trocar foto' : 'Escolher foto');
						}
					})
				),
				valor ? el(c.Button, { variant: 'tertiary', isDestructive: true, onClick: function () { props.muda(0); } }, 'Remover') : null
			)
		);
	}

	function CampoImagens(props) {
		var lista = Array.isArray(props.valor) ? props.valor : [];
		function mover(i, d) {
			var n = lista.slice(), j = i + d;
			if (j < 0 || j >= n.length) return;
			var t = n[i]; n[i] = n[j]; n[j] = t;
			props.muda(n);
		}
		return el(c.BaseControl, { label: props.rotulo, help: props.ajuda, __nextHasNoMarginBottom: true },
			el('div', { className: 'ieadc-galeria' },
				lista.map(function (id, i) {
					return el('div', { className: 'ieadc-galeria-item', key: id + '-' + i },
						el(Miniatura, { id: id }),
						el('div', { className: 'ieadc-galeria-botoes' },
							el(c.Button, { icon: 'arrow-left-alt2', label: 'Antes', size: 'small', disabled: i === 0, onClick: function () { mover(i, -1); } }),
							el(c.Button, { icon: 'no-alt', label: 'Remover', size: 'small', isDestructive: true, onClick: function () { props.muda(lista.filter(function (_, k) { return k !== i; })); } }),
							el(c.Button, { icon: 'arrow-right-alt2', label: 'Depois', size: 'small', disabled: i === lista.length - 1, onClick: function () { mover(i, 1); } })
						)
					);
				})
			),
			el(be.MediaUploadCheck, null,
				el(be.MediaUpload, {
					allowedTypes: ['image'],
					multiple: true,
					gallery: true,
					value: lista,
					onSelect: function (ms) { props.muda((ms || []).map(function (m) { return m.id; })); },
					render: function (o) {
						return el(c.Button, { variant: 'secondary', onClick: o.open }, lista.length ? 'Editar fotos' : 'Escolher fotos');
					}
				})
			)
		);
	}

	function CampoIcone(props) {
		var nomes = Object.keys(ICONES).sort();
		return el(c.BaseControl, { label: props.rotulo, help: props.ajuda, __nextHasNoMarginBottom: true },
			el('div', { className: 'ieadc-icones', role: 'listbox', 'aria-label': props.rotulo },
				el(c.Button, { className: 'ieadc-icone' + (!props.valor ? ' is-on' : ''), label: 'Nenhum', showTooltip: true, onClick: function () { props.muda(''); } }, '—'),
				nomes.map(function (n) {
					return el(c.Button, {
						key: n,
						className: 'ieadc-icone' + (props.valor === n ? ' is-on' : ''),
						label: n,
						showTooltip: true,
						'aria-pressed': props.valor === n,
						onClick: function () { props.muda(n); }
					}, el(RawHTML, null, ICONES[n]));
				})
			)
		);
	}

	function CampoLink(props) {
		return el(c.BaseControl, { label: props.rotulo, help: props.ajuda || 'Digite para buscar uma página do site, ou cole um endereço.', __nextHasNoMarginBottom: true },
			el(be.URLInput, {
				value: props.valor || '',
				__nextHasNoMarginBottom: true,
				onChange: function (url) { props.muda(linkRelativo(url)); }
			})
		);
	}

	function CampoLista(props) {
		var itens = props.def ? cfg(props.def).itens : null;
		var lista = Array.isArray(props.valor) ? props.valor : [];
		var simples = itensSaoSimples(itens);
		function muda(n) { props.muda(n); }
		function troca(i, v) { var n = lista.slice(); n[i] = v; muda(n); }
		function mover(i, d) {
			var n = lista.slice(), j = i + d;
			if (j < 0 || j >= n.length) return;
			var t = n[i]; n[i] = n[j]; n[j] = t;
			muda(n);
		}
		return el('div', { className: 'ieadc-lista' },
			el('p', { className: 'ieadc-lista-rotulo' }, props.rotulo + ' (' + lista.length + ')'),
			props.ajuda ? el('p', { className: 'ieadc-ajuda' }, props.ajuda) : null,
			lista.map(function (item, i) {
				var titulo = (i + 1) + '. ' + (curto(resumo(item)) || 'Item');
				var corpo = simples
					? Campo({ nome: 'item', def: itens, rotulo: 'Texto', valor: item, muda: function (v) { troca(i, v); } })
					: Object.keys(itens || {}).map(function (k) {
						return Campo({
							key: k, nome: k, def: itens[k], valor: item ? item[k] : undefined,
							muda: function (v) { var o = Object.assign({}, item); o[k] = v; troca(i, o); }
						});
					});
				return el(c.PanelBody, { key: i, title: titulo, initialOpen: false, className: 'ieadc-item' },
					corpo,
					el('div', { className: 'ieadc-item-botoes' },
						el(c.Button, { variant: 'secondary', size: 'small', disabled: i === 0, onClick: function () { mover(i, -1); } }, 'Subir'),
						el(c.Button, { variant: 'secondary', size: 'small', disabled: i === lista.length - 1, onClick: function () { mover(i, 1); } }, 'Descer'),
						el(c.Button, { variant: 'tertiary', size: 'small', isDestructive: true, onClick: function () { muda(lista.filter(function (_, k) { return k !== i; })); } }, 'Remover')
					)
				);
			}),
			el(c.Button, { variant: 'secondary', className: 'ieadc-adicionar', onClick: function () { muda(lista.concat([novoItem(itens)])); } }, 'Adicionar')
		);
	}

	// Um campo do painel. Recebe {nome, def, valor, muda} e devolve o controle certo.
	function Campo(p) {
		var def = p.def || {};
		var conf = cfg(def);
		var tipo = campoDe(def);
		var rotulo = p.rotulo || rotuloDe(p.nome, def);
		var ajuda = conf.ajuda;
		var valor = p.valor === undefined ? padraoDe(def) : p.valor;
		var base = { key: p.key || p.nome, label: rotulo, help: ajuda, __nextHasNoMarginBottom: true, __next40pxDefaultSize: true };
		var props = { key: p.key || p.nome, rotulo: rotulo, ajuda: ajuda, valor: valor, muda: p.muda, def: def };

		switch (tipo) {
			case 'texto-longo':
				return el(c.TextareaControl, Object.assign(base, { value: valor || '', rows: 6, onChange: p.muda, help: ajuda || 'Deixe uma linha em branco entre os parágrafos.' }));
			case 'rico':
				return el(c.TextareaControl, Object.assign(base, { value: valor || '', rows: 4, onChange: p.muda, help: ajuda || 'Aceita <b>, <i>, <a href="…">, <br> e <span class="azul">.' }));
			case 'imagem':
				return el(CampoImagem, props);
			case 'imagens':
				return el(CampoImagens, props);
			case 'link':
				return el(CampoLink, props);
			case 'icone':
				return el(CampoIcone, props);
			case 'opcao':
				var opcoes = (conf.opcoes || def['enum'] || []).map(function (o) {
					return typeof o === 'object' ? { value: o.valor, label: o.rotulo || o.valor } : { value: o, label: o === '' ? '(padrão)' : o };
				});
				return el(c.SelectControl, Object.assign(base, { value: valor, options: opcoes, onChange: p.muda }));
			case 'lista':
				return el(CampoLista, props);
			case 'sim-nao':
				return el(c.ToggleControl, Object.assign(base, { checked: !!valor, onChange: p.muda }));
			case 'numero':
				return el(c.TextControl, Object.assign(base, {
					type: 'number', value: valor === null || valor === undefined ? '' : valor,
					onChange: function (v) { p.muda(v === '' ? undefined : Number(v)); }
				}));
			default:
				return el(c.TextControl, Object.assign(base, { value: valor || '', onChange: p.muda }));
		}
	}

	/* ---------- edit/save ---------- */

	function criarEdit(nome, defs) {
		var visiveis = Object.keys(defs).filter(function (k) { return AVANCADO.indexOf(k) < 0 && !cfg(defs[k]).oculto && ['lock', 'metadata', 'className', 'style'].indexOf(k) < 0; });
		var avancados = Object.keys(defs).filter(function (k) { return AVANCADO.indexOf(k) >= 0; });

		return function Edit(props) {
			var at = props.attributes;
			var blockProps = be.useBlockProps({ className: 'ieadc-bloco' });
			function campos(lista) {
				return lista.map(function (k) {
					return Campo({ nome: k, def: defs[k], valor: at[k], muda: function (v) { var o = {}; o[k] = v; props.setAttributes(o); } });
				});
			}
			var titulo = (BLOCOS[nome] && BLOCOS[nome].title) || nome;
			return el(Fragment, null,
				el(be.InspectorControls, null,
					visiveis.length ? el(c.PanelBody, { title: 'Conteúdo', initialOpen: true }, campos(visiveis)) : null,
					avancados.length ? el(c.PanelBody, { title: 'Avançado', initialOpen: false }, campos(avancados)) : null
				),
				el('div', blockProps,
					el('div', { className: 'ieadc-ui ieadc-pagina' },
						el(ServerSideRender, {
							block: nome,
							attributes: at,
							httpMethod: 'POST',
							skipBlockSupportAttributes: true,
							EmptyResponsePlaceholder: function () {
								return el(c.Placeholder, { label: titulo, instructions: 'Bloco vazio: preencha os campos no painel ao lado.' });
							}
						})
					)
				)
			);
		};
	}

	Object.keys(BLOCOS).forEach(function (nome) {
		if (nome.indexOf('ieadc/') !== 0) return;
		var tipo = wp.blocks.getBlockType(nome);
		if (tipo && tipo.edit && tipo.edit.ieadcGenerico) return;
		var defs = BLOCOS[nome].attributes || {};
		var edit = criarEdit(nome, defs);
		edit.ieadcGenerico = true;
		var config = { edit: edit, save: function () { return null; } };
		if (tipo) {
			// Já veio do servidor (block.json): troca só edit/save.
			wp.blocks.unregisterBlockType(nome);
			wp.blocks.registerBlockType(nome, Object.assign({}, tipo, config));
		} else {
			wp.blocks.registerBlockType(nome, Object.assign({
				apiVersion: 3,
				title: BLOCOS[nome].title || nome,
				category: 'ieadc',
				icon: BLOCOS[nome].icon || 'layout',
				attributes: defs,
				supports: { html: false }
			}, config));
		}
	});
})(window.wp);
