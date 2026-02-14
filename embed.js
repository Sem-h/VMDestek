/**
 * VMDestek - Embed Script
 * 
 * Bu dosyayı web sitenize ekleyin:
 * <script src="http://localhost/LiveSupport/embed.js"></script>
 */

(function () {
    'use strict';

    // Configuration
    const WIDGET_URL = (function () {
        const scripts = document.getElementsByTagName('script');
        for (let i = scripts.length - 1; i >= 0; i--) {
            const src = scripts[i].src;
            if (src && src.indexOf('embed.js') !== -1) {
                return src.replace('/embed.js', '');
            }
        }
        return 'http://localhost/LiveSupport';
    })();

    const WIDGET_WIDTH = 380;
    const WIDGET_HEIGHT = 560;

    // Check if already loaded
    if (window.__vmdestek_loaded) return;
    window.__vmdestek_loaded = true;

    // Styles
    const styles = document.createElement('style');
    styles.textContent = `
        #vmdestek-button {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 24px rgba(102,126,234,0.4);
            z-index: 999998;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            outline: none;
        }

        #vmdestek-button:hover {
            transform: scale(1.08);
            box-shadow: 0 6px 32px rgba(102,126,234,0.5);
        }

        #vmdestek-button svg {
            width: 28px;
            height: 28px;
            fill: white;
            transition: all 0.3s;
        }

        #vmdestek-button.open svg.icon-chat {
            display: none;
        }

        #vmdestek-button.open svg.icon-close {
            display: block;
        }

        #vmdestek-button svg.icon-close {
            display: none;
        }

        #vmdestek-button .badge {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 22px;
            height: 22px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 700;
            display: none;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        #vmdestek-button .badge.show {
            display: flex;
        }

        /* Pulse animation */
        #vmdestek-button::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            animation: ls-pulse 2s ease-out infinite;
            z-index: -1;
        }

        @keyframes ls-pulse {
            0% { transform: scale(1); opacity: 0.5; }
            100% { transform: scale(1.6); opacity: 0; }
        }

        #vmdestek-iframe-container {
            position: fixed;
            bottom: 96px;
            right: 24px;
            width: ${WIDGET_WIDTH}px;
            height: ${WIDGET_HEIGHT}px;
            max-height: calc(100vh - 120px);
            z-index: 999999;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 12px 48px rgba(0,0,0,0.15), 0 4px 16px rgba(0,0,0,0.1);
            opacity: 0;
            transform: translateY(20px) scale(0.95);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            pointer-events: none;
        }

        #vmdestek-iframe-container.open {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: all;
        }

        #vmdestek-iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 16px;
        }

        /* Mobile */
        @media (max-width: 480px) {
            #vmdestek-iframe-container {
                bottom: 0;
                right: 0;
                left: 0;
                width: 100%;
                height: 100%;
                border-radius: 0;
            }

            #vmdestek-iframe-container.open {
                border-radius: 0;
            }

            #vmdestek-iframe {
                border-radius: 0;
            }

            #vmdestek-button {
                bottom: 16px;
                right: 16px;
            }
        }

        /* Greeting tooltip */
        #vmdestek-greeting {
            position: fixed;
            bottom: 92px;
            right: 24px;
            background: white;
            color: #1a1a2e;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.12);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            max-width: 260px;
            z-index: 999997;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            pointer-events: none;
        }

        #vmdestek-greeting.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: all;
        }

        #vmdestek-greeting::after {
            content: '';
            position: absolute;
            bottom: -6px;
            right: 24px;
            width: 12px;
            height: 12px;
            background: white;
            transform: rotate(45deg);
            box-shadow: 2px 2px 4px rgba(0,0,0,0.05);
        }

        #vmdestek-greeting .close-greeting {
            position: absolute;
            top: 4px;
            right: 8px;
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 14px;
            padding: 2px 4px;
        }
    `;
    document.head.appendChild(styles);

    // Create button
    const button = document.createElement('button');
    button.id = 'vmdestek-button';
    button.innerHTML = `
        <svg class="icon-chat" viewBox="0 0 24 24">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
            <circle cx="8" cy="10" r="1.2"/>
            <circle cx="12" cy="10" r="1.2"/>
            <circle cx="16" cy="10" r="1.2"/>
        </svg>
        <svg class="icon-close" viewBox="0 0 24 24">
            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
        </svg>
        <span class="badge" id="vmdestek-badge">0</span>
    `;
    document.body.appendChild(button);

    // Create iframe container
    const iframeContainer = document.createElement('div');
    iframeContainer.id = 'vmdestek-iframe-container';
    iframeContainer.innerHTML = `<iframe id="vmdestek-iframe" src="${WIDGET_URL}/widget/" allow="autoplay"></iframe>`;
    document.body.appendChild(iframeContainer);

    // Create greeting tooltip
    const greeting = document.createElement('div');
    greeting.id = 'vmdestek-greeting';
    greeting.innerHTML = `
        <button class="close-greeting" onclick="this.parentElement.classList.remove('show')">&times;</button>
        Merhaba! Size yardımcı olabilir miyiz? 💬
    `;
    document.body.appendChild(greeting);

    // Show greeting after 3 seconds
    let greetingTimer = setTimeout(() => {
        if (!isOpen) {
            greeting.classList.add('show');
            // Auto-hide after 10 seconds
            setTimeout(() => greeting.classList.remove('show'), 10000);
        }
    }, 3000);

    // Toggle
    let isOpen = false;

    button.addEventListener('click', () => {
        isOpen = !isOpen;
        button.classList.toggle('open', isOpen);
        iframeContainer.classList.toggle('open', isOpen);

        // Hide greeting when opened
        greeting.classList.remove('show');
        clearTimeout(greetingTimer);
    });

    // Close greeting on click
    greeting.addEventListener('click', (e) => {
        if (e.target.className !== 'close-greeting') {
            greeting.classList.remove('show');
            button.click(); // Open chat
        }
    });

    // Fetch widget config and apply colors
    fetch(`${WIDGET_URL}/api/chat.php?action=status`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.config) {
                const c1 = data.config.widget_color || '#667eea';
                const c2 = data.config.widget_gradient_end || '#764ba2';

                button.style.background = `linear-gradient(135deg, ${c1} 0%, ${c2} 100%)`;
                button.style.boxShadow = `0 4px 24px ${c1}66`;

                const pulseStyle = document.createElement('style');
                pulseStyle.textContent = `
                    #livesupport-button::before {
                        background: linear-gradient(135deg, ${c1} 0%, ${c2} 100%);
                    }
                `;
                document.head.appendChild(pulseStyle);

                if (data.config.welcome_message) {
                    greeting.innerHTML = `
                        <button class="close-greeting" onclick="this.parentElement.classList.remove('show')">&times;</button>
                        ${data.config.welcome_message} 💬
                    `;
                }
            }
        })
        .catch(() => { });

    // Listen for minimize message from widget iframe
    window.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'vmdestek-minimize') {
            isOpen = false;
            button.classList.remove('open');
            iframeContainer.classList.remove('open');
        }
    });
})();
