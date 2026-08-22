/**
 * Boltfolio front-end interactions.
 * Zero dependencies — progressive enhancement only.
 *
 * - Mobile nav toggle
 * - Copy buttons + language labels on <pre> blocks
 * - "On this page" TOC rail with scroll-spy on docs pages
 */
(function () {
	'use strict';

	/* ---------------------------------------------
	   Mobile navigation
	--------------------------------------------- */
	function initNav() {
		var toggle = document.querySelector('.nav-toggle');
		var nav = document.getElementById('site-nav');

		if (!toggle || !nav) {
			return;
		}

		toggle.addEventListener('click', function () {
			var isOpen = nav.classList.toggle('is-open');
			toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && nav.classList.contains('is-open')) {
				nav.classList.remove('is-open');
				toggle.setAttribute('aria-expanded', 'false');
			}
		});
	}

	/* ---------------------------------------------
	   Code blocks: wrap <pre>, add language label + copy button
	--------------------------------------------- */
	function detectLang(pre) {
		var code = pre.querySelector('code[class*="language-"]');
		if (!code) {
			return 'code';
		}
		var match = code.className.match(/language-([\w+-]+)/);
		return match ? match[1] : 'code';
	}

	function enhanceCodeBlocks() {
		var pres = document.querySelectorAll('.entry-content pre:not(.no-enhance)');

		pres.forEach(function (pre) {
			if (pre.parentElement && pre.parentElement.classList.contains('code-block')) {
				return;
			}

			var wrapper = document.createElement('div');
			wrapper.className = 'code-block';

			var bar = document.createElement('div');
			bar.className = 'code-block-bar';

			var lang = document.createElement('span');
			lang.className = 'code-block-lang';
			lang.textContent = detectLang(pre);

			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'code-block-copy';
			button.setAttribute('aria-label', 'Copy code to clipboard');
			button.innerHTML =
				'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copy';

			button.addEventListener('click', function () {
				copyText(pre.innerText, button);
			});

			bar.appendChild(lang);
			bar.appendChild(button);

			pre.parentNode.insertBefore(wrapper, pre);
			wrapper.appendChild(bar);
			wrapper.appendChild(pre);
		});
	}

	function copyText(text, button) {
		var markCopied = function () {
			button.classList.add('is-copied');
			button.innerHTML =
				'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg> Copied';

			setTimeout(function () {
				button.classList.remove('is-copied');
				button.innerHTML =
					'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copy';
			}, 2000);
		};

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(markCopied).catch(function () {
				fallbackCopy(text, markCopied);
			});
		} else {
			fallbackCopy(text, markCopied);
		}
	}

	function fallbackCopy(text, done) {
		var ta = document.createElement('textarea');
		ta.value = text;
		ta.style.position = 'fixed';
		document.body.appendChild(ta);
		ta.select();

		try {
			document.execCommand('copy');
			done();
		} catch (e) {
			/* clipboard unavailable — silently ignore */
		}

		document.body.removeChild(ta);
	}

	/* ---------------------------------------------
	   Docs TOC rail with numbered links + scroll-spy
	--------------------------------------------- */
	function buildDocsToc() {
		var container = document.querySelector('.docs-toc');
		var list = container ? container.querySelector('.docs-toc-list') : null;

		if (!container || !list) {
			return;
		}

		var headings = document.querySelectorAll(
			'.docs-content .entry-content h2[id]'
		);

		if (!headings.length) {
			container.hidden = true;
			return;
		}

		document.querySelector('.docs-layout').classList.add('has-toc');

		headings.forEach(function (heading, index) {
			var item = document.createElement('li');
			var link = document.createElement('a');

			link.href = '#' + heading.id;
			link.innerHTML =
				'<span class="toc-num">' + (index + 1) + '</span>' +
				heading.textContent.trim();

			item.appendChild(link);
			list.appendChild(item);
		});

		// Scroll-spy: highlight the topmost heading inside the reading zone.
		if ('IntersectionObserver' in window) {
			var visible = new Map();
			var links = list.querySelectorAll('a');
			var byId = {};

			headings.forEach(function (heading, index) {
				byId[heading.id] = links[index];
			});

			var observer = new IntersectionObserver(
				function (entries) {
					entries.forEach(function (entry) {
						visible.set(entry.target.id, entry.isIntersecting);
					});

					var bestId = '';
					var bestTop = Infinity;

					headings.forEach(function (heading) {
						if (!visible.get(heading.id)) {
							return;
						}
						var top = heading.getBoundingClientRect().top;
						if (top < bestTop) {
							bestTop = top;
							bestId = heading.id;
						}
					});

					links.forEach(function (link) {
						link.classList.remove('is-active');
					});

					if (bestId && byId[bestId]) {
						byId[bestId].classList.add('is-active');
					}
				},
				{ rootMargin: '-20% 0px -70% 0px' }
			);

			headings.forEach(function (heading) {
				observer.observe(heading);
			});
		}
	}

	/* ---------------------------------------------
	   Boot
	--------------------------------------------- */
	document.addEventListener('DOMContentLoaded', function () {
		initNav();
		enhanceCodeBlocks();
		buildDocsToc();
	});
})();
