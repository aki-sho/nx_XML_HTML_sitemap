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