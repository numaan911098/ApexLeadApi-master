<template>
  <div class="lf-form" :style="extracss">
    <div v-if="formGeolocationForbidden">
    </div>
    <!-- FORM ERROR-->
    <div class="lf-form__error" v-else-if="errorMessage.length > 0">
      <p class="lf-red-text">{{errorMessage}}</p>
    </div>
    <!-- FORM PRELOADER -->
    <div class="lf-form__preloader" v-else-if="loading">
      <div class="lf-form__preloader__content">
        <ui-progress-circular color="primary" v-show="true"></ui-progress-circular>
      </div>
    </div>
    <!-- FORM CONTENT -->
    <div class="lf-form__content" v-else-if="themeLoaded" :style="formStyle"
    :class='{formShadow: theme.ui_elements.background.formShadow}'>
      <!-- FORM PROGRESS -->
      <div class="lf-form__progress">
         <ui-progress-linear  type="determinate" v-if="theme.ui_elements.step_progress.showProgress" :style="{backgroundColor:theme.ui_elements.step_progress.config.stroke_color}" :progress="stepProgress" v-show="true"></ui-progress-linear>
      </div>
      <!-- FORM -->
      <form @submit.prevent="onFormSubmit" :id="formId" data-vv-scope="leadgen-form" v-if="!formSent">
        <!-- FORM STEPS -->
        <div class="lf-form__step__container" v-for="(step, sindex) in form.steps" :key="step.id">
          <div class="lf-form__step" v-if="currentStep === sindex">
            <div class="lf-form__step__item" v-for="(stepItem,stepItemIndex) in stepItems(step)" :key="stepItemIndex">
              <div class="lf-form__step__item-element" v-if="stepItem.type === 'element'">
                <!-- ELEMENT ITEM -->
                <div class="lf-form__element">
                  <div class="ql-editor" v-html="stepItem.element.content"></div>
                </div>
              </div>
              <div class="lf-form__step__item-question" v-if="stepItem.type === 'question'">
                <!-- QUESTION ITEM -->
                <div class="lf-form__question" v-if="stepItem.question.type === 'SHORT_TEXT' || stepItem.question.type === 'FIRST_NAME' || stepItem.question.type === 'LAST_NAME'">
                  <div class="lf-form__question__inputfield">
                    <label :for="questionFieldName(stepItem.question)" class="active" v-if="!theme.ui_elements.question.hide_question_labels"> {{stepItem.question.title}}
                      <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span>
                    </label>
                    <p class="lf-form__question__description" v-if="stepItem.question.description  && !theme.ui_elements.question.hide_question_labels">{{ stepItem.question.description }}</p>
                    <ui-textbox v-validate="validateString(stepItem.question)" :data-vv-scope="formScope" data-vv-value-path="value" :name="questionFieldName(stepItem.question)" v-model.trim="form['steps'][sindex]['questions'][stepItem.qindex]['value']" :placeholder="stepItem.question.placeholder" :id="questionFieldName(stepItem.question)" type="text" @keydown="onFormEnterKey($event)">
                    </ui-textbox>
                    <div class="lf-form__question__errors" v-if="currentStep === sindex">
                      <span  v-show="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger" :style="warningColorStyle">This field is required</span>
                    </div>
                  </div>
                </div>

                <div class="lf-form__question" v-if="stepItem.question.type === 'PARAGRAPH_TEXT'">
                  <div class="lf-form__question__inputfield">
                    <label v-if="!theme.ui_elements.question.hide_question_labels" :for="questionFieldName(stepItem.question)" class="active"> {{stepItem.question.title}}
                      <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span>
                    </label>
                    <p class="lf-form__question__description" v-if="stepItem.question.description  && !theme.ui_elements.question.hide_question_labels">{{ stepItem.question.description }}</p>
                    <ui-textbox type="text" :rows="3" v-validate="validateString(stepItem.question)" :data-vv-scope="formScope" data-vv-value-path="value" :name="questionFieldName(stepItem.question)" v-model.trim="form['steps'][sindex]['questions'][stepItem.qindex]['value']" :id="questionFieldName(stepItem.question)" :placeholder="stepItem.question.placeholder" class="materialize-textarea" :multiLine="true"/>
                    <div class="lf-form__question__errors" v-if="currentStep === sindex">
                      <span  v-show="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger"  :style="warningColorStyle">This field is required</span>
                    </div>
                  </div>
                </div>

                <div class="lf-form__question" v-if="stepItem.question.type === 'EMAIL_ADDRESS'">
                  <div class="lf-form__question__inputfield">
                    <label v-if="!theme.ui_elements.question.hide_question_labels" :for="questionFieldName(stepItem.question)" class="active"> {{stepItem.question.title}} <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span></label>
                    <p class="lf-form__question__description" v-if="stepItem.question.description && !theme.ui_elements.question.hide_question_labels">{{ stepItem.question.description }}</p>
                    <ui-textbox type="email" v-validate="validateString(stepItem.question)" :name="questionFieldName(stepItem.question)" v-model.trim="form['steps'][sindex]['questions'][stepItem.qindex]['value']" :placeholder="stepItem.question.placeholder" :id="questionFieldName(stepItem.question)" :data-vv-scope="formScope" data-vv-value-path="value" @keydown="onFormEnterKey($event)" />
                    <div class="lf-form__question__errors" v-if="currentStep === sindex">
                      <span v-show="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger" :style="warningColorStyle">This field is required</span>
                      <span v-show="checkErr(questionFieldName(stepItem.question), 'email')" class="is-danger" :style="warningColorStyle">This field must be a valid email</span>
                    </div>
                  </div>
                </div>

                <div class="lf-form__question" v-if="stepItem.question.type === 'PHONE_NUMBER'">
                  <div class="lf-form__question__inputfield">
                    <label v-if="!theme.ui_elements.question.hide_question_labels" :for="questionFieldName(stepItem.question)" class="active"> {{stepItem.question.title}}
                      <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span>
                    </label>
                    <p class="lf-form__question__description" v-if="stepItem.question.description  && !theme.ui_elements.question.hide_question_labels">{{ stepItem.question.description }}</p>
                    <ui-textbox v-validate="validateString(stepItem.question)" :data-vv-scope="formScope" data-vv-value-path="value" :name="questionFieldName(stepItem.question)" v-model.trim="form['steps'][sindex]['questions'][stepItem.qindex]['value']" :placeholder="stepItem.question.placeholder" :id="questionFieldName(stepItem.question)" type="tel" @keydown="onFormEnterKey($event)"/>
                    <div class="lf-form__question__errors" v-if="currentStep === sindex">
                      <span  v-show="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger" :style="warningColorStyle">This field is required</span>
                    </div>
                  </div>
                </div>

                <div class="lf-form__question" v-if="stepItem.question.type === 'DATE'">
                  <div class="lf-form__question__inputfield">
                    <label :for="questionFieldName(stepItem.question)" class="active"> {{stepItem.question.title}}</label>
                    <p class="lf-form__question__description" v-if="stepItem.question.description">{{ stepItem.question.description }}</p>
                    <ui-datepicker
                    v-validate="validateString(stepItem.question)"
                    :data-vv-scope="formScope"
                    data-vv-value-path="value"
                    :name="questionFieldName(stepItem.question)"
                    v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                    :id="questionFieldName(stepItem.question)"
                    floating-label
                    :max-date="stepItem.question.enableMinMax ? (stepItem.question.maxDate ? new Date(stepItem.question.maxDate) : null) : null"
                    :min-date="stepItem.question.enableMinMax ? (stepItem.question.minDate ? new Date(stepItem.question.minDate) : null) : null"/>
                    <div class="lf-form__question__errors" v-if="currentStep === sindex">
                      <span  v-show="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger"  :style="warningColorStyle">This field is required</span>
                    </div>
                  </div>
                </div>

                <div class="lf-form__question lf-form__single-select-question" v-if="stepItem.question.type === 'SINGLE_CHOICE'">
                  <div class="lf-form__question__inputfield">
                    <label class="active"> {{stepItem.question.title}}
                      <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span>
                    </label>
                    <p class="lf-form__question__description" v-if="stepItem.question.description">{{ stepItem.question.description }}</p>
                    <!-- button skin -->
                    <ul :class="'lf-form__radio-list' + alignmentClass(stepItem.question.skin)" v-if="!stepItem.question.skin || stepItem.question.skin.id === 'button'">
                      <li v-for="(choice, cindex) in stepItem.question.choices" :key="cindex">
                        <input v-validate="validateString(stepItem.question)" :id="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex" :name="questionFieldName(stepItem.question)" type="radio" :value="choice" v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']">
                        <label :for="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex" class="checkbox-label">{{choice.label}}</label>
                      </li><br/>
                    </ul>
                    <!-- radio skin -->
                    <div :class="'lf-form__radio-options' + alignmentClass(stepItem.question.skin)" v-else-if="stepItem.question.skin.id === 'radio'">
                      <div class="lf-form__radio-options__item" v-for="(choice, cindex) in stepItem.question.choices" :key="cindex">
                        <ui-radio
                          v-validate="validateString(stepItem.question)"
                          :data-vv-scope="formScope"
                          data-vv-value-path="value"
                          :id="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex"
                          :name="questionFieldName(stepItem.question)"
                          :value="form['steps'][sindex]['questions'][stepItem.qindex]['value'].id || ''"
                          @input="updateSingleChoiceRadioSkinValue($event, form['steps'][sindex]['questions'][stepItem.qindex])"
                          :true-value="choice.id">
                          {{ choice.label }}
                        </ui-radio>
                      </div>
                    </div>
                    <!-- dropdown skin -->
                    <div class="lf-form__dropdown-options" v-else-if="stepItem.question.skin.id === 'dropdown'">
                      <ui-select
                        v-validate="validateString(stepItem.question)"
                        :data-vv-scope="formScope"
                        data-vv-value-path="value"
                        :id="'lf-form-question-' + stepItem.question.id"
                        :name="questionFieldName(stepItem.question)"
                        :options="stepItem.question.choices"
                        :keys="{value: 'id', label: 'label'}"
                        v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']">
                      </ui-select>
                    </div>
                    <div class="lf-form__question__errors" v-if="currentStep === sindex">
                      <span  v-show="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger" :style="warningColorStyle" >This field is required</span>
                    </div>
                  </div>
                </div>

                <div class="lf-form__question lf-form__multi-select-question" v-if="stepItem.question.type === 'MULTIPLE_CHOICE'">
                  <div class="lf-form__question__inputfield">
                    <label class="active"> {{stepItem.question.title}}
                      <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span>
                    </label>
                    <p class="lf-form__question__description" v-if="stepItem.question.description">{{ stepItem.question.description }}</p>
                    <!-- button skin -->
                    <ul :class="'lf-form__radio-list' + alignmentClass(stepItem.question.skin)" v-if="!stepItem.question.skin || stepItem.question.skin.id === 'button'">
                      <li v-for="(choice, cindex) in stepItem.question.choices" :key="cindex">
                        <input v-validate="validateString(stepItem.question)" type="checkbox" :value="choice" :name="questionFieldName(stepItem.question)" :id="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex" v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']" >
                        <label :for="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex" class="checkbox-label">{{choice.label}} </label>
                      </li>
                    </ul>
                    <!-- checkbox skin -->
                    <div :class="'lf-form__checkbox-options' + alignmentClass(stepItem.question.skin)" v-else-if="stepItem.question.skin.id === 'checkbox'">
                      <ui-checkbox-group
                        v-validate="validateString(stepItem.question)"
                        :data-vv-scope="formScope"
                        :options="mapMultiSelectCheckboxOptions(stepItem.question.choices)"
                        data-vv-value-path="value"
                        :id="'lf-form-question-' + stepItem.question.id"
                        :name="questionFieldName(stepItem.question)"
                        v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                        :vertical="stepItem.question.skin.alignment === 'vertical'">
                      </ui-checkbox-group>
                    </div>
                    <!-- dropdown skin -->
                    <div class="lf-form__dropdown-options" v-else-if="stepItem.question.skin.id === 'dropdown'">
                      <ui-select
                        v-validate="validateString(stepItem.question)"
                        :data-vv-scope="formScope"
                        data-vv-value-path="value"
                        :id="'lf-form-question-' + stepItem.question.id"
                        :name="questionFieldName(stepItem.question)"
                        :options="stepItem.question.choices"
                        :keys="{value: 'id', label: 'label'}"
                        v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                        :multiple="true">
                      </ui-select>
                    </div>
                    <div class="lf-form__question__errors" v-if="currentStep === sindex">
                      <span v-if="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger"  :style="warningColorStyle">This field is required</span>
                      <div v-else-if="stepItem.question.enableMinMaxChoices && stepItem.question.required">
                        <span v-show="checkErr(questionFieldName(stepItem.question)+'_between', 'between') && stepItem.question.required" class="is-danger" :style="warningColorStyle">
                            {{ stepItem.question.minChoices > stepItem.question.value.length ? 'Please select atleast ' + stepItem.question.minChoices + ' choices' : '' }}
                            {{ stepItem.question.maxChoices <stepItem.question.value.length ? 'Please select only ' + stepItem.question.maxChoices + ' choices at maximum.' : '' }}
                         </span>
                        <input v-validate="betweenValidateString(stepItem.question.minChoices, stepItem.question.maxChoices)" :name="questionFieldName(stepItem.question) + '_between'" type="hidden" :value="form['steps'][sindex]['questions'][stepItem.qindex]['value'].length || 0">
                      </div>
                    </div>
                  </div>
                </div>

                <div class="lf-form__question" v-if="stepItem.question.type === 'ADDRESS'">
                  <div class="lf-form__question__inputfield">
                    <label v-if="!theme.ui_elements.question.hide_question_labels" :for="questionFieldName(stepItem.question)" class="active" > {{stepItem.question.title}}<span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span></label>
                    <p class="lf-form__question__description" v-if="stepItem.question.description && !theme.ui_elements.question.hide_question_labels" >{{ stepItem.question.description }}</p>
                    <div class="lf-form__question__fields">
                      <div class="lf-form__question__field" v-for="field in addressFieldsFilter(stepItem.question.fields)" :key="field.id">
                        <ui-select
                          v-if="field.id === 'country'"
                          :label="field.label_value"
                          :placeholder="field.placeholder_value"
                          v-validate="field.required ? 'required' : ''"
                          :data-vv-scope="formScope"
                          data-vv-value-path="value"
                          :data-vv-as="field.label"
                          :name="addressFieldName(stepItem.question, field)"
                          :options="autocompleteCountryAddressField"
                          :hasSearch="true"
                          v-model="field.value"
                          @change="onCountryChange($event, stepItem.question)"
                          :id="addressFieldName(stepItem.question, field)" />
                        <ui-select
                          v-else-if="field.id === 'state' && stepItem.question.fields.country.enabled"
                          :label="field.label_value"
                          :placeholder="field.placeholder_value"
                          v-validate="field.required ? 'required' : ''"
                          :data-vv-scope="formScope"
                          data-vv-value-path="value"
                          :data-vv-as="field.label"
                          :name="addressFieldName(stepItem.question, field)"
                          :options="autocompleteStateAddressField(stepItem.question.fields.country.value)"
                          :hasSearch="true"
                          v-model="field.value"
                          :id="addressFieldName(stepItem.question, field)" />
                        <ui-textbox
                          v-else-if="field.id === 'state' && ! stepItem.question.fields.country.enabled"
                          :label="field.label_value"
                          :placeholder="field.placeholder_value"
                          v-validate="field.required ? 'required' : ''"
                          :data-vv-scope="formScope"
                          data-vv-value-path="value"
                          :data-vv-as="field.label"
                          :name="addressFieldName(stepItem.question, field)"
                          v-model="field.value"
                          :id="addressFieldName(stepItem.question, field)" />
                        <ui-textbox
                          v-else
                          :label="field.label_value"
                          :placeholder="field.placeholder_value"
                          v-validate="field.required ? 'required' : ''"
                          :data-vv-scope="formScope"
                          data-vv-value-path="value"
                          :data-vv-as="field.label"
                          :name="addressFieldName(stepItem.question, field)"
                          v-model.trim="field.value"
                          :id="addressFieldName(stepItem.question, field)"/>
                      </div>
                    </div>
                    <div class="lf-form__question__errors" v-if="currentStep === sindex">
                      <div class="lf-form__question__error" v-for="field in stepItem.question.fields" :key="field.id">
                        <span  v-show="checkErr(addressFieldName(stepItem.question, field), 'required') && stepItem.question.required" class="is-danger" :style="warningColorStyle">{{ checkErr(addressFieldName(stepItem.question, field), 'required') }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="lf-form__question" v-if="stepItem.question.type === 'GDPR' && stepItem.question.enabled">
                  <div class="lf-form__question__inputfield">
                    <label class="active"> {{stepItem.question.title}}
                      <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span>
                    </label>
                    <p class="lf-form__question__description" v-if="stepItem.question.description"  v-html="stepItem.question.description"
                    :style="questionDescriptionStyle">{{ stepItem.question.description }}</p>
                    <div class="default-radio-list">
                      <ui-checkbox-group :options="stepItem.question.options.choices.map((c) => c.label)"  v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']" vertical></ui-checkbox-group>
                    </div>
                    <div class="lf-form__question__errors" v-if="currentStep === sindex">
                      <span v-html="gdprErrorMessage" v-show="gdprErrorMessage.length > 0 " class="is-danger"></span>
                    </div>
                  </div>
                  <p class="lf-form__gdpr-terms" :style="{color: theme.general.colors.text_color}"  v-html="stepItem.question.legalText" >{{ stepItem.question.legalText }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- FORM SUMMARY -->
        <div :style="summarycss" class="lf-form__summary" v-if="currentStep === stepCount-1 && form.formSetting.steps_summary">
          <h5 class="lf-center-align">Please review your details then submit.</h5>
          <div class="lf-form__step-summary" v-for="(step, sindex) in summarySteps" :key="sindex">
            <div class="lf-form__question-summary__container" v-for="(question, qindex) in step.questions" :key="qindex">
              <div class="lf-form__question-summary" v-if="question.type === 'GDPR' ? question.enabled : true">
                <h6  class="titleStyle">{{question.title}}</h6>
                <div>
                  <span v-if="question.type === 'PARAGRAPH_TEXT'" v-html="nl2Br(question.value)"></span>
                  <span v-else-if="question.type === 'SINGLE_CHOICE'">{{ question.value ? question.value.label : '---' }}</span>
                  <span v-else-if="question.type === 'MULTIPLE_CHOICE'">{{ question.value ? question.value.map((v) => v.label).join(', ') : '---' }}</span>
                  <span v-else-if="question.type === 'DATE'"> {{ question.value ? toDateString(question.value) : '---' }} </span>
                  <span v-else-if="question.type === 'ADDRESS'">
                    <span v-for="field in question.fields" :key="field.id">
                      <span v-if="field.enabled"><b>{{ field.label }}:</b> {{ field.value }}</span>
                    </span>
                  </span>
                  <span v-else-if="question.type === 'GDPR'"> {{ question.value ? question.value.join(', ') : '---' }}</span>
                  <span v-else>{{ question.value || '---' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- FORM STEP NAVIGATION -->
        <div class="lf-form__step-nav">
          <div class="lf-form__first-step-nav" v-if="currentStep === 0 && ( form.formSetting.steps_summary || stepCount > 1)">

            <ui-button @click="goNextStep" type="primary" color="primary" name="action" :loading="formSending" raised :style="nextButtonStyle" :class="{'lf-shadow': theme.ui_elements.step_navigation.next_button.shadow}">
              {{ continueBtnText }} <i class="material-icons" v-if="getNextStep === -1">{{theme.ui_elements.step_navigation.submit_button.icon}} </i>
              <i class="material-icons" v-else> {{theme.ui_elements.step_navigation.next_button.icon}}</i>
            </ui-button>
          </div>

          <div class="lf-form__mid-step-nav" v-if="currentStep > 0 && currentStep < stepCount-1">
            <div class="back_button_wrapper">
              <div :style="'width:' + theme.ui_elements.step_navigation.back_button.width+ '%;' "     v-if="!theme.ui_elements.step_navigation.back_button.hide">
                  <ui-button  @click="goPrevStep" type="primary" name="action" class="lf-form__prev-step-btn" color="default" :style="backButtonStyle" :class="{'lf-shadow': theme.ui_elements.step_navigation.back_button.shadow}">
                    <i class="material-icons"> {{theme.ui_elements.step_navigation.back_button.icon}} </i>
                    {{backBtnText}}
                  </ui-button>
              </div>
            </div>

           <div class="continue_button_wrapper">
              <div :style="'width:' + theme.ui_elements.step_navigation.next_button.width + '%'" >
                <ui-button @click="goNextStep" type="primary" name="action" class="lf-form__next-step-btn" color="primary" :loading="formSending" raised :style="nextButtonStyle" :class="{'lf-shadow': theme.ui_elements.step_navigation.next_button.shadow}">
                  {{ continueBtnText }}
                  <i class="material-icons" v-if="getNextStep === -1">{{theme.ui_elements.step_navigation.submit_button.icon}} </i>
                  <i class="material-icons" v-else> {{theme.ui_elements.step_navigation.next_button.icon}}</i>
                </ui-button>
              </div>
           </div>
          </div>

          <div class="lf-form__last-step-nav center-align" v-if="currentStep === stepCount-1">
            <div  class="lf-form__mid-step-nav">
              <div class="back_button_wrapper">
                <div :style="'width:' + theme.ui_elements.step_navigation.back_button.width+ '%;' "     v-if="!theme.ui_elements.step_navigation.back_button.hide">
                  <ui-button v-if="stepHistory.length > 0 && !theme.ui_elements.step_navigation.back_button.hide" @click="goPrevStep" type="primary" name="action"  class="lf-form__prev-step-btn" color="default" :style="backButtonStyle" :class="{'lf-shadow': theme.ui_elements.step_navigation.back_button.shadow}">
                    <i class="material-icons">{{theme.ui_elements.step_navigation.back_button.icon}} </i>
                    {{backBtnText}}
                  </ui-button>
                </div>
              </div>

              <div class="submit_button_wrapper">
                <div :style="'width:' + theme.ui_elements.step_navigation.submit_button.width + '%'" >
                  <ui-button @click="goNextStep"   type="primary" name="action" class="lf-form__next-step-btn" color="primary" :loading="formSending" raised :style="submitButtonStyle" :class="{'lf-shadow': theme.ui_elements.step_navigation.submit_button.shadow}">
                    {{ continueBtnText }}
                    <i class="material-icons" v-if="getNextStep === -1">{{theme.ui_elements.step_navigation.submit_button.icon}} </i>
                    <i class="material-icons" v-else> {{theme.ui_elements.step_navigation.next_button.icon}}</i>
                  </ui-button>
                </div>
              </div>
            </div>
            <p class="lf-red-text lf-center-align" v-if="responseErrMsg" :style="theme.general.colors.warning_color">{{responseErrMsg}}</p>
          </div>

        </div>
        <div :id="'grecaptcha-' + formId"></div>
      </form>
      <!-- FORM THANKYOU MESSAGE -->
      <div class="lf-form__thankyou" v-else>
        <div class="lf-form__thankyou__msg" v-html="parsedMessage"></div>
      </div>
    </div>
  </div>
</template>

<script>
/* eslint-disable no-eval */

import Vue from 'vue'
import googleFonts from '../../google-fonts'
import countries from '../../countries'
import errorTypes from '../../error-types'
import * as deepmerge from 'deepmerge'
import themeDefaults from '../../theme-defaults'
import _ from 'lodash'
import formSubmitActions from '../../form-submit-actions'

export default {
  props : {
    formKey: String,
    variantId : {
      type : Number,
      required : false
    },
    landingPageVisitId: {
      type: Number,
      required: false
    },
    landingPageId: {
      type: Number,
      required: false
    }
  },

  data : function() {
    return {
      fetchingState: false,
      loading: true,
      errorMessage: '',
      form: {
        formSetting: {

        }
      },
      currentStep: 0,
      formScope: 'leadgen-form',
      siteKey: window.googleIrecaptchaSiteKeyPagesDomain,
      recaptchaWidgetId: null,
      recaptchaResponse: null,
      extracss: '',
      summarycss: '',
      formSent: false,
      formSending: false,
      responseErrMsg: '',
      formVisitId: -1,
      gdprErrorMessage: '',
      stepHistory: [],
      fId: '',
      vId: '',
      customStyle: '',
      theme: '',
      themeLoaded: false,
      hiddenFields: [],
      formGeolocationForbidden: false,
      getNextStep: -1,
      postMessage: {
        prefix: 'lf__',
        parentOrigin: '',
        height: {
          value: -1,
          sent: false
        }
      }
    }
  },

  mounted : function() {
    this.fetchFormState();
    if (window.self !== window.top) {
      window.addEventListener('message', this.receivePostMessage, false)
    }
  },

  methods : {
    initializeComponent: function () {
      this.fetchingState = false
      this.loading = true
      this.errorMessage = ''
      this.form = {formSetting: {}}
      this.currentStep = 0
      this.formScope = 'leadgen-form'
      this.siteKey = window.googleIrecaptchaSiteKeyPagesDomain
      this.recaptchaWidgetId = null
      this.recaptchaResponse = null
      this.extracss = ''
      this.summarycss = ''
      this.formSent = false
      this.formSending = false
      this.responseErrMsg = ''
      this.formVisitId = -1
      this.stepHistory = []
      this.fId = ''
      this.vId = ''
      this.customStyle = ''
      this.theme = ''
      this.themeLoaded = false
      this.hiddenFields = []
      this.formGeolocationForbidden = false
      this.getNextStep = -1
      this.postMessage = {
        prefix: 'lf__',
        parentOrigin: '',
        height: {
          value: -1,
          sent: false
        }
      }
    },

    receivePostMessage: function (event) {
      if (
        !event.data.action ||
        !event.data.action.startsWith(this.postMessage.prefix) ||
        event.data.formKey !== this.formKey
      ) {
        return
      }
      switch (event.data.action) {
        case this.postMessage.prefix + 'parent_origin':
          this.postMessage.parentOrigin = event.origin
          if (this.postMessage.height.sent) {
            this.updateFormHeightPostMessage()
          }
          break
      }
    },

    updateFormHeightPostMessage: function (height) {
      if (window.self !== window.top) {
        let actionData = {}
        if (height || height === 0 || this.formGeolocationForbidden) {
          actionData = {
            action: this.postMessage.prefix + 'form_height',
            height: height || 0,
            formKey: this.formKey
          }
        } else {
          let wrapper = '#leadgen-form-wrap-' + this.formKey
          wrapper = document.querySelector(wrapper)
          if (!wrapper) {
            return
          }
          let formHeight = wrapper.scrollHeight + 20
          actionData = {
            action: this.postMessage.prefix + 'form_height',
            height: formHeight,
            formKey: this.formKey
          }
        }
        window.parent.postMessage(actionData, this.postMessage.parentOrigin || '*')
        this.postMessage.height.sent = true
        this.postMessage.height.value = actionData.height
      }
    },

    postFormDataPostMessage: function (data, url, method) {
      if (this.insideIframe) {
        let actionData = {
          action: this.postMessage.prefix + 'post_form_data',
          formKey: this.formKey,
          form: {
            method: method || 'post',
            url: url,
            data: data
          }
        }
        window.parent.postMessage(actionData, this.postMessage.parentOrigin || '*')
      }
    },

    postHiddenForm: function (path, params, method) {
      method = method || 'post'

      let form = document.createElement('form')
      form.setAttribute('method', method)
      form.setAttribute('action', path)

      for (let param of params) {
        let hiddenField = document.createElement('input')
        hiddenField.setAttribute('type', 'hidden')
        hiddenField.setAttribute('name', param.name)
        hiddenField.setAttribute('value', param.value)
        form.appendChild(hiddenField)
      }

      document.body.appendChild(form)
      form.submit()
    },

    getFormData: function (form) {
      let data = []
      for (let i = 0; i < form.steps.length; i++) {
        let step = form.steps[i]
        if (step.skipped) {
          continue
        }
        for (let j = 0; j < step.questions.length; j++) {
          let question = step.questions[j]
          let name = question.field_name || 'S' + (i + 1) + '_Q' + (j + 1)
          let value = question.value
          if (question.type === 'SINGLE_CHOICE') {
            if (value) {
              value = question.value.label
            } else {
              value = ''
            }
          } else if (question.type === 'MULTIPLE_CHOICE') {
            if (value) {
              value = JSON.stringify(_.map(question.value, (choice) => choice.label))
            } else {
              value = '[]'
            }
          } else if (question.type === 'GDPR') {
            if (question.enabled) {
              if (value) {
                value = JSON.stringify(value)
              } else {
                value = '[]'
              }
              data.push({
                name: name,
                value: value
              })
            }
            continue
          } else if (question.type === 'ADDRESS') {
            value = []
            for (let fieldKey in question.fields) {
              let field = question.fields[fieldKey]
              if (field.enabled) {
                value.push({[field.id]: field.value})
              }
            }
            value = JSON.stringify(value)
          }
          data.push({
            name: name,
            value: value
          })
        }
        for (let hiddenField of this.hiddenFields) {
          data.push({
            name: hiddenField.name,
            value: hiddenField.default_value
          })
        }
      }
      return data
    },

    prepareTheme: function () {
      this.fontFamily()
      this.inputStyle()
      this.questionTitleStyle()
      this.questionDescriptionStyle()
      this.bodyStyle()
      this.gdprStyle()
      this.reviewSummaryStyle()
      this.progressStyle()
      this.buttonStyles()
    },

    fontFamily: function () {
      for (const googleFontKey in googleFonts) {
        const googleFontValue = googleFonts[googleFontKey]
        if (googleFontValue.label === this.theme.general.font.family || googleFontValue.label === this.theme.typography.question_description.font.family || googleFontValue.label === this.theme.typography.question_title.font.family || googleFontValue.label === this.theme.ui_elements.step_navigation.back_button.font.family || googleFontValue.label === this.theme.ui_elements.step_navigation.next_button.font.family || googleFontValue.label === this.theme.typography.input_box.font.family) {
          let link = document.createElement('link')
          link.href = googleFontValue.link
          link.rel = 'stylesheet'
          document.head.appendChild(link)
        }
      }
    },
    inputStyle: function () {
      // Input Box
      this.customStyle += '.ui-textbox .ui-textbox__content .ui-textbox__label .ui-textbox__input{' + (this.theme.typography.input_box.border.skin === 'all' ? 'border' : 'border-bottom') + ':' + this.theme.typography.input_box.border.width + 'px ' + ' ' + this.theme.typography.input_box.border.style + ' ' + this.theme.typography.input_box.border.color + '!important; box-shadow:' + 'none' + ';color:' + this.theme.typography.input_box.font.color + '!important; text-indent:' + this.theme.typography.input_box.font.text_intent + 'px !important;' + ';font-family:' + this.theme.typography.input_box.font.family + '!important; font-size:' + this.theme.typography.input_box.font.font_size + 'px !important;' + ';background-color:' + this.theme.typography.input_box.font.background_color + ';border-radius:' + this.theme.typography.input_box.radius + 'px !important' +
      ';height:' + this.theme.typography.input_box.font.height + 'px !important;' + 'margin-bottom:' + this.theme.typography.input_box.font.spacing + 'px !important;' + '}'
      // Input Box focus
      this.customStyle += `
      .ui-textbox .ui-textbox__content .ui-textbox__label .ui-textbox__input:hover,
      .ui-textbox .ui-textbox__content .ui-textbox__label .ui-textbox__input:focus {
        ${this.theme.typography.input_box.border.skin === 'all' ? 'border' : 'border-bottom'}: ${this.theme.typography.input_box.border.width}px ${this.theme.typography.input_box.border.style} ${this.theme.general.colors.active_color} !important;
      }`

      // Select Country / date
      this.customStyle += '.ui-select__display, .ui-datepicker__display{' + (this.theme.typography.input_box.border.skin === 'all' ? 'border' : 'border-bottom') + ':' + this.theme.typography.input_box.border.width + 'px ' + ' ' + this.theme.typography.input_box.border.style + ' ' + this.theme.typography.input_box.border.color + '!important; box-shadow:' + 'none' + ';color:' + this.theme.typography.input_box.font.color + '!important; text-indent:' + this.theme.typography.input_box.font.text_intent + 'px !important;' + ';font-family:' + this.theme.typography.input_box.font.family + '!important; font-size:' + this.theme.typography.input_box.font.font_size + 'px !important;' + ';background-color:' + this.theme.typography.input_box.font.background_color + ';border-radius:' + this.theme.typography.input_box.radius + 'px !important' +
      ';height:' + this.theme.typography.input_box.font.height + 'px !important;' + 'margin-bottom:' + this.theme.typography.input_box.font.spacing + 'px !important;' + '}'

      // date Picker header
      this.customStyle += '.ui-calendar--color-primary .ui-calendar__header , .ui-calendar--color-primary .ui-calendar-week__date.is-selected { background-color:' + this.theme.typography.input_box.font.color + '!important; }'

      this.customStyle += '.ui-calendar--color-primary .ui-calendar-week__date.is-today { color:' + this.theme.typography.input_box.font.color + '!important; }'

      // text area
      this.customStyle += '.ui-textbox__textarea{ ' + (this.theme.typography.input_box.border.skin === 'all' ? 'border' : 'border-bottom') + ':' + this.theme.typography.input_box.border.width + 'px ' + ' ' + this.theme.typography.input_box.border.style + ' ' + this.theme.typography.input_box.border.color + '!important; box-shadow:' + 'none' + ';color:' + this.theme.typography.input_box.font.color + '!important; text-indent:' + this.theme.typography.input_box.font.text_intent + 'px !important;' + ';font-family:' + this.theme.typography.input_box.font.family + '!important; font-size:' + this.theme.typography.input_box.font.font_size + 'px !important;' + ';background-color:' + this.theme.typography.input_box.font.background_color + ';border-radius:' + this.theme.typography.input_box.radius + 'px !important' + ';margin-bottom:' + this.theme.typography.input_box.font.spacing + 'px !important;' + '}'

      // Border radius select
      this.customStyle += '.lf-form__content form .lf-form__radio-list li label.checkbox-label{ border-radius:' + this.theme.ui_elements.radio_checkbox.radius + 'px !important;}'

      // Margin bottom of radio skin
      this.customStyle += '.alignment-vertical > .lf-form__radio-options__item > label { margin-bottom:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'

      // Margin right of radio alignment-horizontal
      this.customStyle += '.alignment-horizontal > .lf-form__radio-options__item > label  { margin-right:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'

      // Margin right of radio alignment-horizontal_center
      this.customStyle += '.alignment-horizontal_center > div > label { margin-right:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'

      // Margin right alignment-horizontal button multiple selection
      this.customStyle += '.alignment-horizontal > li  { margin-right:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'

      // Margin right of Buttons alignment-horizontal_center multiple selection
      this.customStyle += '.alignment-horizontal_center > li { margin-right:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'

      //  alignment-vertical  Margin right of checkbox multiple selection
      this.customStyle += '.alignment-vertical >.ui-checkbox-group.is-vertical .ui-checkbox-group__checkbox{ margin-bottom:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'
      this.customStyle += '.alignment-vertical >.ui-checkbox-group.is-vertical .ui-checkbox-group__checkbox:last-child   { margin-bottom: 0px !important;}'

      //  alignment-horizontal  Margin right of checkbox multiple selection
      this.customStyle += '.alignment-horizontal .ui-checkbox-group__checkboxes label { margin-right:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'

      //  alignment-horizontal_center Margin right of checkbox multiple selection
      this.customStyle += '.alignment-horizontal_center .ui-checkbox-group__checkboxes label { margin-right:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'

      this.customStyle += '.alignment-vertical>li  { margin-bottom:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'
      this.customStyle += '.alignment-vertical>li:last-child   { margin-bottom: 0px !important;}'

      // Select Checked
      this.customStyle += 'input[type="radio"]:checked + label, input[type="checkbox"]:checked + label{' + 'background-color:' + this.theme.ui_elements.radio_checkbox.checked_color + ';border:' + '2px solid ' + this.theme.ui_elements.radio_checkbox.checked_color + ' !important;color:' + 'white !important' + '}'

      // drop down multiple selected
      this.customStyle += '.ui-select-option.is-selected .ui-select-option__checkbox { color:' + this.theme.ui_elements.radio_checkbox.checked_color + '!important;' +'}'

      // Select hover
      this.customStyle += 'input[type="radio"]:hover + label, input[type="checkbox"]:hover + label{' + 'background-color:' + this.theme.ui_elements.radio_checkbox.hover_color + ';border:' + '2px solid ' + this.theme.ui_elements.radio_checkbox.hover_color + ' !important;color:' + 'white !important' + '}'

      // Skin Checked Color
      this.customStyle += '.ui-radio--color-primary.is-checked:not(.is-disabled) .ui-radio__outer-circle {' + 'border-color:' + this.theme.ui_elements.radio_checkbox.checked_color + '!important}'

      this.customStyle += '.ui-radio--color-primary.is-checked:not(.is-disabled) .ui-radio__inner-circle {' + '    background-color:' + this.theme.ui_elements.radio_checkbox.checked_color + '!important}'

      this.customStyle += '.ui-checkbox--color-primary.is-checked .ui-checkbox__checkmark::before {' + 'background-color:' + this.theme.ui_elements.radio_checkbox.checked_color + '!important;' + 'border-color:' + this.theme.ui_elements.radio_checkbox.checked_color + '!important}'
       // DropDrown Icon
      this.customStyle += '.ui-select__dropdown-button, .ui-datepicker__dropdown-button { margin-right: 0.75rem !important; }'

      // Country Search Box Color
      this.customStyle += '.ui-select__search .ui-icon svg, .ui-select__dropdown .ui-select__options .ui-select-option.is-selected { color:' + this.theme.typography.input_box.font.color + '!important;' + '}'
    },
    buttonStyles: function () {
      let temp = 'center;'
      if (this.theme.ui_elements.step_navigation.back_button.alignment === 'left') {
        temp = 'flex-start;'
      } else if (this.theme.ui_elements.step_navigation.back_button.alignment === 'right') {
        temp = 'flex-end;'
      }
      this.customStyle += '.lf-form__mid-step-nav > .back_button_wrapper { justify-content:' + temp + ';width:' + this.theme.ui_elements.step_navigation.back_button.width + '%; }'

      temp = 'center;'
      if (this.theme.ui_elements.step_navigation.next_button.alignment === 'left') {
        temp = 'flex-start;'
      } else if (this.theme.ui_elements.step_navigation.next_button.alignment === 'right') {
        temp = 'flex-end;'
      }
      this.customStyle += '.lf-form__mid-step-nav > .continue_button_wrapper { justify-content:' + temp + ';width:' + this.theme.ui_elements.step_navigation.next_button.width + '%; }'

      temp = 'center;'
      if (this.theme.ui_elements.step_navigation.submit_button.alignment === 'left') {
        temp = 'flex-start;'
      } else if (this.theme.ui_elements.step_navigation.submit_button.alignment === 'right') {
        temp = 'flex-end;'
      }
      this.customStyle += '.lf-form__mid-step-nav > .submit_button_wrapper { justify-content:' + temp + ';width:' + this.theme.ui_elements.step_navigation.submit_button.width + '%; }'

      if (this.theme.ui_elements.step_navigation.back_button.hide) {
        this.customStyle += '.lf-form__mid-step-nav > .back_button_wrapper { width: 0%  }'
        this.customStyle += '.lf-form__mid-step-nav > .continue_button_wrapper { width: 100%  }'
        this.customStyle += '.lf-form__mid-step-nav > .submit_button_wrapper { width: 100%  }'
      } else {
        this.customStyle += '.lf-form__content form .lf-form__step-nav .lf-form__mid-step-nav .back_button_wrapper, .lf-form__content form .lf-form__step-nav .lf-form__mid-step-nav .continue_button_wrapper, .lf-form__content form .lf-form__step-nav .lf-form__mid-step-nav .submit_button_wrapper { width:48% } '
      }
    },
     questionTitleStyle: function () {
      this.customStyle += '.lf-form form .lf-form__step .lf-form__step__item .lf-form__question .lf-form__question__inputfield > label{' + 'color:' + this.theme.typography.question_title.font.color + ';font-family:' + this.theme.typography.question_title.font.family + '!important;font-size:' + this.theme.typography.question_title.font.size + 'px' + ';font-Weight:' + this.theme.typography.question_title.font.weight + ';line-height:' + this.theme.typography.question_title.font.line_height + 'px' + '}'
    },

    questionDescriptionStyle: function () {
      this.customStyle += '.lf-form form .lf-form__step .lf-form__step__item .lf-form__question .lf-form__question__inputfield > .lf-form__question__description{' + 'color:' + this.theme.typography.question_description.font.color + '; font-family:' + this.theme.typography.question_description.font.family + '!important; font-size:' + this.theme.typography.question_description.font.size + 'px' + ';font-Weight:' + this.theme.typography.question_description.font.weight + ';line-height:' + this.theme.typography.question_description.font.line_height + 'px' + '}'
    },

    bodyStyle: function () {
      this.customStyle += `
        body {
          font-family: ${this.theme.general.font.family};
          background-color: ${this.theme.ui_elements.background.color};
          color: ${this.theme.general.colors.text_color};
        }
      `
    },

    gdprStyle: function () {
      this.customStyle += '.lf-form label{' + 'font-family:' + this.theme.general.font.family + ';color:' + this.theme.general.colors.text_color + '}'
      this.customStyle += '.lf-form__content form .lf-form__gdpr-terms p { text-align: justify }'
    },

    reviewSummaryStyle: function () {
      this.customStyle += '.titleStyle{' + 'color:' + this.theme.typography.question_title.font.color + ';font-family:' + this.theme.typography.question_title.font.family + '}'
      this.customStyle += '.lf-form form .lf-form__summary .lf-form__step-summary .lf-form__question-summary > div{' + 'color:' + this.theme.general.colors.active_color + ';font-family:' + this.theme.general.font.family + ';border-bottom:' + '1.5px solid' + this.theme.general.colors.active_color + ';background-color:' + '#fff' + '}'
    },
    progressStyle: function () {
      this.customStyle += '.ui-progress-linear--color-primary .ui-progress-linear__progress-bar{' + 'background-color:' + this.theme.ui_elements.step_progress.config.fill_color + ' !important}'
    },

    addStyle: function () {
      var x = document.createElement('STYLE')
      var t = document.createTextNode(this.customStyle)
      x.appendChild(t)
      document.head.appendChild(x)
    },

    alignmentClass: function (skin) {
      if (!skin || !skin.alignment) {
        return ''
      }
      return ' alignment-' + skin.alignment
    },

    goNextStep: function () {
      if (!this.verifyGDPR()) {
        return
      }
      let self = this
      this.validateForm(this.formScope).then((result) => {
        if (result) {
          this.getNextStep = this.computeNextStep(this.form)
          if (!(this.currentStep === 0 && this.stepCount === 1)) {
            this.stepHistory.push(this.currentStep)
          }
          if (this.getNextStep === -1) {
            this.submitForm()
          } else {
            this.currentStep = this.getNextStep
          }
          this.getNextStep = this.computeNextStep(this.form)
          setTimeout(function () {
            self.$validator.reset(this.formScope)
          }, 10)
        }
        setTimeout(this.updateFormHeightPostMessage, 10)
      })
    },

    goPrevStep: function () {
      if (this.stepHistory.length > 0) {
        this.currentStep = this.stepHistory.pop()
        this.getNextStep = this.computeNextStep(this.form)
        setTimeout(this.updateFormHeightPostMessage, 10)
      }
    },
    submitForm: function () {
      this.formSending = true
      this.responseErrMsg = ''
      this.gdprErrorMessage = ''

      if (!this.verifyGDPR()) {
        this.formSending = false
        return
      }

      this.validateForm(this.formScope).then((result) => {
        if (result) {
          if (this.form.formSetting.enable_google_recaptcha) {
            if (this.recaptchaWidgetId === null) {
              this.recaptchaWidgetId = window.grecaptcha.render('grecaptcha-' + this.formId, {
                'sitekey': this.siteKey,
                'callback': this.recaptchaHandler,
                'expired-callback': this.resetRecaptchaResponse,
                'size': 'invisible',
                'error-callback': this.resetRecaptchaResponse
              })
              window.grecaptcha.execute(this.recaptchaWidgetId)
            } else {
              window.grecaptcha.execute(this.recaptchaWidgetId)
            }
          } else {
            this.saveLead()
          }
        } else {
          this.formSending = false
        }
      })
    },

    isGDPRValid: function () {
      let lastStep = this.form.steps[this.form.steps.length - 1]
      let lastQuestion = lastStep.questions[lastStep.questions.length - 1]
      if (lastQuestion.type !== 'GDPR') {
        return ''
      }
      for (let choice of lastQuestion.options.choices) {
        if (choice.required && lastQuestion.value.indexOf(choice.label) === -1 && lastQuestion.enabled) {
          return '<strong>' + choice.label + '</strong> must be selected '
        }
      }
      return ''
    },

    verifyGDPR: function () {
      let verifyGDPR = false
      if (this.form.formSetting.steps_summary) {
        if (this.stepCount - 2 === this.currentStep) {
          verifyGDPR = true
        }
      } else {
        if (this.stepCount - 1 === this.currentStep) {
          verifyGDPR = true
        }
      }
      if (verifyGDPR) {
        if ((this.gdprErrorMessage = this.isGDPRValid()).length > 0) {
          return false
        }
      }
      return true
    },

    validateForm: function (scope) {
      return this.$validator.validateAll(scope)
    },

    checkErr: function (field, rule) {
      return this.errors.firstByRule(field, rule, this.formScope)
    },

    validateString: function (q) {
      let str = ''
      if (q.required) {
        str = 'required'
      }
      if (q.type === 'EMAIL_ADDRESS') {
        str += '|email'
      }
      return str
    },

    betweenValidateString: function (min, max) {
      return 'between: ' + min + ',' + max
    },

    fetchFormState: function () {
      if (this.fetchingState) {
        return
      }
      if (!this.formKey) {
        return
      }
      this.fetchingState = true
      Vue.http.post('forms/key/'+this.formKey,
        {'leadgen_visitor_id': Vue.ls.get('visitor_id')},
        {emulateJSON : true, headers: {'Authorization': 'Bearer null'}})
      .then((response) => {
        this.loading = false
        this.fetchingState = false
        this.formVisitId = response.body.data.visitId
        Vue.ls.set('visitor_id', response.body.data.visitorId)
        for (let step of response.body.data.steps) {
          for (let question of step.questions) {
            if (
              question.type === 'MULTIPLE_CHOICE' ||
              question.type === 'GDPR'
            ) {
              question.value = []
            } else if (question.type === 'ADDRESS') {
              for (let field in question.fields) {
                question.fields[field].value = ''
              }
            } else if (question.type === 'DATE') {
              question.value = null
            } else {
              question.value = ''
            }
          }
        }
        this.form = response.body.data
        this.fId = response.body.data.form_id
        this.vId = response.body.data.id
        this.fetchTheme()
        this.fetchHiddenFields()
      }, (response) => {
        this.fetchingState = false
        if (
          response.status === 400 &&
          response.body.meta.error_type === errorTypes.FORM_GEOLOCATION_FORBIDDEN
        ) {
          this.formGeolocationForbidden = true
          this.updateFormHeightPostMessage(0)
        } else if (response.status === 404) {
          this.errorMessage = 'Form key is invalid.'
        } else {
          this.errorMessage = 'Form load error.'
        }
      })
    },
    fetchTheme: function () {
      this.themeLoaded = false
      Vue.http.get('forms/' + this.fId + '/variants/' + this.vId + '/theme', {emulateJSON : true, headers: {'Authorization': 'Bearer null'}})
        .then((response) => {
          // this.theme = this.setThemeDefaults(response.body.data)
          this.theme = deepmerge(themeDefaults, response.body.data)
          this.themeLoaded = true
          this.prepareTheme()
          this.styleForm()
          this.addStyle()
          this.getNextStep = this.computeNextStep(this.form)
        })
    },

    fetchHiddenFields: function () {
      Vue.http.get('forms/' + this.form.form_id + '/variants/' + this.form.id + '/hiddenFields', {headers: {'Authorization': 'Bearer null'}})
        .then((response) => {
          this.hiddenFields = response.body.data || []
          let url = new URL(window.location.href)
          for (let hiddenField of this.hiddenFields) {
            if (
              url.searchParams.get(hiddenField.name) &&
              hiddenField.capture_from_url_parameter
            ) {
              hiddenField.default_value = url.searchParams.get(hiddenField.name)
            }
          }
        })
    },

    recaptchaHandler: function (token) {
      if (token.length > 0) {
        this.recaptchaResponse = token
        this.saveLead()
      }
    },

    resetRecaptchaResponse: function () {
      this.recaptchaResponse = null
      if (this.recaptchaWidgetId != null) {
        window.grecaptcha.reset(this.recaptchaWidgetId)
      }
      this.recaptchaWidgetId = null
    },

    markSkipped: function (steps) {
      let sIndex = 0
      for (let step of steps) {
        step.skipped = this.stepHistory.indexOf(sIndex) === -1
        sIndex++
      }
      if (!this.form.formSetting.steps_summary) {
        steps[sIndex - 1].skipped = false
      }
    },

    saveLead: function () {
      let form = _.cloneDeep(this.form)

      if (this.recaptchaResponse || !form.formSetting.enable_google_recaptcha) {
        // update DATE type value
        for (let step of form.steps) {
          for (let question of step.questions) {
            if (
              question.type === 'DATE' &&
              typeof question.value === 'object'
            ) {
              question.value = question.value.toISOString()
            }
          }
        }
        this.markSkipped(form.steps)
        form.hiddenFields = this.hiddenFields
        Vue.http.post('leads', {
          ...form,
          visitorId: Vue.ls.get('visitor_id'),
          formVisitId: this.formVisitId,
          recaptchaResponse: this.recaptchaResponse,
          previewMode: false,
          landingPageVisitId: this.landingPageVisitId,
          landingPageId: this.landingPageId
        }, {emulateJSON : true, headers: {'Authorization': 'Bearer null'}})
        .then((response) => {
          this.resetRecaptchaResponse()
          this.formSending = false
          this.afterSubmitAction(form)
          setTimeout(this.updateFormHeightPostMessage, 10)
        }, (response) => {
          this.resetRecaptchaResponse()
          if (
            response.status === 400 &&
            response.body.meta.error_type === 'recaptcha_invalid_response'
          ) {
            this.responseErrMsg = 'Unable to verify recaptcha please submit again.'
          } else if (
            response.status === 400 &&
            response.body.meta.error_type === 'accept_responses_disabled'
          ) {
            this.responseErrMsg = 'Form submission is closed for now.'
          } else if (
            response.status === 400 &&
            response.body.meta.error_type === 'response_limit_reached'
          ) {
            this.responseErrMsg = 'Too many submissions are not allowed'
          } else if (
            response.status === 400 &&
            response.body.meta.error_type === 'domain_not_allowed'
          ) {
            this.responseErrMsg = 'You\'re not allowed to submit Form on this domain'
          } else {
            this.responseErrMsg = 'Error occurred during submission, please try again.'
          }
          this.formSending = false
          setTimeout(this.updateFormHeightPostMessage, 10)
        })
      } else {
        this.formSending = false
      }
    },

    afterSubmitAction: function (form) {
      if (form.formSetting.submit_action === formSubmitActions.URL.value) {
        if (this.insideIframe) {
          if (form.formSetting.post_data_to_url) {
            this.postFormDataPostMessage(this.getFormData(form), form.formSetting.thankyou_url)
          } else {
            window.top.location.href = form.formSetting.thankyou_url
          }
        } else {
          if (form.formSetting.post_data_to_url) {
            this.postHiddenForm(form.formSetting.thankyou_url, this.getFormData(form), 'post')
          } else {
            window.location.href = form.formSetting.thankyou_url
          }
        }
      } else if (form.formSetting.submit_action === formSubmitActions.MESSAGE.value) {
        this.formSent = true
        if (!this.insideIframe) {
          if (window.self === window.top) {
            setTimeout(() => {
              if (document.getElementsByClassName('lf-form__thankyou').length > 0) {
                document.getElementsByClassName('lf-form__thankyou')[0]
                  .scrollIntoView({behavior: 'smooth', block: 'start', inline: 'nearest'})
              }
            }, 100)
          }
        }
      }
    },

    nl2Br: function (text) {
      if (!text) {
        return ''
      }
      return text.replace(/(?:\r\n|\r|\n)/g, '<br />')
    },

    styleForm: function () {
      if (
        this.state === 'preview' ||
        this.state === 'shared'
      ) {
        this.extracss = 'margin: 100px auto; max-width: 600px; width: 90%;'

        this.extracss += 'color:' + this.theme.general.text_color + ';fontFamily:' + this.theme.general.font.family + '!important;backgroundColor:' + '#ffffff' + '!important;'

        this.summarycss = 'max-height: initial; overflow: auto'
        this.summarycss += 'color:' + this.theme.general.colors.text_color + ';fontFamily:' + this.theme.general.font.family
      } else {
        if (window.self === window.top) {
          // this.extracss = 'box-shadow: 0 0 10px grey;'
        }
      }
    },

    onFormSubmit: function () {
    },

    onFormEnterKey: function (e) {
      if (e.keyCode === 13) {
        e.preventDefault()
        // when user tries to submit form through enter key on last step of form if summary is disabled
        if (!this.form.formSetting.steps_summary && this.stepCount - 1 === this.currentStep) {
          this.submitForm()
          return
        }
        this.goNextStep()
      }
    },

    getChoiceValue (question, choice) {
      let cIndex = 0
      for (let c of question.choices) {
        if (c.id === choice.id) {
          return question.choicesValues[cIndex]
        }
        cIndex++
      }
    },

    parsedChoiceFormula: function () {
      if (!this.form.choiceFormula || this.form.choiceFormula.trim() === '') {
        return ''
      }
      let sIndex = 1
      let parsedFormula = this.form.choiceFormula
      for (let step of this.form.steps) {
        let qIndex = 1
        for (let question of step.questions) {
          if (question.type === 'SINGLE_CHOICE') {
            if (!question.value) {
              question.value = 0
            } else {
              question.value = parseFloat(this.getChoiceValue(question, question.value))
            }
            let field = 'S' + sIndex + '_Q' + qIndex
            parsedFormula = parsedFormula.replace(new RegExp(field, 'g'), question.value)
          }

          if (question.type === 'MULTIPLE_CHOICE') {
            if (!question.value || question.value.length === 0) {
              question.value = 0
            } else {
              let sum = 0
              for (let v of question.value) {
                sum += parseFloat(this.getChoiceValue(question, v))
              }
              question.value = sum
            }
            let field = 'S' + sIndex + '_Q' + qIndex
            parsedFormula = parsedFormula.replace(new RegExp(field, 'g'), question.value)
          }

          qIndex++
        }
        sIndex++
      }
      return parsedFormula
    },

    stepItems: function (step) {
      let items = []

      let qIndex = 0
      for (let question of step.questions) {
        items.push({
          type: 'question',
          id: question['id'],
          number: question['number'],
          qindex: qIndex,
          question: question
        })
        qIndex++
      }

      let eIndex = 0
      for (let element of step.elements) {
        items.push({
          type: 'element',
          id: element['id'],
          number: element['number'],
          eindex: eIndex,
          element: element
        })
        eIndex++
      }

      items = items.sort(function (a, b) {
        return a.number - b.number
      })
      return items
    },

    toDateString: function (date) {
      if (typeof date === 'string') {
        return (new Date(Date.parse(date))).toDateString()
      } else {
        return date.toDateString()
      }
    },

    setThemeDefaults: function (theme) {
      if (!theme.ui_elements.step_navigation.submit_button) {
        theme.ui_elements.step_navigation.submit_button = {
          'text': 'Submit',
          'backgroundColor': '#2196f3',
          'font': {
            'family': 'Lato',
            'weight': '500',
            'size': '14',
            'line_height': '20',
            'color': '#ffffff'
          },
          'borderRadius': 0,
          'shadow': true,
          'icon': ''
        }
      }
      return theme
    },

    questionFieldName: function (question) {
      if (!question.field_name || !question.field_name.trim()) {
        return 'question' + question.id
      }
      return question.field_name
    },

    addressFieldName: function (question, field) {
      if (field.field_name) {
        return field.field_name
      }
      return 'question' + question.id + '_' + field.id
    },

    addressFieldsFilter: function (fields) {
      let f = []
      for (let key in fields) {
        if (fields[key].enabled) {
          f.push(fields[key])
        }
      }
      return _.orderBy(f, ['order'], ['asc'])
    },

    autocompleteStateAddressField: function (country) {
      if (!country) {
        return []
      }
      for (let c of countries) {
        if (c.name === country) {
          return c.states
        }
      }
      return []
    },

    onCountryChange: function (country, question) {
      for (let key in question.fields) {
        if (question.fields[key].id === 'state') {
          question.fields[key].value = ''
        }
      }
    },

    computeNextStep: function (form) {
      if (this.currentStep === -1 || this.currentStep >= form.steps.length) {
        return -1
      }

      let nextStep = this.currentStep + 1

      let formSummaryFilterVal = this.formSummaryFilter(form, nextStep)
      nextStep = formSummaryFilterVal === false ? nextStep : formSummaryFilterVal

      if (form.steps.length === 1) {
        return nextStep
      }

      let stepJumpFilterVal = this.stepJumpFilter(form)
      nextStep = stepJumpFilterVal === false ? nextStep : stepJumpFilterVal

      let questionJumpFilterVal = this.questionJumpFilter(form)
      nextStep = questionJumpFilterVal === false ? nextStep : questionJumpFilterVal

      if (form.formSetting.steps_summary && nextStep === -1) {
        nextStep = form.steps.length
      }

      return nextStep
    },

    questionJumpFilter: function (form) {
      for (let question of form.steps[this.currentStep].questions) {
        if (question.type === 'SINGLE_CHOICE') {
          if (question.jumps && question.jumps.length > 0 && question.value) {
            for (let jump of question.jumps) {
              if (jump.conditions[0] && jump.conditions[0].choice === question.value.id) {
                return jump.step !== -1 ? jump.step - 1 : jump.step
              }
            }
          }
        } else if (question.type === 'MULTIPLE_CHOICE') {
          if (question.jumps && question.jumps.length > 0 && question.value && question.value.length) {
            for (let jump of question.jumps) {
              let matched
              let conditionIndex = 0
              for (let condition of jump.conditions) {
                let isSelectedChoice = question.value
                  .map((v) => v.id)
                  .indexOf(condition.choice) >= 0
                if (conditionIndex === 0) {
                  matched = isSelectedChoice
                  conditionIndex++
                  continue
                }
                let operator = jump.conditions[conditionIndex - 1].operator
                if (operator === 'AND') {
                  matched = matched && isSelectedChoice
                } else if (operator === 'OR') {
                  matched = matched || isSelectedChoice
                }
                conditionIndex++
              }
              if (matched) {
                return jump.step !== -1 ? jump.step - 1 : jump.step
              }
            }
          }
        }
      }
      return false
    },

    stepJumpFilter: function () {
      if (this.step && this.step.jump) {
        return this.step.jump.step === -1 ? this.step.jump.step : this.step.jump.step - 1
      }
      return false
    },

    formSummaryFilter: function (form, nextStep) {
      // one step before form summary
      if (form.formSetting.steps_summary && this.currentStep === this.stepCount - 2) {
        return this.currentStep + 1
      }
      // at form summary step
      if (form.formSetting.steps_summary && this.currentStep >= this.stepCount - 1) {
        return -1
      }
      // at last step
      if (!form.formSetting.steps_summary && this.currentStep >= this.stepCount - 1) {
        return -1
      }
      return false
    },

    updateSingleChoiceRadioSkinValue: function (value, question) {
      question.value = question.choices.filter(c => c.id === value).pop()
    },

    mapMultiSelectCheckboxOptions: function (choices) {
      return choices.map((c) => {
        return {label: c.label, value: c}
      })
    }
  },

  computed : {
     warningColorStyle: function () {
      return {
        color: this.theme.general.colors.warning_color
      }
    },

    backButtonStyle: function () {
      return {
        backgroundColor: this.theme.ui_elements.step_navigation.back_button.backgroundColor,
        color: this.theme.ui_elements.step_navigation.back_button.font.color,
        fontFamily: this.theme.ui_elements.step_navigation.back_button.font.family,
        fontSize: this.theme.ui_elements.step_navigation.back_button.font.size + 'px',
        fontWeight: this.theme.ui_elements.step_navigation.back_button.font.weight,
        height: 'auto',
        padding: '10px',
        width: '100%',
        lineHeight: this.theme.ui_elements.step_navigation.back_button.font.line_height + 'px',
        borderRadius: this.theme.ui_elements.step_navigation.back_button.borderRadius + 'px',
        textTransform: 'none'
      }
    },

    nextButtonStyle: function () {
      return {
        backgroundColor: this.theme.ui_elements.step_navigation.next_button.backgroundColor,
        color: this.theme.ui_elements.step_navigation.next_button.font.color,
        fontFamily: this.theme.ui_elements.step_navigation.next_button.font.family,
        fontSize: this.theme.ui_elements.step_navigation.next_button.font.size + 'px',
        fontWeight: this.theme.ui_elements.step_navigation.next_button.font.weight,
        height: 'auto',
        padding: '10px',
        width: '100%',
        lineHeight: this.theme.ui_elements.step_navigation.next_button.font.line_height + 'px',
        borderRadius: this.theme.ui_elements.step_navigation.next_button.borderRadius + 'px',
        textTransform: 'none'
      }
    },
     submitButtonStyle: function () {
      return {
        backgroundColor: this.theme.ui_elements.step_navigation.submit_button.backgroundColor,
        color: this.theme.ui_elements.step_navigation.submit_button.font.color,
        fontFamily: this.theme.ui_elements.step_navigation.submit_button.font.family,
        fontSize: this.theme.ui_elements.step_navigation.submit_button.font.size + 'px',
        fontWeight:this.theme.ui_elements.step_navigation.submit_button.font.weight,
        height: 'auto',
        padding: '10px',
        width: '100%',
        lineHeight: this.theme.ui_elements.step_navigation.submit_button.font.line_height + 'px',
        borderRadius: this.theme.ui_elements.step_navigation.submit_button.borderRadius + 'px',
        textTransform: 'none'
      }
    },

    submitButtonStyle: function () {
      return {
        backgroundColor: this.theme.ui_elements.step_navigation.submit_button.backgroundColor,
        color: this.theme.ui_elements.step_navigation.submit_button.font.color,
        fontFamily: this.theme.ui_elements.step_navigation.submit_button.font.family,
        fontSize: this.theme.ui_elements.step_navigation.submit_button.font.size + 'px',
        fontWeight: this.theme.ui_elements.step_navigation.submit_button.font.weight,
        height: 'auto',
        padding: '10px',
        width: '100%',
        lineHeight: this.theme.ui_elements.step_navigation.submit_button.font.line_height + 'px',
        borderRadius: this.theme.ui_elements.step_navigation.submit_button.borderRadius + 'px'
      }
    },
    formStyle: function () {
      return {
        backgroundColor: this.theme.ui_elements.background.formColor,
        border: this.theme.ui_elements.background.form_border_width + 'px' + ' ' + this.theme.ui_elements.background.form_border_style + ' ' + this.theme.ui_elements.background.form_border_color,
        borderRadius: this.theme.ui_elements.background.form_border_radius + 'px'
      }
    },

    formBackgroundColor: function () {
      return {
        backgroundColor: this.theme.ui_elements.background.formColor
      }
    },

    formId: function () {
      return 'leadgenform_' + this.formKey
    },

    stepCount: function () {
      if(!this.form || !this.form.steps) {
        return 0;
      }
      if (this.form.formSetting.steps_summary) {
        return this.form.steps.length + 1
      }
      return this.form.steps.length
    },

    stepProgress: function () {
      if (this.formSent) {
        return 100
      }
      let stepCount = this.form.formSetting.steps_summary ? this.stepCount - 1 : this.stepCount
      let progress = parseInt((this.currentStep / stepCount) * 100)
      return progress
    },

    parsedMessage: function () {
      let msg = this.form.formSetting.thankyou_message
      if (!this.formSent) {
        return
      }
      if (msg.search('[choice_formula]') === -1) {
        return
      }
      try {
        let val = eval(this.parsedChoiceFormula())
        return msg.replace(/\[choice_formula\]/g, val || '')
      } catch (e) {
        if (e instanceof SyntaxError) {
        } else {
          throw (e)
        }
      }
    },

    summarySteps: function () {
      return (this.form.steps || []).filter((step, stepIndex) => {
        return this.stepHistory.indexOf(stepIndex) !== -1
      })
    },

    step: function () {
      return this.form.steps[this.currentStep] || null
    },

     backButBtnText: function () {
        return this.theme.ui_elements.step_navigation.back_button.text
     },
    continueBtnText: function () {
      if (this.getNextStep === -1) {
        return this.theme.ui_elements.step_navigation.submit_button.text || 'SUBMIT'
      }
      return this.theme.ui_elements.step_navigation.next_button.text
    },

    autocompleteCountryAddressField: function () {
      return countries.map((country) => {
        return country.name
      })
    },

    insideIframe: function () {
      return window.self !== window.top
    }
  },

  watch: {
    form: {
      handler: function (to, from) {
        this.getNextStep = this.computeNextStep(to)
      },
      deep: true
    }
  }
}
</script>


<style lang="scss">
@import url(https://fonts.googleapis.com/icon?family=Material+Icons);

@mixin alignments() {
  &.alignment-horizontal {
    flex-direction: row;
    > label, > div, > li {
      margin-right: 1.5rem;
      margin-left: 0;
      margin-bottom: 15px;
    }
    > label:last-child, > div:last-child, > li:last-child {
      margin-right: 0;
    }
  }
  &.alignment-horizontal_center {
    flex-direction: row;
    justify-content: center;
    > label, > div, > li {
      margin-right: 1.5rem;
      margin-left: 0;
      margin-bottom: 15px;
    }
    > label:last-child, > div:last-child, > li:last-child {
      margin-right: 0;
    }
  }
  &.alignment-horizontal_space_between {
    flex-direction: row;
    justify-content: space-between;
    > label, > div, > li {
      margin-right: 0;
      margin-left: 0;
      margin-bottom: 15px;
    }
    > label:last-child, > div:last-child, > li:last-child {
      margin-right: 0;
    }
  }
  &.alignment-horizontal_space_around {
    flex-direction: row;
    justify-content: space-around;
    > label, > div, > li {
      margin-right: 0;
      margin-left: 0;
      margin-bottom: 15px;
    }
    > label:last-child, > div:last-child, > li:last-child {
      margin-right: 0;
    }
  }
}

body {
  overflow-y: auto !important;
}

.drop-element {
  z-index: 10000;
}

.ql-editor {
  &[contenteditable="true"] {
    background-color: #f5f0f0;
  }
  overflow: unset !important;
}

.ql-editor h1, .ql-editor h2, h1, h2 {
  font-weight: normal;
}

.ql-snow .ql-editor h1, .ql-editor h1,  h1{
  font-size: 45px;
  line-height: 48px;
  font-weight: 500;
  margin-bottom: 25px;
}

.ql-snow .ql-editor h2, .ql-editor h2,  h2{
  font-size: 36px;
  line-height: 40px;
  font-weight: 500;
  margin-bottom: 20px;
}

.ql-snow .ql-editor h3, .ql-editor h3, h3 {
  font-size: 30px;
  line-height: 45px;
  font-weight: 500;
  margin-bottom: 15px;
}

.ql-snow .ql-editor h4, .ql-editor h4, h4 {
  font-size: 24px;
  line-height: 36px;
  font-weight: 400;
  margin-bottom: 10px;
}

.ql-snow .ql-editor h5, .ql-editor h5, h5 {
  font-size: 20px;
  line-height: 30px;
  font-weight: 400;
  margin-bottom: 10px;
}

.ql-snow .ql-editor h6, .ql-editor h6, h6 {
  font-size: 16px;
  line-height: 25px;
  font-weight: 400;
  margin-bottom: 10px;
}

.ql-snow .ql-editor p, .ql-editor p, p {
  font-size: 16px;
  line-height: 30px;
  font-weight: 400;
  margin-bottom: 10px;
}

.ql-editor ul, .ql-editor ol {
  padding-left: 0;
  margin-left: 0;
}

.ql-editor li{
  font-size: 16px;
  line-height: 30px;
}

.ql-editor a {
  color: #039be5;
}

.main-front-container .front-footer a { font-size: 11px !important; }

.is-danger {
  color: red;
}

.lf-form__progress {
  margin: 0px;
}

.lf-form {
  flex-basis: 100%;
  &__error {
    text-align: center;
    p {
      color: red;
    }
  }

  &__preloader {
    display: table;
    width: 100%;
    min-height: 200px;
    text-align: center;
    &__content {
      display: table-cell;
      width: 100%;
      vertical-align: middle;
      text-align: center;
      .ui-progress-circular {
        margin: auto;
      }
    }
  }

  .ql-editor {
    padding: 0;
  }

  .lf-form__thankyou {
    &__msg {
      padding: 30px 0px;
      font-weight: 300;
      font-size: 18px;
      text-align: center;
    }
  }

  label {
    color: black;
    font-size: 16px;
  }

  &__content {
    form {
      // padding: 25px;
      .lf-form__summary {
        overflow-y: auto;
        > h6 {
          padding: 20px 0px;
        }
        .lf-form__step-summary {
          .lf-form__question-summary {
            margin-bottom: 30px;
            > h6 {
              font-weight: bold;
            }
            > div {
              background: #f5efef;
              padding: 10px;
              i {
                cursor: pointer;
                color:#2196f3;
                font-size: 16px;
              }
              span {
                display: block;
                word-break: break-word;
              }
            }
          }
        }
      }
      .lf-form__step {
        &:after {
          content : "";
          clear: both;
          display: block;
        }
        .lf-form__step__item {
          // margin: 30px 0px;
          .lf-form__question {
            &:after {
              content: "";
              clear: both;
              display: block;
            }
            &__inputfield {
              > label {
                margin-bottom: 15px !important;
                display: inline-block;
                font-weight: 500;
                font-size: 18px;
              }
            }
            &__description {
              margin: 0 !important;
              font-weight: 400;
              font-size: 14px;
              margin-bottom: 10px !important;
            }
            &__field {
              margin-bottom: 20px;
              input {
                margin-bottom: 0;
              }
            }
            &__fields {
              > div:last-child {
                margin-bottom: 0;
              }
            }
            &__errors {
              span {
                font-size: 14px;
              }
            }
            &__error {
              margin-bottom: 5px;
              span {
                font-size: 14px;
              }
            }
          }
          .lf-form__single-select-question {
            .lf-form__radio-list {
              display: flex;
              flex-direction: column;
              flex-wrap: wrap;
              @include alignments();
            }
            .lf-form__radio-options {
              display: flex;
              flex-direction: column;
              flex-wrap: wrap;
              @include alignments();
              &__item {
                padding: 5px 0;
              }
            }
          }
          .lf-form__multi-select-question {
            .lf-form__radio-list {
              display: flex;
              flex-direction: column;
              flex-wrap: wrap;
              @include alignments();
            }
            .lf-form__checkbox-options {
              .ui-checkbox-group__checkboxes {
                display: flex;
                flex-direction: column;
                flex-wrap: wrap;
              }
              &.alignment-horizontal {
                .ui-checkbox-group__checkboxes {
                  flex-direction: row;
                  label {
                    margin-right: 1.5rem;
                    margin-left: 0;
                    margin-bottom: 15px;
                  }
                  label:last-child {
                    margin-right: 0;
                  }
                }
              }
              &.alignment-horizontal_center {
                .ui-checkbox-group__checkboxes {
                  flex-direction: row;
                  justify-content: center;
                  label {
                    margin-right: 1.5rem;
                    margin-left: 0;
                    margin-bottom: 15px;
                  }
                  label:last-child {
                    margin-right: 0;
                  }
                }
              }
              &.alignment-horizontal_space_between {
                .ui-checkbox-group__checkboxes {
                  flex-direction: row;
                  justify-content: space-between;
                  label {
                    margin-right: 0;
                    margin-left: 0;
                    margin-bottom: 15px;
                  }
                  label:last-child {
                    margin-right: 0;
                  }
                }
              }
              &.alignment-horizontal_space_around {
                .ui-checkbox-group__checkboxes {
                  flex-direction: row;
                  justify-content: space-around;
                  label {
                    margin-right: 0;
                    margin-left: 0;
                    margin-bottom: 15px;
                  }
                  label:last-child {
                    margin-right: 0;
                  }
                }
              }
            }
          }
        }
      }
      .lf-form__step-nav {
        margin-top: 20px;
        button {
          height: 40px;
        }
        .lf-form__first-step-nav {
          display: flex;
          justify-content: center;
          button {
            width: 100%;
          }
        }
        .lf-form__prev-step-btn {
          text-align: left;
          .ui-icon {
            -ms-transform: rotate(180deg) !important;
            -webkit-transform: rotate(180deg) !important;
            transform: rotate(180deg) !important;
          }
          button {
            width: 100%;
          }
        }
        .lf-form__mid-step-nav {
          display: flex;
          justify-content: space-around !important;
          .back_button_wrapper, .continue_button_wrapper, .submit_button_wrapper {
            display: flex;
          }
          button {
            &:first-child {
              margin: 0 10px;
            }
            &:last-child {
            }
          }
        }
        .lf-form__last-step-nav {
          display: flex;
          flex-wrap: wrap;
          justify-content: center;
          > div:first-child {
            width: 100%;
            display: flex;
            justify-content: center;
            button {
              width: 100%;
            }
          }

          > *:last-child {
            width: 100%;
          }
        }
      }
      [type="radio"]:not(:checked),
      [type="checkbox"]:not(:checked) {
        pointer-events: auto !important;
        opacity: 0.011 !important;
      }
      .lf-form__radio-list {
        list-style-type:none;
        text-align: center;
        padding: 0;
        &:after {
          content : '';
          clear: both;
          display: block;
        }
        li {
          position: relative;
          float: left;
          margin: 0px 5px 5px 0px;
          input[type="radio"],
          input[type="checkbox"]{
            position: absolute !important;
            z-index: 100;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            opacity: 0.01;
            cursor: pointer;
          }
          label {
            padding:10px 15px;
            display: block;
            height: auto;
            border:2px solid #CCC;
            // border-radius: 20px;
            line-height: 20px;
            font-size: 14px;
            word-break: break-all;
            cursor:pointer;
            z-index:90;
            &:before, &:after {
              display: none !important;
            }
            &.checkbox-label {
            }
          }
        }
      }
      .lf-form__gdpr-terms {
        * {
        }
        p {
          text-align: center;
          line-height: 20px;
        }
        a {
          color: blue;
        }
      }
    }
  }
}

.lf-form form .lf-form__radio-list li {
  float: none;
  text-align: left;
}

/* helpers */
.lf-shadow {
  box-shadow: 0 0 2px rgba(0,0,0,.12), 0 2px 2px rgba(0,0,0,.2);
}
.lf-radius {
  border-radius: 25px;
}
.lf-red-text {
  color: red;
}
.lf-center-align {
  text-align: center;
}

.ui-select__display-value.is-placeholder {
  color: #757575;
}

.ui-radio__outer-circle {
  box-sizing: border-box;
}

.ui-select__dropdown {
  z-index: 10000;
}
.formShadow {
   box-shadow: 0px 0px 10px grey;
}


/*
.drop-element {
  z-index: 10000;
}

.ql-editor {
  &[contenteditable="true"] {
    background-color: #f5f0f0;
  }
  overflow: unset !important;
}

.ql-editor h1, .ql-editor h2 {
  font-weight: normal;
}

.ql-snow .ql-editor h1, .ql-editor h1{
  font-size: 45px;
  line-height: 48px;
  font-weight: 600;
  margin-bottom: 25px;
}

.ql-snow .ql-editor h2, .ql-editor h2{
  font-size: 36px;
  line-height: 40px;
  font-weight: 600;
  margin-bottom: 20px;
}

.ql-snow .ql-editor h3, .ql-editor h3{
  font-size: 30px;
  line-height: 45px;
  font-weight: 600;
  margin-bottom: 15px;
}

.ql-snow .ql-editor h4, .ql-editor h4 {
  font-size: 24px;
  line-height: 36px;
  font-weight: 400;
  margin-bottom: 10px;
}

.ql-snow .ql-editor h5, .ql-editor h5 {
  font-size: 20px;
  line-height: 30px;
  font-weight: 400;
  margin-bottom: 10px;
}

.ql-snow .ql-editor h6, .ql-editor h6 {
  font-size: 16px;
  line-height: 25px;
  font-weight: 400;
  margin-bottom: 10px;
}

.ql-snow .ql-editor p, .ql-editor p {
  font-size: 16px;
  line-height: 30px;
  font-weight: 400;
  margin-bottom: 10px;
}


.ql-editor ul, .ql-editor ol {
  padding-left: 0;
  margin-left: 0;
}

.ql-editor li {
  font-size: 16px;
  line-height: 30px;
}

.ql-editor a {
  color: #039be5;
}

.main-front-container .front-footer a { font-size: 11px !important; }

.lf-form {
  flex-basis: 100%;
}
.is-danger {
  color: red;
}
.lf-form__progress {
  margin: 0px;
}

.lf-form {
  .ql-editor {
    padding: 0;
  }
  .loader {
    display: table;
    width: 100%;
    min-height: 200px;
    .loader-content {
      display: table-cell;
      width: 100%;
      vertical-align: middle;
      text-align: center;
      .ui-progress-circular {
        margin: auto;
      }
    }
  }
  .form-thankyou-wrapper {
    .msg {
      padding: 30px 0px;
      font-weight: 300;
      font-size: 18px;
      text-align: center;
    }
  }
  label {
    color: black;
    font-size: 16px;
  }
  form {
    padding: 15px;
    input {
      height: 2rem;
    }
    .form-summary {
      overflow-y: auto;
      > h6 {
        padding: 20px 0px;
      }
      .step-summary {
        .question-summary {
          margin-bottom: 30px;
          > h6 {
            font-weight: bold;
          }
          > div {
            background: #f5efef;
            padding: 10px;
            i {
              cursor: pointer;
              color:#2196f3;
              font-size: 16px;
            }
            span {
              display: block;
            }
          }
        }
      }
    }
    .step {
      &:after {
        content : "";
        clear: both;
        display: block;
      }
      .step__item {
        margin: 30px 0px;
        .question {
          &:after {
            content: "";
            clear: both;
            display: block;
          }
          .inputfield {
            > label {
              margin-bottom: 10px;
              display: inline-block;
              font-weight: 600;
              font-size: 18px;
            }
            > .question-description {
              margin: 0 !important;
              font-weight: 400;
              font-size: 14px;
              margin-bottom: 10px !important;
            }
          }
        }
        .element {
        }
      }
    }
    .step-nav {
      margin-top: 20px;
      button {
        height: 40px;
      }
      .first-step-nav {
        display: flex;
        justify-content: center;
        button {
          width: 100%;
        }
      }
      .prev-step-btn {
        text-align: left;
        .ui-icon {
          -ms-transform: rotate(180deg) !important;
          -webkit-transform: rotate(180deg) !important;
          transform: rotate(180deg) !important;
        }
        button {
          width: 100%;
        }
      }
      .mid-step-nav {
        display: flex;
        justify-content: center;
        button {
          width: 100%;
          &:first-child {
            margin: 0 10px;
          }
          &:last-child {
            margin: 0 10px;
          }
        }
      }
      .last-step-nav {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        > div:first-child {
          width: 100%;
          display: flex;
          justify-content: center;
          button {
            width: 100%;
          }
        }

        > *:last-child {
          width: 100%;
        }
      }
    }
    [type="radio"]:not(:checked),
    [type="checkbox"]:not(:checked) {
      pointer-events: auto !important;
      opacity: 0.011 !important;
    }
    .radio-list {
      list-style-type:none;
      text-align: center;
      padding: 0;
      &:after {
        content : '';
        clear: both;
        display: block;
      }
      li {
        position: relative;
        float: none;
        margin: 0px 5px 5px 0px;
        text-align: left;
        input[type="radio"],
        input[type="checkbox"]{
          position: absolute !important;
          z-index: 100;
          left: 0;
          right: 0;
          top: 0;
          bottom: 0;
          width: 100%;
          height: 100%;
          opacity: 0.01;
          cursor: pointer;
        }
        // input[type="radio"]:checked + label,
        // input[type="radio"]:hover + label,
        // input[type="checkbox"]:checked + label,
        // input[type="checkbox"]:hover + label {
        //   background: #2196f3;
        //   border: 2px solid #2196f3;
        //   color: white;
        //   box-shadow: 0 0 5px #9e8686;
        // }
        label {
          padding:10px 15px;
          display: block;
          height: auto;
          border:2px solid #CCC;
          border-radius: 20px;
          line-height: 20px;
          font-size: 14px;
          word-break: break-all;
          cursor:pointer;
          z-index:90;
          &:before, &:after {
            display: none !important;
          }
          &.checkbox-label {
            border-radius: 5px;
          }
        }
      }
    }
    .gdpr-terms {
      * {
        font-size: 12px;
      }
      p {
        text-align: center;
        line-height: 20px;
      }
      a {
        color: blue;
      }
    }
  }
    .shadow {
         box-shadow: 0 0 2px rgba(0,0,0,.12), 0 2px 2px rgba(0,0,0,.2);
         }
    .radius {
      border-radius: 25px;
    }

}*/
</style>
