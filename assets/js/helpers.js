// Notification helpers — DOM-based, no external dependency

const NOTIFY_DURATION = 4000;

function ensureNotifyContainer() {
    let $c = $('#lt-notify-container');
    if (!$c.length) {
        $c = $('<div id="lt-notify-container"></div>').css({
            position: 'fixed',
            top: '16px',
            right: '16px',
            zIndex: 9999,
            display: 'flex',
            flexDirection: 'column',
            gap: '8px',
            maxWidth: '360px'
        });
        $('body').append($c);
    }
    return $c;
}

function notify(msg, type) {
    // DOM notification — always visible
    var $el = $('<div></div>').css({
        padding: '12px 16px',
        borderRadius: '6px',
        fontSize: '0.8125rem',
        fontWeight: 500,
        color: '#fff',
        boxShadow: '0 5px 20px rgba(0,0,0,.15)',
        cursor: 'pointer',
        opacity: 0,
        transform: 'translateX(40px)',
        transition: 'opacity .25s ease, transform .25s ease'
    });

    if (type === 'error') {
        $el.css({ background: '#ef4444' });
    } else if (type === 'warning') {
        $el.css({ background: '#f59e0b', color: '#1a1a1a' });
    } else {
        $el.css({ background: '#22c55e' });
    }

    $el.text(msg);
    ensureNotifyContainer().append($el);

    // Animate in
    requestAnimationFrame(function() {
        $el.css({ opacity: 1, transform: 'translateX(0)' });
    });

    // Click to dismiss
    $el.on('click', function() {
        $el.css({ opacity: 0, transform: 'translateX(40px)' });
        setTimeout(function() { $el.remove(); }, 250);
    });

    // Auto-dismiss
    setTimeout(function() {
        if ($el.parent().length) {
            $el.css({ opacity: 0, transform: 'translateX(40px)' });
            setTimeout(function() { $el.remove(); }, 250);
        }
    }, NOTIFY_DURATION);
}

window.showSuccess = function(msg) { notify(msg, 'success'); };
window.showError   = function(msg) { notify(msg, 'error'); };
window.showWarning = function(msg) { notify(msg, 'warning'); };
