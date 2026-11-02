(() => {
    "use strict";

    const containers = document.querySelectorAll(
        "[data-nx-sitemap-tabs]"
    );

    containers.forEach((container) => {
        const tabs = container.querySelectorAll(
            "[data-nx-sitemap-tab]"
        );

        const panels = container.querySelectorAll(
            "[data-nx-sitemap-panel]"
        );

        if (tabs.length === 0 || panels.length === 0) {
            return;
        }

        const activate = (targetKey) => {
            tabs.forEach((tab) => {
                const key = tab.getAttribute(
                    "data-nx-sitemap-tab"
                );

                const isActive = key === targetKey;

                tab.classList.toggle(
                    "is-active",
                    isActive
                );

                tab.setAttribute(
                    "aria-selected",
                    isActive ? "true" : "false"
                );
            });

            panels.forEach((panel) => {
                const key = panel.getAttribute(
                    "data-nx-sitemap-panel"
                );

                panel.classList.toggle(
                    "is-active",
                    key === targetKey
                );
            });
        };

        tabs.forEach((tab) => {
            tab.setAttribute("role", "tab");

            tab.addEventListener("click", () => {
                const targetKey = tab.getAttribute(
                    "data-nx-sitemap-tab"
                );

                if (targetKey) {
                    activate(targetKey);
                }
            });
        });

        panels.forEach((panel) => {
            panel.setAttribute(
                "role",
                "tabpanel"
            );
        });
    });
})();
(() => {
  'use strict';
  const initialize = () => {
    const button = document.querySelector('[data-nx-sitemap-check]');
    if (!button) return;
    button.addEventListener('click', async () => {
      const status = document.querySelector('[data-nx-sitemap-result]'); button.disabled = true;
      status.textContent = '確認中…';
      try {
        const url = new URL(button.dataset.nxSitemapCheck, location.href);
        if (url.origin !== location.origin) throw new Error('同一サイトのURLだけ確認できます。');
        const response = await fetch(url.href, { credentials:'omit', redirect:'manual', cache:'no-store', signal:AbortSignal.timeout(8000) });
        if (response.type === 'opaqueredirect') throw new Error('別のURLへ転送されています。公開URLの設定を確認してください。');
        if (response.status === 404) throw new Error('404：XML出力の有効状態・拡張機能の有効化・公開URLを確認してください。');
        if (response.status === 503) throw new Error('503：サイトマップの生成・設定・保存先を確認してください。');
        if (!response.ok) throw new Error('HTTP ' + response.status + '：公開設定を確認してください。');
        const reader = response.body.getReader(), chunks = []; let size = 0;
        try { while (true) { const part = await reader.read(); if (part.done) break; size += part.value.byteLength; if (size > 2097152) throw new Error('2MiBを超えるため簡易確認を中止しました。XMLを開いて確認してください。'); chunks.push(part.value); } }
        finally { await reader.cancel().catch(()=>{}); }
        const bytes = new Uint8Array(size); let offset = 0; chunks.forEach(chunk=>{bytes.set(chunk,offset);offset+=chunk.length;});
        const text = new TextDecoder().decode(bytes);
        if (/<!DOCTYPE|<!ENTITY/i.test(text)) throw new Error('確認対象のXML形式を確認してください。');
        const xml = new DOMParser().parseFromString(text,'application/xml');
        if (xml.querySelector('parsererror') || !['urlset','sitemapindex'].includes(xml.documentElement.localName)) throw new Error('HTTP 200ですが、サイトマップXMLではありません。');
        status.textContent = '公開中：HTTP 200、サイトマップXMLを確認しました。';
      } catch (error) { status.textContent = error.message; }
      finally { button.disabled = false; }
    });
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',initialize); else initialize();
})();
