import TomSelect from 'tom-select'

window.TomSelect = TomSelect

window.initTomSelect = function (selector, opts = {}) {
  return $(selector).each(function () {
    if (this.tomselect) return

    // When inside .form-floating, use form-select class on the wrapper
    // so Bootstrap's floating-label styles position the label correctly.
    const inFloating = $(this).closest('.form-floating').length > 0
    const baseOpts = {
      plugins: ['remove_button'],
      maxOptions: null,
    }
    if (inFloating) {
      baseOpts.className = 'form-select'
      // Ensure label floats when the select has a value
      baseOpts.onInitialize = function () {
        if (this.getValue() && this.getValue().length > 0) {
          this.wrapper.classList.add('has-value')
        }
      }
      baseOpts.onChange = function (value) {
        if (value && value.length > 0) {
          this.wrapper.classList.add('has-value')
        } else {
          this.wrapper.classList.remove('has-value')
        }
      }
    }

    new TomSelect(this, { ...baseOpts, ...opts })
  })
}

window.destroyTomSelect = function (selector) {
  $(selector).each(function () {
    if (this.tomselect) {
      this.tomselect.destroy()
    }
  })
}
