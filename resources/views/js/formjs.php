Vue.use(KeenUI)
Vue.use(VueLocalStorage, { namespace: 'vuejs__'})
Vue.use(VeeValidate)
Vue.config.productionTip = false

Vue.component('LEADGEN_FORM_TAG', {
  template: `
    <div class="lf-form-wrapper" >
      <div class="load-error" v-if="errorMessage.length > 0">
        <p class="red-text center-align">{{errorMessage}}</p>
      </div>
      <div class="loader center-align" v-else-if="loading">
        <div class="loader-content">
          <ui-progress-circular color="primary" v-show="true"></ui-progress-circular>
        </div>
      </div>
      <div class="form-content-wrapper" v-else>
        <div class="progress">
          <ui-progress-linear color="primary" type="determinate" :progress="stepProgress" v-show="true"></ui-progress-linear>
        </div>
        <form @submit.prevent="onFormSubmit" :id="formId" data-vv-scope="leadgen-form" v-if="!formSent">
          <div class="step" v-for="(step, sindex) in form.steps" :key="step.id" v-if="currentStep === sindex">
            <div class="questions" v-for="(question, qindex) in  step.questions" :key="question.id">
              <div class="question" v-if="question.type === 'SHORT_TEXT'">
                <div class="inputfield">
                  <label :for="'question'+question.id" class="active"><i class="material-icons left">arrow_forward</i> {{question.title}}</label>
                  <p class="question-description">{{ question.description }}</p>
                  <ui-textbox v-validate="validateString(question)" :data-vv-scope="formScope" data-vv-value-path="value" :name="'question'+question.id" v-model.trim="form['steps'][sindex]['questions'][qindex]['value']" :placeholder="question.placeholder" :id="'question'+question.id" type="text" @keydown="onFormEnterKey($event)">
                  </ui-textbox>
                  <div class="question-errors" v-if="currentStep === sindex">
                    <span  v-show="checkErr('question'+question.id, 'required') && question.required" class="is-danger">This field is required</span>
                  </div>
                </div>
              </div>

              <div class="question" v-if="question.type === 'PARAGRAPH_TEXT'">
                <div class="inputfield">
                  <label :for="'question'+question.id" class="active"><i class="material-icons left">arrow_forward</i> {{question.title}}</label>
                  <p class="question-description">{{ question.description }}</p>
                  <ui-textbox type="text" :rows="3" v-validate="validateString(question)" :data-vv-scope="formScope" data-vv-value-path="value" :name="'question'+question.id" v-model.trim="form['steps'][sindex]['questions'][qindex]['value']" :id="'question'+question.id" :placeholder="question.placeholder" class="materialize-textarea" :multiLine="true"/>
                  <div class="question-errors" v-if="currentStep === sindex">
                    <span  v-show="checkErr('question'+question.id, 'required') && question.required" class="is-danger">This field is required</span>
                  </div>
                </div>
              </div>

              <div class="question" v-if="question.type === 'EMAIL_ADDRESS'">
                <div class="inputfield">
                  <label :for="'question'+question.id" class="active"><i class="material-icons left">arrow_forward</i> {{question.title}}</label>
                  <p class="question-description">{{ question.description }}</p>
                  <ui-textbox type="email" v-validate="validateString(question)" :name="'question'+question.id" v-model.trim="form['steps'][sindex]['questions'][qindex]['value']" :placeholder="question.placeholder" :id="'question'+question.id" :data-vv-scope="formScope" data-vv-value-path="value" @keydown="onFormEnterKey($event)">
                  </ui-textbox>
                  <div class="question-errors" v-if="currentStep === sindex">
                    <span  v-show="checkErr('question'+question.id, 'required') && question.required" class="is-danger">This field is required</span>
                    <span  v-show="checkErr('question'+question.id, 'email')" class="is-danger">This field must be a valid email</span>
                  </div>
                </div>
              </div>

              <div class="question" v-if="question.type === 'PHONE_NUMBER'">
                <div class="inputfield">
                  <label :for="'question'+question.id" class="active"><i class="material-icons left">arrow_forward</i> {{question.title}}</label>
                  <p class="question-description">{{ question.description }}</p>
                  <ui-textbox v-validate="validateString(question)" :data-vv-scope="formScope" data-vv-value-path="value" :name="'question'+question.id" v-model.trim="form['steps'][sindex]['questions'][qindex]['value']" :placeholder="question.placeholder" :id="'question'+question.id" type="text" @keydown="onFormEnterKey($event)">
                  </ui-textbox>
                  <div class="question-errors" v-if="currentStep === sindex">
                    <span  v-show="checkErr('question'+question.id, 'required') && question.required" class="is-danger">This field is required</span>
                  </div>
                </div>
              </div>

              <div class="question" v-if="question.type === 'SINGLE_CHOICE'">
                <div class="inputfield">
                  <label class="active"><i class="material-icons left">arrow_forward</i> {{question.title}}</label>
                  <p class="question-description">{{ question.description }}</p>
                  <ul class="radio-list">
                    <li v-for="(choice, cindex) in question.choices" :key="cindex">
                      <input v-validate="validateString(question)" :name="'question'+question.id" type="radio" :value="choice"  v-model="form['steps'][sindex]['questions'][qindex]['value']" />
                      <label :for="'choice'+cindex">{{choice}}</label>
                    </li>
                  </ul>
                  <div class="question-errors" v-if="currentStep === sindex">
                    <span  v-show="checkErr('question'+question.id, 'required') && question.required" class="is-danger">This field is required</span>
                  </div>
                </div>
              </div>

              <div class="question" v-if="question.type === 'MULTIPLE_CHOICE'">
                <div class="inputfield">
                  <label class="active"><i class="material-icons left">arrow_forward</i> {{question.title}}</label>
                  <p class="question-description">{{ question.description }}</p>
                  <ul class="radio-list">
                    <li v-for="(choice, cindex) in question.choices" :key="cindex">
                      <input v-validate="validateString(question)" type="checkbox" :value="choice"  :name="'question'+question.id" :id="'choice'+cindex" v-model="form['steps'][sindex]['questions'][qindex]['value']" />
                      <label :for="'choice'+cindex" class="checkbox-label">{{choice}}</label>
                    </li>
                  </ul>
                  <div class="question-errors" v-if="currentStep === sindex">
                    <span  v-show="checkErr('question'+question.id, 'required') && question.required" class="is-danger">This field is required</span>
                  </div>
                </div>
              </div>

            </div>
          </div>
          <div class="form-summary" v-if="currentStep === stepCount-1 && form.formSetting.steps_summary">
          <h6 class="center-align">Please review your details then submit.</h6>
          <div class="step-summary" v-for="(step, sindex) in form.steps" :key="sindex">
            <div class="question-summary" v-for="(question, qindex) in step.questions" :key="qindex">
                <h6>{{question.title}}</h6>
                <div v-if="question.value">
                  <i class="material-icons" @click="currentStep = sindex">edit</i>
                  <span v-if="question.type !== 'PARAGRAPH_TEXT' && question.type !== 'MULTIPLE_CHOICE'">{{question.value}}</span>
                  <span v-if="question.type === 'PARAGRAPH_TEXT'" v-html="nl2Br(question.value)"></span>
                  <span v-if="question.type === 'MULTIPLE_CHOICE'">{{question.value.join(', ')}}</span>
                </div>
                <div v-else>
                  ---
                </div>
            </div>
          </div>
        </div>
        <div class="step-nav">
          <div class="first-step-nav" v-if="currentStep === 0 && ( form.formSetting.steps_summary || stepCount > 1)">
            <ui-button @click="goNextStep" type="primary" name="action" icon="send" icon-position="right" color="default" raised>NEXT
            </ui-button>
          </div>
          <div class="mid-step-nav" v-if="currentStep > 0 && currentStep < stepCount-1">
            <ui-button @click="goPrevStep" type="primary" name="action" icon="send" icon-position="left" class="prev-step-btn" color="default" raised>
            PREVIOUS
            </ui-button>
            <ui-button @click="goNextStep" type="primary" name="action" icon="send" icon-position="right" class="next-step-btn" color="default" raised>NEXT
            </ui-button>
          </div>
          <div class="last-step-nav center-align" v-if="currentStep === stepCount-1">
            <ui-button size="large" color="primary" @click="submitForm" id="submit" name="action" icon="send" icon-position="right" :loading="formSending">
              SUBMIT
            </ui-button>
            <p class="red-text center-align" v-if="responseErrMsg">{{responseErrMsg}}</p>
          </div>
        </div>
        </form>
        <div class="form-thankyou-wrapper" v-else>
          <h5 class="msg">{{form.formSetting.thankyou_message}}</h5>
        </div>
      </div>
    </div>
  `,
  props : {
    formKey : {
      type: String,
      default: 'LEADGEN_FORM_KEY'
    },
    variantId : {
      type : Number,
      required : false
    }
  },

  data : function() {
    return {
      fetchingState : false,
      loading : true,
      errorMessage : '',
      form : {
        formSetting : {

        },
        steps: []
      },
      currentStep : 0,
      formScope : 'leadgen-form',
      siteKey : 'LEADGEN_FORM_RECAPTCHA_SITEKEY',
      recaptchaWidgetId : null,
      recaptchaResponse: null,
      formSent : false,
      formSending : false,
      responseErrMsg : '',
      formVisitId : -1
    }
  },

  mounted : function() {
    this.fetchFormState();
  },

  methods : {
    initializeComponent : function() {
      this.fetchingState = false;
      this.loading = true;
      this.errorMessage = '';
      this.form = {formSetting: {}, steps: []};
      this.currentStep = 0;
      this.formScope = 'leadgen-form';
      this.siteKey = 'LEADGEN_FORM_RECAPTCHA_SITEKEY';
      this.recaptchaWidgetId = null;
      this.recaptchaResponse = null;
      this.formSent = false;
      this.formSending = false;
      this.responseErrMsg = '';
      this.formVisitId = -1;
    },

    goNextStep : function () {
      let self = this;
      this.validateForm(this.formScope).then((result) => {
        if(result) {
          this.currentStep++;
          setTimeout(function() {
             self.$validator.reset(this.formScope);
          }, 10);
        }
      });
    },

    goPrevStep : function() {
      this.currentStep--;
    },

    submitForm : function() {
      this.formSending = true;
      this.responseErrMsg = '';
      this.validateForm(this.formScope).then((result) => {
        if(result) {
          if(this.form.formSetting.enable_google_recaptcha) {
            if(this.recaptchaWidgetId === null) {
              this.recaptchaWidgetId = grecaptcha.render('submit', {
                'sitekey' : this.siteKey,
                'callback' : this.recaptchaHandler,
                'expired-callback' : this.resetRecaptchaResponse
              });
            }
            if(this.recaptchaResponse === null) {
              grecaptcha.execute(this.recaptchaWidgetId);
            } else {
              this.saveLead();
            }
          } else {
            this.saveLead();
          }
        } else {
          this.formSending = false;
        }
      });
    },

    validateForm : function(scope) {
      return this.$validator.validateAll(scope);
    },

    checkErr : function(field, rule) {
      return this.errors.firstByRule(field, rule, this.formScope);
    },

    validateString : function(q) {
      let str = '';
      if(q.required)
        str = 'required';
      if(q.type === 'EMAIL_ADDRESS') {
        str += '|email';
      }
      return str;
    },

    fetchFormState : function() {
      if(this.fetchingState) {
        return;
      }
      if(!this.formKey) {
        return;
      }
      this.fetchingState = true;
      Vue.http.post(
        'http://leadgen.dev/api/forms/key/'+this.formKey,
        {leadgen_visitor_id : Vue.ls.get('visitor_id')},
        {emulateJSON : true, headers: {'Authorization': 'Bearer null'}}).then((response) => {
        this.loading = false;
        this.fetchingState = false;
        this.formVisitId = response.body.data.visitId;
        if(!Vue.ls.get('visitor_id'))
          Vue.ls.set('visitor_id', response.body.data.visitorId || '');
        for(let step of response.body.data.steps) {
          for(let question of step.questions) {
            if(question.type === 'MULTIPLE_CHOICE')
              question.value = [];
          }
        }
        this.form = response.body.data;
      }, (response) => {
        this.fetchingState = false;
        if(response.status === 404)
          this.errorMessage = "Form key is invalid.";
        else
          this.errorMessage = "Form load error."
      });
    },

    recaptchaHandler : function(token) {
      if(token.length > 0) {
        this.recaptchaResponse = token;
        this.saveLead();
      }
    },

    resetRecaptchaResponse : function() {
      this.recaptchaResponse = null;
    },

    saveLead : function() {
      if(this.recaptchaResponse || !this.form.formSetting.enable_google_recaptcha) {
        Vue.http.post('http://leadgen.dev/api/leads', {
          ...this.form,
          visitorId : Vue.ls.get('visitor_id'),
          formVisitId : this.formVisitId,
          recaptchaResponse : this.recaptchaResponse,
          previewMode: false
        }, {emulateJSON : true, headers: {'Authorization': 'Bearer null'}}).then((response) => {
          let data = response.body.data
          this.recaptchaResponse = null;
          this.formSending = false;
          if(this.form.formSetting.enable_thankyou_url) {
            if(window.parent)
              window.parent.location = this.form.formSetting.thankyou_url
            else
              window.location.href = this.form.formSetting.thankyou_url;
          } else {
            this.formSent = true;
          }
        }, (response) => {
          if(
            response.status === 400 &&
            response.body.meta.error_type === 'recaptcha_invalid_response'
          ) {
            this.responseErrMsg = "Unable to verify recaptcha please submit again.";
          } else if(
            response.status === 400 &&
            response.body.meta.error_type === 'accept_responses_disabled'
          ) {
            this.responseErrMsg = "Form submission is closed for now.";
          } else if(
            response.status === 400 &&
            response.body.meta.error_type === 'response_limit_reached'
          ) {
            this.responseErrMsg = "Too many submissions are not allowed";
          } else if(
            response.status === 400 &&
            response.body.meta.error_type === 'domain_not_allowed'
          ) {
            this.responseErrMsg = "You're not allowed to submit Form on this domain";
          } else {
            this.responseErrMsg = 'Error occurred during submission, please try again.'
          }
          this.formSending = false;
        });
      } else {
        this.formSending = false;
      }
    },

    nl2Br : function(text) {
      return text.replace(/(?:\r\n|\r|\n)/g, '<br />');
    },

    onFormSubmit : function() {

    },

    onFormEnterKey : function(e) {
      if(e.keyCode === 13) {
        e.preventDefault();
        this.goNextStep();
      }
    }
  },

  computed : {
    formId : function() {
      return 'leadgenform_'+this.formKey
    },

    stepCount : function() {
      if(this.form.formSetting.steps_summary) {
        return this.form.steps.length + 1;
      }
      return this.form.steps.length;
    },

    stepProgress : function() {
      if(this.formSent) {
        return 100;
      }
      let stepCount = this.form.formSetting.steps_summary ? this.stepCount - 1: this.stepCount;
      let progress = parseInt((this.currentStep / stepCount) * 100);
      return progress;
    }
  },

  watch: {
    formKey: function() {
      this.initializeComponent();
      this.fetchFormState();
    },

    variantId : function(to, from) {
      this.initializeComponent();
      this.fetchFormState();
    }
  }
});

var app = new Vue({
  el: '#leadgen-form-wrap-LEADGEN_FORM_ID',
})
