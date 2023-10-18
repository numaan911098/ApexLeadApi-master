var styles = [
  'https://fonts.googleapis.com/icon?family=Material+Icons',
  'http://leadgen.dev/css/lf.min.css'
];

var scripts = [
  'http://leadgen.dev/js/lf-lib.min.js/LEADGEN_FORM_ID',
  'https://www.google.com/recaptcha/api.js'
];

for(var i = 0; i < styles.length; i++) {
  var style = document.createElement('link');
  style.setAttribute('href', styles[i]);
  style.setAttribute('rel', 'stylesheet');
  document.head.appendChild(style);
}

for(var i = 0; i < scripts.length; i++) {
  var script = document.createElement('script');
  script.setAttribute('src', scripts[i]);
  script.setAttribute('type', 'text/javascript');
  if(scripts[i].search('recaptcha') > 0 ) {
    script.setAttribute('defer', '');
    script.setAttribute('async', '');
  }
  document.head.appendChild(script);
}
