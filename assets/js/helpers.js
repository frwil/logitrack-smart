window.showSuccess = function(msg) {
    $.notify(msg, {className: 'success'});
};

window.showError = function(msg) {
    $.notify(msg, {className: 'error'});
};

window.showWarning = function(msg) {
    $.notify(msg, {className: 'warning'});
};
