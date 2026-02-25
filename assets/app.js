(function () {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (el) {
        setTimeout(function () {
            el.style.opacity = '0';
            el.style.transition = 'opacity 0.35s';
        }, 2800);
    });

    var menuToggle = document.getElementById('menuToggle');
    var sidebar = document.getElementById('sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });

        document.addEventListener('click', function (event) {
            if (!sidebar.classList.contains('open')) {
                return;
            }
            if (sidebar.contains(event.target) || menuToggle.contains(event.target)) {
                return;
            }
            sidebar.classList.remove('open');
        });
    }

})();
