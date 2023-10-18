.lf-form-wrapper {
  flex-basis: 100%;
}

.is-danger {
  color: red;
}

.progress {
  margin: 0px;
}

.lf-form-wrapper .loader {
  display: table;
  width: 100%;
  min-height: 200px;
}
.lf-form-wrapper .loader .loader-content {
  display: table-cell;
  width: 100%;
  vertical-align: middle;
  text-align: center;
}
.lf-form-wrapper .loader .loader-content .ui-progress-circular {
  margin: auto;
}
.lf-form-wrapper .form-thankyou-wrapper .msg {
  padding: 30px 0px;
  font-weight: 300;
  font-size: 18px;
  text-align: center;
}
.lf-form-wrapper label {
  color: black;
  font-size: 16px;
}
.lf-form-wrapper form {
  padding: 15px;
}
.lf-form-wrapper form input {
  height: 2rem;
}
.lf-form-wrapper form .form-summary {
  max-height: 300px;
  overflow-y: auto;
}
.lf-form-wrapper form .form-summary > h6 {
  padding: 20px 0px;
}
.lf-form-wrapper form .form-summary .step-summary .question-summary {
  margin-bottom: 30px;
}
.lf-form-wrapper form .form-summary .step-summary .question-summary > h6 {
  font-weight: bold;
}
.lf-form-wrapper form .form-summary .step-summary .question-summary > div {
  background: #f5efef;
  padding: 10px;
}
.lf-form-wrapper form .form-summary .step-summary .question-summary > div i {
  cursor: pointer;
  color: #2196f3;
  font-size: 16px;
}
.lf-form-wrapper form .form-summary .step-summary .question-summary > div span {
  display: block;
}
.lf-form-wrapper form .step:after {
  content: "";
  clear: both;
  display: block;
}
.lf-form-wrapper form .step .questions .question {
  margin: 30px 0px;
}
.lf-form-wrapper form .step .questions .question:after {
  content: "";
  clear: both;
  display: block;
}
.lf-form-wrapper form .step .questions .question .inputfield > label {
  margin-bottom: 10px;
  display: inline-block;
}
.lf-form-wrapper form .step .questions .question .inputfield > label .material-icons {
  line-height: 16px;
  float: left;
  margin-right: 15px;
}
.lf-form-wrapper form .step .questions .question .inputfield > .question-description {
  padding-left: 40px;
  margin: 0 !important;
  line-height: 25px;
  font-weight: 300;
  font-size: 14px;
  margin-bottom: 10px !important;
}
.lf-form-wrapper form .step-nav {
  margin-top: 20px;
  overflow: hidden;
}
.lf-form-wrapper form .step-nav button {
  height: 40px;
}
.lf-form-wrapper form .step-nav .first-step-nav button {
  float: right;
}
.lf-form-wrapper form .step-nav .mid-step-nav button {
  float: left;
}
.lf-form-wrapper form .step-nav .mid-step-nav button:first-child {
  text-align: left;
  float: left;
}
.lf-form-wrapper form .step-nav .mid-step-nav button:first-child .ui-icon {
  -ms-transform: rotate(180deg);
  /* IE 9 */
  -webkit-transform: rotate(180deg);
  /* Chrome, Safari, Opera */
  transform: rotate(180deg);
}
.lf-form-wrapper form .step-nav .mid-step-nav button:last-child {
  float: right;
  text-align: right;
}
.lf-form-wrapper form .step-nav .last-step-nav button {
  width: 100%;
}
.lf-form-wrapper form [type="radio"]:not(:checked),
.lf-form-wrapper form [type="checkbox"]:not(:checked) {
  pointer-events: auto !important;
  opacity: 0.011 !important;
}
.lf-form-wrapper form .radio-list {
  list-style-type: none;
  text-align: center;
}
.lf-form-wrapper form .radio-list:after {
  content: '';
  clear: both;
  display: block;
}
.lf-form-wrapper form .radio-list li {
  position: relative;
  float: left;
  margin: 0px 5px 5px 0px;
}
.lf-form-wrapper form .radio-list li input[type="radio"],
.lf-form-wrapper form .radio-list li input[type="checkbox"] {
  position: absolute !important;
  z-index: 100;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  width: 100%;
  height: 100%;
  cursor: pointer;
}
.lf-form-wrapper form .radio-list li input[type="radio"]:checked + label,
.lf-form-wrapper form .radio-list li input[type="checkbox"]:checked + label {
  background: #2196f3;
  border: 2px solid #2196f3;
  color: white;
}
.lf-form-wrapper form .radio-list li label {
  padding: 10px 15px;
  display: block;
  height: auto;
  border: 2px solid #CCC;
  border-radius: 20px;
  line-height: 20px;
  font-size: 14px;
  word-break: break-all;
  cursor: pointer;
  z-index: 90;
}
.lf-form-wrapper form .radio-list li label:before, .lf-form-wrapper form .radio-list li label:after {
  display: none !important;
}
.lf-form-wrapper form .radio-list li label.checkbox-label {
  border-radius: 0px;
}
