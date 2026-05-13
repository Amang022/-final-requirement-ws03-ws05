// =============================================================
// assets/js/main.js — Frontend utilities
// =============================================================

// ── Modal helpers ──────────────────────────────────────────────
function openModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.style.display = 'flex';
        el.classList.add('active');
    }
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.style.display = 'none';
        el.classList.remove('active');
    }
}

// Close modal when clicking outside the modal box
document.addEventListener('click', function (e) {
    document.querySelectorAll('.modal-overlay.active').forEach(function (overlay) {
        if (e.target === overlay) {
            overlay.style.display = 'none';
            overlay.classList.remove('active');
        }
    });
});

// Close modal on Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(function (overlay) {
            overlay.style.display = 'none';
            overlay.classList.remove('active');
        });
    }
});

// ── Item search/filter ────────────────────────────────────────
function filterItems() {
    var input  = document.getElementById('searchInput');
    var filter = input ? input.value.toLowerCase() : '';
    var table  = document.getElementById('itemsTable');
    if (!table) return;

    var rows = table.getElementsByTagName('tr');
    for (var i = 1; i < rows.length; i++) {
        var cells = rows[i].getElementsByTagName('td');
        var show  = false;
        for (var j = 0; j < cells.length; j++) {
            if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                show = true;
                break;
            }
        }
        rows[i].style.display = show ? '' : 'none';
    }
}

// ── Auto-dismiss alerts after 5 seconds ───────────────────────
document.addEventListener('DOMContentLoaded', function () {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity .5s';
            alert.style.opacity = '0';
            setTimeout(function () { alert.remove(); }, 500);
        }, 5000);
    });

    // Highlight navbar active link
    var path = window.location.pathname.split('/').pop();
    document.querySelectorAll('.navbar-nav a').forEach(function (link) {
        if (link.href.indexOf(path) !== -1 && path !== '') {
            link.style.background = 'rgba(255,255,255,.18)';
            link.style.color = '#fff';
        }
    });
});
