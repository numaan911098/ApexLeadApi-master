function LeadgenForm (iframe) {
  this.pm = {
    prefix: 'lf__'
  }
  this.formKey = iframe.getAttribute('data-leadgen-form-key')
  this.iframe = iframe
  this.iframeUrl = new URL(this.iframe.getAttribute('src'))
}

LeadgenForm.prototype.receiveMessage = function (event) {
  if (
    !event.data.action ||
    !event.data.action.startsWith(this.pm.prefix) ||
    event.data.formKey !== this.formKey
  ) {
    return
  }
  var data = event.data
  switch(data.action) {
    case this.pm.prefix + 'form_height':
      this.iframe.style.height = data.height + 'px'
      break
    case this.pm.prefix + 'post_form_data_url':
      this.postFormData(data.form.url, data.form.data, data.form.method)
      break
    case this.pm.prefix + 'post_form_data':
      this.postFormData(data.form.url, data.form.data, data.form.method)
      break
    case this.pm.prefix + 'scrolltop':
      if (data.top >= 0) {
        return
      }

      window.scrollTo({
        top: window.pageYOffset + this.iframe.getBoundingClientRect().top - 30,
        behavior: 'smooth'
      })
      break
    case this.pm.prefix + 'formShadow':
      this.iframe.style.boxShadow = data.value;
      break
  }
}

LeadgenForm.prototype.postMessage = function (data) {
  var iframeUrl = new URL(this.iframe.getAttribute('src'))
  this.iframe
    .contentWindow
    .postMessage(data, iframeUrl.origin)
}

LeadgenForm.prototype.postFormData = function (path, params, method) {
  method = method || 'post'

  let formAlreadyInserted = document.getElementById(this.formKey);
  if (formAlreadyInserted) {
    return;
  }

  let form = document.createElement('form')
  form.setAttribute('method', method)
  form.setAttribute('action', path)
  form.setAttribute('id', this.formKey);

  for (let param of params) {
    let hiddenField = document.createElement('input')
    hiddenField.setAttribute('type', 'hidden')
    hiddenField.setAttribute('name', param.name)
    hiddenField.setAttribute('value', param.value)
    form.appendChild(hiddenField);
  }

  document.body.appendChild(form)
  form.submit()
}

LeadgenForm.prototype.init = function () {
  var self = this

  window.addEventListener('message', function (event) {
    self.receiveMessage(event)
  }, false)

  window.addEventListener('scroll', function () {
    self.postMessage({
      action: self.pm.prefix + 'iframe_viewport_top',
      origin: window.location.origin,
      formKey: self.formKey,
      top: self.iframe.getBoundingClientRect().top
    })
  })

  this.postMessage({
    action: this.pm.prefix + 'parent_origin',
    origin: window.location.origin,
    formKey: this.formKey
  })

  this.postMessage({
    action: this.pm.prefix + 'parent_url',
    url: window.location.href,
    formKey: this.formKey
  })

  this.postMessage({
    action: this.pm.prefix + 'parent_font',
    url: window.location.href,
    formKey: this.formKey,
    family: window.getComputedStyle( document.body, null ).getPropertyValue( 'font-family' )
  })

  this.postMessage({
    action: this.pm.prefix + 'parent_title',
    url: window.document.title,
    formKey: this.formKey
  })
}

function onLeadgenFormIframeLoad (iframe) {
  (new LeadgenForm(iframe)).init()
}
