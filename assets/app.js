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

    var quickButtons = document.querySelectorAll('[data-toggle-target]');
    quickButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-toggle-target');
            if (!targetId) {
                return;
            }
            var box = document.getElementById(targetId);
            if (!box) {
                return;
            }
            box.classList.toggle('hidden');
        });
    });

})();
