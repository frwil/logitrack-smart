import TomSelect from 'tom-select'

window.TomSelect = TomSelect

window.initTomSelect = function (selector, opts = {}) {
  return $(selector).filter('select').each(function () {
    if (this.tomselect) return

    new TomSelect(this, {
      plugins: ['remove_button'],
      maxOptions: null,
      ...opts
    })
  })
}

window.destroyTomSelect = function (selector) {
  $(selector).each(function () {
    if (this.tomselect) {
      this.tomselect.destroy()
    }
  })
}
