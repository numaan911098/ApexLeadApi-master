!function(e,t){"object"==typeof exports&&"undefined"!=typeof module?module.exports=t():"function"==typeof define&&define.amd?define(t):e["vue-ls"]=t()}(this,function(){"use strict";var e={},t={getItem:function(t){return t in e?e[t]:null},setItem:function(t,n){return e[t]=n,!0},removeItem:function(t){return!!(t in e)&&delete e[t]},clear:function(){return e={},!0},key:function(t){var n=Object.keys(e);return void 0!==n[t]?n[t]:null}};Object.defineProperty(t,"length",{get:function(){return Object.keys(e).length}});!function(){function e(e){this.value=e}function t(t){function n(e,t){return new Promise(function(n,i){var a={key:e,arg:t,resolve:n,reject:i,next:null};u?u=u.next=a:(r=u=a,o(e,t))})}function o(n,r){try{var u=t[n](r),a=u.value;a instanceof e?Promise.resolve(a.value).then(function(e){o("next",e)},function(e){o("throw",e)}):i(u.done?"return":"normal",u.value)}catch(e){i("throw",e)}}function i(e,t){switch(e){case"return":r.resolve({value:t,done:!0});break;case"throw":r.reject(t);break;default:r.resolve({value:t,done:!1})}(r=r.next)?o(r.key,r.arg):u=null}var r,u;this._invoke=n,"function"!=typeof t.return&&(this.return=void 0)}"function"==typeof Symbol&&Symbol.asyncIterator&&(t.prototype[Symbol.asyncIterator]=function(){return this}),t.prototype.next=function(e){return this._invoke("next",e)},t.prototype.throw=function(e){return this._invoke("throw",e)},t.prototype.return=function(e){return this._invoke("return",e)}}();var n=function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")},o=function(){function e(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}return function(t,n,o){return n&&e(t.prototype,n),o&&e(t,o),t}}(),i=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var o in n)Object.prototype.hasOwnProperty.call(n,o)&&(e[o]=n[o])}return e},r={},u=function(){function e(){n(this,e)}return o(e,null,[{key:"on",value:function(e,t){void 0===r[e]&&(r[e]=[]),r[e].push(t)}},{key:"off",value:function(e,t){r[e].length?r[e].splice(r[e].indexOf(t),1):r[e]=[]}},{key:"emit",value:function(e){var t=e||window.event,n=function(e){try{return JSON.parse(e).value}catch(t){return e}},o=function(e){e(n(t.newValue),n(t.oldValue),t.url||t.uri)};if(void 0!==t&&void 0!==t.key){var i=r[t.key];void 0!==i&&i.forEach(o)}}}]),e}(),a=new(function(){function e(t){if(n(this,e),this.storage=t,this.options={namespace:"",events:["storage"]},Object.defineProperty(this,"length",{get:function(){return this.storage.length}}),"undefined"!=typeof window)for(var o in this.options.events)window.addEventListener?window.addEventListener(this.options.events[o],u.emit,!1):window.attachEvent?window.attachEvent("on"+this.options.events[o],u.emit):window["on"+this.options.events[o]]=u.emit}return o(e,[{key:"setOptions",value:function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};this.options=i(this.options,e)}},{key:"set",value:function(e,t){var n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:null,o=JSON.stringify({value:t,expire:null!==n?(new Date).getTime()+n:null});this.storage.setItem(this.options.namespace+e,o)}},{key:"get",value:function(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:null,n=this.storage.getItem(this.options.namespace+e);if(null!==n)try{var o=JSON.parse(n);if(null===o.expire)return o.value;if(o.expire>=(new Date).getTime())return o.value;this.remove(e)}catch(e){return t}return t}},{key:"key",value:function(e){return this.storage.key(e)}},{key:"remove",value:function(e){return this.storage.removeItem(this.options.namespace+e)}},{key:"clear",value:function(){if(0!==this.length){for(var e=[],t=0;t<this.length;t++){var n=this.storage.key(t);!1!==new RegExp("^"+this.options.namespace+".+","i").test(n)&&e.push(n)}for(var o in e)this.storage.removeItem(e[o])}}},{key:"on",value:function(e,t){u.on(this.options.namespace+e,t)}},{key:"off",value:function(e,t){u.off(this.options.namespace+e,t)}}]),e}())("undefined"!=typeof window&&"localStorage"in window?window.localStorage:t),s={install:function(e,t){a.setOptions(i(a.options,{namespace:""},t||{})),e.ls=a,Object.defineProperty(e.prototype,"$ls",{get:function(){return a}})}};return"undefined"!=typeof window&&(window.VueLocalStorage=s),s});
