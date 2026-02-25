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

    var moduleToggles = document.querySelectorAll('[data-module-toggle]');
    moduleToggles.forEach(function (button) {
        button.addEventListener('click', function () {
            var moduleId = button.getAttribute('data-module-toggle');
            if (!moduleId) {
                return;
            }
            var body = document.querySelector('[data-module-body="' + moduleId + '"]');
            if (!body) {
                return;
            }
            var isCollapsed = body.classList.toggle('collapsed');
            button.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
            var arrow = button.querySelector('.section-arrow');
            if (arrow) {
                arrow.textContent = isCollapsed ? '>' : 'v';
            }
            try {
                localStorage.setItem('nav.module.' + moduleId, isCollapsed ? 'collapsed' : 'open');
            } catch (e) {
                // sem persistencia local
            }
        });

        var moduleId = button.getAttribute('data-module-toggle');
        if (!moduleId) {
            return;
        }
        var body = document.querySelector('[data-module-body="' + moduleId + '"]');
        if (!body) {
            return;
        }
        try {
            var savedState = localStorage.getItem('nav.module.' + moduleId);
            if (savedState === 'collapsed') {
                body.classList.add('collapsed');
                button.setAttribute('aria-expanded', 'false');
                var arrow = button.querySelector('.section-arrow');
                if (arrow) {
                    arrow.textContent = '>';
                }
            } else if (savedState === 'open') {
                body.classList.remove('collapsed');
                button.setAttribute('aria-expanded', 'true');
                var openArrow = button.querySelector('.section-arrow');
                if (openArrow) {
                    openArrow.textContent = 'v';
                }
            }
        } catch (e) {
            // sem persistencia local
        }
    });

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

    var kanbanBoard = document.querySelector('.kanban-board');
    if (kanbanBoard) {
        var isReadOnlyBoard = kanbanBoard.getAttribute('data-readonly') === '1';
        var dragTaskId = null;
        var columns = kanbanBoard.querySelectorAll('.kanban-column');
        var cards = kanbanBoard.querySelectorAll('.kanban-card');
        var moveForm = document.getElementById('kanbanMoveForm');
        var taskIdInput = document.getElementById('kanbanMoveTaskId');
        var statusInput = document.getElementById('kanbanMoveStatus');

        cards.forEach(function (card) {
            if (isReadOnlyBoard) {
                card.setAttribute('draggable', 'false');
                return;
            }
            card.addEventListener('dragstart', function () {
                dragTaskId = card.getAttribute('data-task-id');
                card.classList.add('dragging');
            });
            card.addEventListener('dragend', function () {
                card.classList.remove('dragging');
            });
        });

        columns.forEach(function (column) {
            if (isReadOnlyBoard) {
                return;
            }
            column.addEventListener('dragover', function (event) {
                event.preventDefault();
                column.classList.add('drop-zone');
            });
            column.addEventListener('dragleave', function () {
                column.classList.remove('drop-zone');
            });
            column.addEventListener('drop', function (event) {
                event.preventDefault();
                column.classList.remove('drop-zone');

                if (!dragTaskId || !moveForm || !taskIdInput || !statusInput) {
                    return;
                }

                var toStatus = column.getAttribute('data-status');
                if (!toStatus) {
                    return;
                }

                taskIdInput.value = dragTaskId;
                statusInput.value = toStatus;
                moveForm.submit();
            });
        });
    }

})();
