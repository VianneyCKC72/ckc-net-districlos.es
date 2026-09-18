/**
 * Panneau assistant shopping (YourGPT API via module PrestaShop).
 */
(function () {
    'use strict';

    var panelEl = null;
    var messagesEl = null;
    var inputEl = null;
    var formEl = null;
    var sendBtn = null;
    var sessionUid = null;
    var busy = false;
    var config = window.dtcClosy || {};
    var MAX_PRODUCT_URLS = 6;
    var feedbackSocket = null;
    var feedbackSocketPromise = null;
    var visitorRegistered = false;
    var lastUserMessage = '';
    var historyRestorePromise = null;

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function getRoot() {
        return document.querySelector('.dtc-closy-questions');
    }

    function sessionStorageKey() {
        var id = getContextProductId();
        return 'dtc_closy_yourgpt_session_' + (id || 'global');
    }

    function loadStoredSession() {
        var key = sessionStorageKey();
        try {
            var fromLocal = localStorage.getItem(key);
            if (fromLocal) {
                return fromLocal;
            }
            var legacy = sessionStorage.getItem(key);
            if (legacy) {
                localStorage.setItem(key, legacy);
                sessionStorage.removeItem(key);
                return legacy;
            }
            return null;
        } catch (e) {
            return null;
        }
    }

    function saveStoredSession(uid) {
        try {
            if (uid) {
                localStorage.setItem(sessionStorageKey(), String(uid));
            }
        } catch (e) {
            /* ignore */
        }
    }

    function clearStoredSession() {
        try {
            var key = sessionStorageKey();
            var storedUid = localStorage.getItem(key) || sessionStorage.getItem(key);
            localStorage.removeItem(key);
            sessionStorage.removeItem(key);
            if (storedUid) {
                localStorage.removeItem(sessionMessageCountKey(storedUid));
            }
        } catch (e) {
            /* ignore */
        }
    }

    function mapHistoryRole(sendBy) {
        var role = String(sendBy || '').toLowerCase();
        if (role === 'user' || role === 'visitor' || role === 'human' || role === 'customer') {
            return 'user';
        }
        if (
            role === 'bot' ||
            role === 'assistant' ||
            role === 'ai' ||
            role === 'agent' ||
            role === 'chatbot'
        ) {
            return 'bot';
        }
        return null;
    }

    function restoreConversation() {
        var stored = loadStoredSession();
        if (!stored || !messagesEl) {
            return Promise.resolve();
        }

        sessionUid = stored;

        return post('messages', {
            session_uid: stored,
            limit: 100,
            page: 1,
        })
            .then(function (json) {
                var list = json.messages || [];
                if (!list.length) {
                    return;
                }
                list.forEach(function (item) {
                    var role = item.role || mapHistoryRole(item.send_by);
                    var text = item.text || item.message || '';
                    if (!role || !text) {
                        return;
                    }
                    var options = { fetchProducts: role === 'bot', rateable: false };
                    if (role === 'bot' && item.id) {
                        options.rateable = true;
                        options.messageId = item.id;
                    }
                    appendMessage(text, role, options);
                });
            })
            .catch(function () {
                sessionUid = null;
                clearStoredSession();
            });
    }

    function scrollMessages() {
        if (messagesEl) {
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }
    }

    function getVisitorUid() {
        var key = 'dtc_pq_visitor_uid';
        try {
            var uid = localStorage.getItem(key);
            if (!uid) {
                uid = 'v_' + Date.now() + '_' + Math.random().toString(36).slice(2, 12);
                localStorage.setItem(key, uid);
            }
            return uid;
        } catch (e) {
            return 'v_' + Date.now();
        }
    }

    function loadSocketIo() {
        if (typeof window.io === 'function') {
            return Promise.resolve(window.io);
        }
        return new Promise(function (resolve, reject) {
            var existing = document.getElementById('dtc-pq-socket-io');
            if (existing) {
                existing.addEventListener('load', function () {
                    resolve(window.io);
                });
                existing.addEventListener('error', reject);
                return;
            }
            var script = document.createElement('script');
            script.id = 'dtc-pq-socket-io';
            script.src = 'https://cdn.socket.io/4.7.5/socket.io.min.js';
            script.async = true;
            script.onload = function () {
                resolve(window.io);
            };
            script.onerror = function () {
                reject(new Error('Socket.IO indisponible'));
            };
            document.head.appendChild(script);
        });
    }

    function ensureFeedbackSocket() {
        if (!config.widgetUid) {
            return Promise.reject(new Error('Widget UID manquant'));
        }
        if (feedbackSocket && feedbackSocket.connected) {
            return Promise.resolve(feedbackSocket);
        }
        if (feedbackSocketPromise) {
            return feedbackSocketPromise;
        }

        feedbackSocketPromise = loadSocketIo()
            .then(function (io) {
                return new Promise(function (resolve, reject) {
                    var url = config.socketUrl || 'https://wss.yourgpt.ai/chatbot';
                    var socket = io(url, { transports: ['websocket', 'polling'] });
                    var settled = false;

                    function finish(err) {
                        if (settled) {
                            return;
                        }
                        settled = true;
                        if (err) {
                            reject(err);
                        } else {
                            feedbackSocket = socket;
                            resolve(socket);
                        }
                    }

                    socket.on('connect', function () {
                        if (!visitorRegistered) {
                            socket.emit('chatbot:visitor:create', {
                                widget_uid: config.widgetUid,
                                visitor_uid: getVisitorUid(),
                                user_agent: navigator.userAgent,
                            });
                            visitorRegistered = true;
                        }
                        finish(null);
                    });
                    socket.on('connect_error', function (err) {
                        finish(err || new Error('Connexion socket impossible'));
                    });
                    setTimeout(function () {
                        finish(new Error('Délai de connexion socket dépassé'));
                    }, 12000);
                });
            })
            .catch(function (err) {
                feedbackSocketPromise = null;
                throw err;
            });

        return feedbackSocketPromise;
    }

    function buildFeedbackChoices(rating) {
        return [
            {
                value: 'like',
                text: config.likeLabel || 'Utile',
                icon: '👍',
                selected: rating === 'like',
                enabled: true,
            },
            {
                value: 'dislike',
                text: config.dislikeLabel || 'Pas utile',
                icon: '👎',
                selected: rating === 'dislike',
                enabled: true,
            },
        ];
    }

    function emitMessageFeedback(messageId, rating) {
        if (!sessionUid || !messageId || !config.widgetUid) {
            return Promise.resolve();
        }

        return ensureFeedbackSocket()
            .then(function (socket) {
                socket.emit('chatbot:message:update', {
                    message_id: String(messageId),
                    session_uid: String(sessionUid),
                    visitor_uid: getVisitorUid(),
                    widget_uid: config.widgetUid,
                    choices: buildFeedbackChoices(rating),
                    content_type: 'picker',
                });
            })
            .catch(function () {
                /* UI utilisable même si le socket YourGPT échoue */
            });
    }

    function setFeedbackSelection(row, rating) {
        if (!row) {
            return;
        }
        row.querySelectorAll('[data-dtc-pq-feedback]').forEach(function (btn) {
            var isActive = btn.getAttribute('data-dtc-pq-feedback') === rating;
            btn.classList.toggle('is-active', isActive);
            btn.disabled = true;
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function bindFeedbackRow(row, messageId) {
        if (!row || !messageId) {
            return;
        }
        row.querySelectorAll('[data-dtc-pq-feedback]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var rating = btn.getAttribute('data-dtc-pq-feedback');
                if (!rating || btn.disabled) {
                    return;
                }
                setFeedbackSelection(row, rating);
                emitMessageFeedback(messageId, rating);
            });
        });
    }

    function createBotAvatar() {
        var img = document.createElement('img');
        img.className = 'dtc-pq-panel__msg-avatar';
        img.src = config.botAvatarUrl || '';
        img.alt = '';
        img.width = 36;
        img.height = 36;
        img.setAttribute('aria-hidden', 'true');
        img.loading = 'lazy';

        return img;
    }

    function buildBotMessageShell(extraWrapClass) {
        var wrap = document.createElement('div');
        wrap.className = 'dtc-pq-panel__msg-wrap dtc-pq-panel__msg-wrap--bot';
        if (extraWrapClass) {
            wrap.classList.add(extraWrapClass);
        }

        var row = document.createElement('div');
        row.className = 'dtc-pq-panel__msg-row';

        if (config.botAvatarUrl) {
            row.appendChild(createBotAvatar());
        }

        var content = document.createElement('div');
        content.className = 'dtc-pq-panel__msg-content';
        row.appendChild(content);
        wrap.appendChild(row);

        return {
            wrap: wrap,
            content: content,
        };
    }

    function formatInlineMarkdown(raw) {
        var s = String(raw)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        s = s.replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, function (_match, label, href) {
            var safeHref = String(href).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
            if (!/^(https?:|mailto:|tel:|\/|#)/i.test(safeHref)) {
                return label;
            }
            return (
                '<a href="' +
                safeHref +
                '" target="_blank" rel="noopener noreferrer">' +
                label +
                '</a>'
            );
        });
        s = s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        return s;
    }

    function formatBotMessageHtml(text) {
        var lines = String(text).replace(/\r\n/g, '\n').split('\n');
        var out = [];
        var listType = null;

        function closeList() {
            if (listType) {
                out.push('</' + listType + '>');
                listType = null;
            }
        }

        function openList(type) {
            if (listType !== type) {
                closeList();
                out.push('<' + type + '>');
                listType = type;
            }
        }

        for (var i = 0; i < lines.length; i++) {
            var line = lines[i].replace(/\s+$/, '');
            if (line === '') {
                closeList();
                out.push('<br>');
                continue;
            }
            var heading = line.match(/^(#{1,6})\s+(.+)$/);
            if (heading) {
                closeList();
                var level = Math.min(6, heading[1].length + 2);
                out.push('<h' + level + '>' + formatInlineMarkdown(heading[2]) + '</h' + level + '>');
                continue;
            }
            var bullet = line.match(/^\s*[-*]\s+(.+)$/);
            if (bullet) {
                openList('ul');
                out.push('<li>' + formatInlineMarkdown(bullet[1]) + '</li>');
                continue;
            }
            var numbered = line.match(/^\s*\d+[.)]\s*(.+)$/);
            if (numbered) {
                openList('ol');
                out.push('<li>' + formatInlineMarkdown(numbered[1]) + '</li>');
                continue;
            }
            closeList();
            out.push(formatInlineMarkdown(line) + '<br>');
        }
        closeList();

        return out.join('').replace(/(<br>)+$/, '');
    }

    function normalizePanelProductUrl(rawUrl) {
        var url = String(rawUrl || '')
            .trim()
            .replace(/[.,;:!?)]+$/g, '');
        if (!url) {
            return '';
        }
        if (url.indexOf('//') === 0) {
            url = window.location.protocol + url;
        } else if (/^https?:\/\//i.test(url)) {
            /* URL absolue (ex. cloture-discount.fr) — ne pas préfixer l’origine locale */
        } else if (url.charAt(0) === '/') {
            url = window.location.origin + url;
        } else if (/^[a-z0-9]/i.test(url)) {
            url = window.location.origin + '/' + url.replace(/^\//, '');
        } else {
            return '';
        }

        return url;
    }

    function isProductPageUrl(url) {
        try {
            var parsed = new URL(url);
            var path = parsed.pathname || '';
            if (path.indexOf('://') !== -1) {
                return false;
            }

            return /\/\d+-[^/]+(?:\.html)?(?:[/?#]|$)/i.test(path);
        } catch (e) {
            return false;
        }
    }

    function extractProductUrls(text) {
        var urls = [];
        var seen = {};
        var source = String(text);

        function addUrl(rawUrl) {
            var url = normalizePanelProductUrl(rawUrl);
            if (!url || seen[url] || !isProductPageUrl(url)) {
                return;
            }
            seen[url] = true;
            urls.push(url);
        }

        source.replace(/\[([^\]]+)\]\(([^)]+)\)/g, function (_match, _label, href) {
            addUrl(href);
            return '';
        });

        var absoluteMatches = source.match(/https?:\/\/[^\s<>\])"']+/gi) || [];
        absoluteMatches.forEach(addUrl);

        return urls.slice(0, MAX_PRODUCT_URLS);
    }

    /** URLs fiche produit : texte brut + liens déjà rendus dans le message bot. */
    function collectProductUrlsFromMessage(wrap, text) {
        var urls = extractProductUrls(text);
        var seen = {};
        urls.forEach(function (u) {
            seen[u] = true;
        });

        if (wrap) {
            var botMsg = wrap.querySelector('.dtc-pq-panel__msg--bot');
            if (botMsg) {
                botMsg.querySelectorAll('a[href]').forEach(function (anchor) {
                    var url = normalizePanelProductUrl(anchor.getAttribute('href'));
                    if (url && isProductPageUrl(url) && !seen[url]) {
                        seen[url] = true;
                        urls.push(url);
                    }
                });
            }
        }

        return urls.slice(0, MAX_PRODUCT_URLS);
    }

    /** Carrousel uniquement si la réponse du bot contient des liens vers des fiches produit. */
    function shouldShowProductCarousel(wrap, botText) {
        return collectProductUrlsFromMessage(wrap, botText).length > 0;
    }

    function buildCarouselCard(product) {
        var card = document.createElement('article');
        card.className = 'dtc-pq-panel__carousel-card';

        var link = document.createElement('a');
        link.className = 'dtc-pq-panel__carousel-media';
        link.href = product.url || '#';
        link.target = '_blank';
        link.rel = 'noopener noreferrer';

        if (product.image_url) {
            var img = document.createElement('img');
            img.src = product.image_url;
            img.alt = product.name || '';
            img.loading = 'lazy';
            link.appendChild(img);
        } else {
            link.appendChild(document.createElement('span'));
        }

        var body = document.createElement('div');
        body.className = 'dtc-pq-panel__carousel-body';

        var title = document.createElement('a');
        title.className = 'dtc-pq-panel__carousel-title';
        title.href = product.url || '#';
        title.target = '_blank';
        title.rel = 'noopener noreferrer';
        title.textContent = product.name || '';

        var price = document.createElement('p');
        price.className = 'dtc-pq-panel__carousel-price';
        price.textContent = product.price || '';

        var action = document.createElement('button');
        action.type = 'button';
        action.className = 'dtc-pq-panel__carousel-cart dtc-pq-panel__carousel-cart--link';
        action.textContent = config.viewProductLabel || 'Voir le produit';
        action.addEventListener('click', function (e) {
            e.preventDefault();
            if (product.url) {
                window.open(product.url, '_blank', 'noopener');
            }
        });

        body.appendChild(title);
        body.appendChild(price);
        body.appendChild(action);
        card.appendChild(link);
        card.appendChild(body);

        return card;
    }

    function buildProductCarousel(products) {
        var track = document.createElement('div');
        track.className = 'dtc-pq-panel__carousel';
        track.setAttribute('role', 'list');

        products.forEach(function (product) {
            track.appendChild(buildCarouselCard(product));
        });

        return track;
    }

    function getContextProductId() {
        if (panelEl) {
            var fromPanel = panelEl.getAttribute('data-product-id');
            if (fromPanel) {
                return fromPanel;
            }
        }
        var root = getRoot();
        if (root) {
            var fromRoot = root.getAttribute('data-product-id');
            if (fromRoot) {
                return fromRoot;
            }
        }
        if (panelEl) {
            var cardBtn = panelEl.querySelector('[data-dtc-pq-product-card] [data-id-product]');
            if (cardBtn) {
                return cardBtn.getAttribute('data-id-product') || '';
            }
        }

        return '';
    }

    function attachProductCarousel(wrap, text) {
        if (!wrap || !text || !shouldShowProductCarousel(wrap, text)) {
            return;
        }
        var content = wrap.querySelector('.dtc-pq-panel__msg-content');
        if (content) {
            wrap = content;
        }
        var urls = collectProductUrlsFromMessage(wrap, text);

        post('resolveProducts', {
            urls: JSON.stringify(urls),
        })
            .then(function (json) {
                if (!json.products || !json.products.length) {
                    return;
                }
                if (wrap.querySelector('.dtc-pq-panel__carousel')) {
                    return;
                }
                wrap.appendChild(buildProductCarousel(json.products));
                scrollMessages();
            })
            .catch(function () {
                /* ignore */
            });
    }

    function appendMessage(text, role, options) {
        options = options || {};
        if (!messagesEl || !text) {
            return null;
        }

        if (role === 'user') {
            var userDiv = document.createElement('div');
            userDiv.className = 'dtc-pq-panel__msg dtc-pq-panel__msg--user';
            userDiv.textContent = text;
            messagesEl.appendChild(userDiv);
            scrollMessages();
            return userDiv;
        }

        if (role === 'error') {
            var errDiv = document.createElement('div');
            errDiv.className = 'dtc-pq-panel__msg dtc-pq-panel__msg--error';
            errDiv.textContent = text;
            messagesEl.appendChild(errDiv);
            scrollMessages();
            return errDiv;
        }

        var shell = buildBotMessageShell();
        var wrap = shell.wrap;
        var content = shell.content;

        var botDiv = document.createElement('div');
        botDiv.className = 'dtc-pq-panel__msg dtc-pq-panel__msg--bot';
        botDiv.innerHTML = formatBotMessageHtml(text);
        content.appendChild(botDiv);

        if (options.rateable && options.messageId) {
            var feedbackRow = document.createElement('div');
            feedbackRow.className = 'dtc-pq-panel__feedback';
            feedbackRow.setAttribute('role', 'group');
            feedbackRow.setAttribute(
                'aria-label',
                config.feedbackGroupLabel || 'Évaluez cette réponse'
            );

            var likeBtn = document.createElement('button');
            likeBtn.type = 'button';
            likeBtn.className = 'dtc-pq-panel__feedback-btn';
            likeBtn.setAttribute('data-dtc-pq-feedback', 'like');
            likeBtn.setAttribute('aria-label', config.likeLabel || 'Utile');
            likeBtn.setAttribute('aria-pressed', 'false');
            likeBtn.textContent = '👍';

            var dislikeBtn = document.createElement('button');
            dislikeBtn.type = 'button';
            dislikeBtn.className = 'dtc-pq-panel__feedback-btn';
            dislikeBtn.setAttribute('data-dtc-pq-feedback', 'dislike');
            dislikeBtn.setAttribute('aria-label', config.dislikeLabel || 'Pas utile');
            dislikeBtn.setAttribute('aria-pressed', 'false');
            dislikeBtn.textContent = '👎';

            feedbackRow.appendChild(likeBtn);
            feedbackRow.appendChild(dislikeBtn);
            content.appendChild(feedbackRow);
            bindFeedbackRow(feedbackRow, options.messageId);
        }

        messagesEl.appendChild(wrap);
        scrollMessages();

        if (options.fetchProducts !== false) {
            attachProductCarousel(wrap, text);
        }

        return wrap;
    }

    function setTyping(on) {
        var existing = messagesEl
            ? messagesEl.querySelector('.dtc-pq-panel__msg-wrap--typing')
            : null;
        if (on && !existing && messagesEl) {
            var shell = buildBotMessageShell('dtc-pq-panel__msg-wrap--typing');
            var typingDiv = document.createElement('div');
            typingDiv.className = 'dtc-pq-panel__msg dtc-pq-panel__msg--typing';
            var dotsWrap = document.createElement('span');
            dotsWrap.className = 'dtc-pq-panel__typing-dots';
            dotsWrap.setAttribute('role', 'status');
            dotsWrap.setAttribute(
                'aria-label',
                config.typingLabel || 'Réflexion en cours'
            );
            for (var i = 0; i < 3; i++) {
                var dot = document.createElement('span');
                dot.className = 'dtc-pq-panel__typing-dot';
                dot.setAttribute('aria-hidden', 'true');
                dotsWrap.appendChild(dot);
            }
            typingDiv.appendChild(dotsWrap);
            shell.content.appendChild(typingDiv);
            messagesEl.appendChild(shell.wrap);
            scrollMessages();
        } else if (!on && existing) {
            existing.remove();
        }
    }

    function post(action, data) {
        var url = config.chatUrl;
        if (!url) {
            return Promise.reject(new Error('URL chat non configurée'));
        }
        var body = new URLSearchParams();
        body.append('action', action);
        if (config.chatToken) {
            body.append('token', config.chatToken);
        }
        Object.keys(data || {}).forEach(function (key) {
            body.append(key, data[key]);
        });

        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
        }).then(function (res) {
            return res.text().then(function (body) {
                if (!body) {
                    throw new Error('Réponse vide du serveur');
                }
                var json;
                try {
                    json = JSON.parse(body);
                } catch (parseErr) {
                    throw new Error('Réponse JSON invalide');
                }
                if (!res.ok || !json.ok) {
                    throw new Error(json.error || 'Erreur réseau');
                }
                return json;
            });
        });
    }

    function ensureSession() {
        if (sessionUid) {
            return Promise.resolve(sessionUid);
        }
        var stored = loadStoredSession();
        if (stored) {
            sessionUid = stored;
            return Promise.resolve(sessionUid);
        }
        return post('session', { visitor_uid: getVisitorUid() }).then(function (json) {
            sessionUid = json.session_uid;
            saveStoredSession(sessionUid);
            return sessionUid;
        });
    }

    function sessionMessageCountKey(uid) {
        return 'dtc_closy_session_msg_count_' + String(uid || 'global');
    }

    function getSessionMessageCount(uid) {
        if (!uid) {
            return 0;
        }
        try {
            return parseInt(localStorage.getItem(sessionMessageCountKey(uid)), 10) || 0;
        } catch (e) {
            return 0;
        }
    }

    function incrementSessionMessageCount(uid) {
        if (!uid) {
            return;
        }
        try {
            var next = getSessionMessageCount(uid) + 1;
            localStorage.setItem(sessionMessageCountKey(uid), String(next));
        } catch (e) {
            /* ignore */
        }
    }

    function sendChat(userText, mode) {
        if (busy || !userText) {
            return Promise.resolve();
        }
        lastUserMessage = userText;
        busy = true;
        if (sendBtn) {
            sendBtn.disabled = true;
        }

        appendMessage(userText, 'user');
        setTyping(true);

        var idProduct = getContextProductId();

        return ensureSession()
            .then(function (uid) {
                var isFirst = getSessionMessageCount(uid) === 0 ? '1' : '0';
                return post('send', {
                    session_uid: uid,
                    message: userText,
                    id_product: idProduct,
                    mode: mode || 'free',
                    is_first_message: isFirst,
                }).then(function (json) {
                    incrementSessionMessageCount(uid);
                    return json;
                });
            })
            .then(function (json) {
                setTyping(false);
                var botOptions = { rateable: false };
                if (json.message_id) {
                    botOptions.rateable = true;
                    botOptions.messageId = json.message_id;
                }
                appendMessage(json.reply, 'bot', botOptions);
            })
            .catch(function (err) {
                setTyping(false);
                var msg = err.message || 'Une erreur est survenue.';
                if (/session/i.test(msg)) {
                    sessionUid = null;
                    clearStoredSession();
                }
                appendMessage(msg, 'error');
            })
            .finally(function () {
                busy = false;
                if (sendBtn) {
                    sendBtn.disabled = false;
                }
            });
    }

    function showWelcomeIfEmpty() {
        var run = function () {
            if (!messagesEl || messagesEl.children.length > 0) {
                return;
            }
            if (config.welcome) {
                appendMessage(config.welcome, 'bot', { fetchProducts: false, rateable: false });
            }
        };
        if (historyRestorePromise) {
            historyRestorePromise.then(run).catch(run);
            return;
        }
        run();
    }

    function openPanel() {
        if (!panelEl) {
            return;
        }
        panelEl.classList.add('is-open');
        panelEl.setAttribute('aria-hidden', 'false');
        document.body.classList.add('dtc-pq-panel-active');
        showWelcomeIfEmpty();
        if (inputEl) {
            setTimeout(function () {
                inputEl.focus();
            }, 300);
        }
    }

    function closePanel() {
        if (!panelEl) {
            return;
        }
        panelEl.classList.remove('is-open');
        panelEl.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('dtc-pq-panel-active');
    }

  /**
   * @param {string|null} presetQuestion
   * @param {{mode?: string}} options mode: bubble|cta|free
   */
    function openWithMessage(presetQuestion, options) {
        options = options || {};
        var mode = options.mode || 'bubble';
        openPanel();

        if (presetQuestion) {
            sendChat(presetQuestion, mode);
        }
    }

    function findPageAddToCartButton() {
        return document.querySelector(
            '#add-to-cart-or-refresh button.add-to-cart[data-button-action="add-to-cart"], ' +
                '#add-to-cart-or-refresh .add-to-cart'
        );
    }

    function refreshPanelProductPrice() {
        if (!panelEl) {
            return;
        }
        var panelPrice = panelEl.querySelector('.dtc-pq-panel__product-price');
        if (!panelPrice) {
            return;
        }
        var pagePrice = document.querySelector(
            '.product-prices .current-price, .product-prices .product-price, .product-price .price'
        );
        if (pagePrice && pagePrice.textContent) {
            panelPrice.textContent = pagePrice.textContent.replace(/\s+/g, ' ').trim();
        }
    }

    function refreshPanelProductImage(event) {
        if (!panelEl || !event) {
            return;
        }
        var panelImg = panelEl.querySelector('.dtc-pq-panel__product-thumb img');
        if (!panelImg) {
            return;
        }
        var pageImg = document.querySelector(
            '.product-cover img, .img-cover, .js-qv-product-cover, #product-cover img'
        );
        if (pageImg && pageImg.getAttribute('src')) {
            panelImg.setAttribute('src', pageImg.getAttribute('src'));
        }
    }

    function resetCartButton(cartBtn, defaultLabel) {
        if (!cartBtn) {
            return;
        }
        cartBtn.disabled = false;
        cartBtn.classList.remove('is-success', 'is-error');
        if (defaultLabel) {
            cartBtn.textContent = defaultLabel;
        }
    }

    function triggerPanelAddToCart(cartBtn) {
        if (!cartBtn) {
            return;
        }
        var defaultLabel = cartBtn.textContent;
        var pageBtn = findPageAddToCartButton();

        if (!pageBtn || pageBtn.disabled) {
            cartBtn.classList.add('is-error');
            cartBtn.textContent = config.cartErrorLabel || 'Impossible d\'ajouter au panier';
            setTimeout(function () {
                resetCartButton(cartBtn, defaultLabel);
            }, 2800);
            return;
        }

        var onCartUpdated = function () {
            cartBtn.classList.add('is-success');
            cartBtn.textContent = config.cartSuccessLabel || 'Ajouté au panier';
            if (typeof prestashop !== 'undefined' && prestashop.off) {
                prestashop.off('updateCart', onCartUpdated);
            }
            setTimeout(function () {
                resetCartButton(cartBtn, defaultLabel);
            }, 2200);
        };

        if (typeof prestashop !== 'undefined' && prestashop.on) {
            prestashop.on('updateCart', onCartUpdated);
        }

        pageBtn.click();
    }

    function bindLauncher() {
        var launcher = document.getElementById('dtc-closy-launcher');
        if (!launcher) {
            return;
        }
        launcher.addEventListener('click', function (e) {
            e.preventDefault();
            openPanel();
        });
    }

    function bindPanelEvents() {
        if (!panelEl) {
            return;
        }
        panelEl.querySelectorAll('[data-dtc-pq-close]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                closePanel();
            });
        });

        if (typeof prestashop !== 'undefined' && prestashop.on) {
            prestashop.on('updatedProduct', function (event) {
                refreshPanelProductPrice();
                refreshPanelProductImage(event);
            });
        }

        if (formEl) {
            formEl.addEventListener('submit', function (e) {
                e.preventDefault();
                var text = inputEl ? inputEl.value.trim() : '';
                if (!text || busy) {
                    return;
                }
                if (inputEl) {
                    inputEl.value = '';
                }
                sendChat(text, 'free');
            });
        }

        if (inputEl) {
            inputEl.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter' || e.shiftKey || e.isComposing) {
                    return;
                }
                e.preventDefault();
                if (formEl && typeof formEl.requestSubmit === 'function') {
                    formEl.requestSubmit();
                } else if (formEl) {
                    formEl.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                }
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && panelEl.classList.contains('is-open')) {
                closePanel();
            }
        });
    }

    function init() {
        if (!config.enabled) {
            return;
        }
        panelEl = document.getElementById('dtc-pq-panel');
        if (!panelEl) {
            return;
        }
        messagesEl = qs('[data-dtc-pq-messages]', panelEl);
        inputEl = document.getElementById('dtc-pq-panel-input');
        formEl = qs('[data-dtc-pq-form]', panelEl);
        sendBtn = qs('[data-dtc-pq-send]', panelEl);
        bindPanelEvents();
        bindLauncher();
        historyRestorePromise = restoreConversation();
    }

    window.DtcClosy = {
        init: init,
        open: openPanel,
        openWithMessage: openWithMessage,
        isEnabled: function () {
            return !!config.enabled;
        },
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
