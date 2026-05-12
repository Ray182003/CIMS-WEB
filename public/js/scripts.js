
window.addEventListener('DOMContentLoaded', event => {

    // Toggle the side navigation
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        
        sidebarToggle.addEventListener('click', event => {
            event.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
            localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
        });
    }

    const sidebarNav = document.querySelector('#layoutSidenav_nav');
    if (sidebarNav) {
        const navLinks = Array.from(sidebarNav.querySelectorAll('.nav-link'));
        const currentPath = window.location.pathname.replace(/\/$/, '');

        navLinks.forEach(link => {
            const isCollapseToggle = link.dataset.bsToggle === 'collapse';
            const href = link.getAttribute('href');

            if (!href || href === '#' || isCollapseToggle) {
                return;
            }

            const normalizedHref = new URL(href, window.location.origin).pathname.replace(/\/$/, '');

            if (normalizedHref === currentPath) {
                link.classList.add('active');

                const parentCollapse = link.closest('.collapse');
                if (parentCollapse && !parentCollapse.classList.contains('show')) {
                    parentCollapse.classList.add('show');

                    const toggleTrigger = sidebarNav.querySelector(`[data-bs-target="#${parentCollapse.id}"]`);
                    if (toggleTrigger) {
                        toggleTrigger.classList.remove('collapsed');
                        toggleTrigger.setAttribute('aria-expanded', 'true');
                    }
                }
            } else {
                link.classList.remove('active');
            }

            link.addEventListener('click', () => {
                navLinks.forEach(nav => {
                    if (nav.dataset.bsToggle !== 'collapse') {
                        nav.classList.remove('active');
                    }
                });
                link.classList.add('active');
            });
        });
    }

});
