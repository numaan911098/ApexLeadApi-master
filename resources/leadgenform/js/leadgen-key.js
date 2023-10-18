window.leadgen = window.leadgen || {}
window.leadgen.lfForm = {}

window.leadgen.lfForm.styles = [
  'https://fonts.googleapis.com/css?family=Karla:400,700&amp;display=swap',
  'FORMS_URL/css/lf.min.css'
];

window.leadgen.lfForm.scripts = [
  'GA4_SCRIPT',
  'FORMS_URL/js/lf-lib.min.js/LEADGEN_FORM_KEY',
  'RECAPTCHA_SCRIPT'
];

if ('CSCERTIFY_SCRIPT') {
    (function (w,d,s,o,f,js,fjs) {
      w['ContactStateCertify']=o;w[o] = w[o] || function () { (w[o].q = w[o].q || []).push(arguments) };
      js = d.createElement(s), fjs = d.getElementsByTagName(s)[0];
      js.id = o; js.src = f; js.async = 1; fjs.parentNode.insertBefore(js, fjs);
    }(window, document, 'script', 'cscertify', 'https://js.contactstate.com/certify-latest.js'));
    cscertify('init', { landing_page_id: 'LANDING_PAGE_ID' });
}

for(var i = 0; i < window.leadgen.lfForm.styles.length; i++) {
  var style = document.createElement('link');
  style.setAttribute('href', window.leadgen.lfForm.styles[i]);
  style.setAttribute('rel', 'stylesheet');
  document.head.appendChild(style);
}

for(var i = 0; i < window.leadgen.lfForm.scripts.length; i++) {
  if (!window.leadgen.lfForm.scripts[i]) {
    continue;
  }

  var script = document.createElement('script');
  script.setAttribute('src', window.leadgen.lfForm.scripts[i]);
  script.setAttribute('type', 'text/javascript');
  document.head.appendChild(script);
}
