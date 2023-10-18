<template>
  <div :class="{'lf-form': true, 'lf-static-height': hasStaticHeight}" :style="extracss">
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
    <div class="lf-form__content" v-if="themeLoaded" :style="formStyle" :class='{formShadow: insideIframe ? false : theme.ui_elements.background.formShadow}'>
      <!-- FORM PROGRESS -->
      <div v-if="theme.ui_elements.step_progress.progressPosition === progressPositionIds.TOP" class="lf-form__progress"  :style="progressStyle">
        <div v-if="theme.ui_elements.step_progress.showProgress" role="progressbar progress-striped"  class="progress-bar" :style="progressStep"></div>
      </div>
      <!-- FORM -->
      <form @submit.prevent="onFormSubmit" :id="formId" data-vv-scope="leadgen-form" v-if="!formSent" :style="formPadding" novalidate>
        <!-- FORM STEPS -->

        <div class="lf-form__step__container fade-enter-active" v-for="(step, sindex) in form.steps" :key="step.id" :class="{'lf-visibility-hidden': currentStep !== sindex && hasStaticHeight, 'lf-visibility-none': currentStep !== sindex && !hasStaticHeight, 'fade-inactive': sindex === 0}">
          <div class="lf-form__step" :class="{'fade-enter-active': !hasStaticHeight || hasStaticHeight && currentStep == sindex}">
              <div class="lf-form__step__item" v-for="(stepItem, stepItemIndex) in stepItems(step)" :key="stepItemIndex">
                <div class="lf-form__step__item-element" v-if="stepItem.type === 'element'">
                  <!-- ELEMENT ITEM -->
                  <div class="lf-form__element">
                    <div class="ql-editor" v-html="stepItem.element.content"></div>
                  </div>
                </div>
                <div class="lf-form__step__item-question" v-if="stepItem.type === 'question'">
                  <!-- QUESTION ITEM -->
                  <div class="lf-form__question" v-if="stepItem.question.type === 'SHORT_TEXT' || stepItem.question.type === 'FIRST_NAME' || stepItem.question.type === 'LAST_NAME'" :data-question-type="stepItem.question.type">
                    <div class="lf-form__question__inputfield">
                      <label :for="questionFieldName(stepItem.question)" class="active" v-if="!stepItem.question.hide_title && !theme.ui_elements.question.hide_question_labels"> {{stepItem.question.title}}
                        <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span>
                      </label>
                      <p class="lf-form__question__description" v-if="stepItem.question.description">{{ stepItem.question.description }}</p>
                      <ui-textbox v-validate="validateObject(stepItem.question, sindex)" :data-vv-scope="formScope" data-vv-value-path="value" :name="questionFieldName(stepItem.question)" v-model.trim="form['steps'][sindex]['questions'][stepItem.qindex]['value']" :placeholder="stepItem.question.placeholder" :id="questionFieldName(stepItem.question)" type="text" @keydown="onFormEnterKey($event)" @focus="saveVisitInteractedAt()">
                      </ui-textbox>
                      <div class="lf-form__question__errors" v-if="currentStep === sindex && validation.showErrors">
                        <span  v-show="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger" :style="warningColorStyle">This field is required</span>
                      </div>
                    </div>
                  </div>
                  <!-- PARAGRAPH QUESTION -->
                  <div class="lf-form__question" v-if="stepItem.question.type === 'PARAGRAPH_TEXT'" :data-question-type="stepItem.question.type">
                    <div class="lf-form__question__inputfield">
                      <label v-if="!stepItem.question.hide_title && !theme.ui_elements.question.hide_question_labels" :for="questionFieldName(stepItem.question)" class="active"> {{stepItem.question.title}}
                    <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span>
                      </label>
                      <p class="lf-form__question__description" v-if="stepItem.question.description">{{ stepItem.question.description }}</p>
                      <ui-textbox type="text" :rows="3" v-validate="validateObject(stepItem.question, sindex)" :data-vv-scope="formScope" data-vv-value-path="value" :name="questionFieldName(stepItem.question)" v-model.trim="form['steps'][sindex]['questions'][stepItem.qindex]['value']" :id="questionFieldName(stepItem.question)" :placeholder="stepItem.question.placeholder" class="materialize-textarea" :multiLine="true" @focus="saveVisitInteractedAt()"/>
                      <div class="lf-form__question__errors" v-if="currentStep === sindex && validation.showErrors">
                        <span  v-show="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger" :style="warningColorStyle">This field is required</span>
                      </div>
                    </div>
                  </div>
                  <!-- EMAIL QUESTION -->
                  <div class="lf-form__question" v-if="stepItem.question.type === 'EMAIL_ADDRESS'" :data-question-type="stepItem.question.type">
                    <div class="lf-form__question__inputfield">
                      <label v-if="!stepItem.question.hide_title && !theme.ui_elements.question.hide_question_labels" :for="questionFieldName(stepItem.question)" class="active"> {{stepItem.question.title}}
                        <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span>
                      </label>
                      <p class="lf-form__question__description" v-if="stepItem.question.description">{{ stepItem.question.description }}</p>
                      <ui-textbox type="email" v-validate="validateObject(stepItem.question, sindex)" :name="questionFieldName(stepItem.question)" v-model.trim="form['steps'][sindex]['questions'][stepItem.qindex]['value']" :placeholder="stepItem.question.placeholder" :id="questionFieldName(stepItem.question)" :data-vv-scope="formScope" data-vv-value-path="value" @keydown="onFormEnterKey($event)" focust="saveVisitInteractedAt()" />
                      <div class="lf-form__question__errors" v-if="currentStep === sindex && validation.showErrors">
                        <span v-show="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger" :style="warningColorStyle">This field is required</span>
                        <span v-show="checkErr(questionFieldName(stepItem.question), 'email')" class="is-danger" :style="warningColorStyle">This field must be a valid email</span>
                        <span v-show="checkErr(questionFieldName(stepItem.question), 'validateEmailDomain')" class="is-danger" :style="warningColorStyle">{{checkErr(questionFieldName(stepItem.question), 'validateEmailDomain')}}</span>
                      </div>
                    </div>
                  </div>
                  <!-- PHONE QUESTION -->
                  <div class="lf-form__question" v-if="stepItem.question.type === 'PHONE_NUMBER'" :data-question-type="stepItem.question.type">
                    <div class="lf-form__question__inputfield">
                      <label v-if="!stepItem.question.hide_title && !theme.ui_elements.question.hide_question_labels" :for="questionFieldName(stepItem.question)" class="active"> {{stepItem.question.title}}
                        <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span>
                      </label>
                      <p class="lf-form__question__description" v-if="stepItem.question.description">{{ stepItem.question.description }}</p>
                        <ui-textbox v-validate="validateObject(stepItem.question, sindex)" :data-vv-scope="formScope" data-vv-value-path="value" :name="questionFieldName(stepItem.question)" v-model.trim="form['steps'][sindex]['questions'][stepItem.qindex]['value']" :placeholder="stepItem.question.placeholder" :id="questionFieldName(stepItem.question)" type="tel" @keydown="onFormEnterKey($event)" @focus="saveVisitInteractedAt()"/>
                      <div class="lf-form__question__errors" v-if="currentStep === sindex && validation.showErrors">
                        <span v-if="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger" :style="warningColorStyle">This field is required</span>
                        <span class="is-danger" :style="warningColorStyle" v-else-if="stepItem.question.valid !== true">Invalid Phone Number</span>
                      </div>
                    </div>
                  </div>
                  <!-- URL QUESTION -->
                  <div class="lf-form__question" v-if="stepItem.question.type === 'URL'" :data-question-type="stepItem.question.type">
                    <div class="lf-form__question__inputfield">
                      <label v-if="!stepItem.question.hide_title && !theme.ui_elements.question.hide_question_labels" :for="questionFieldName(stepItem.question)" class="active"> {{stepItem.question.title}}
                        <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span>
                      </label>
                      <p class="lf-form__question__description" v-if="stepItem.question.description">{{ stepItem.question.description }}</p>
                        <ui-textbox v-validate="validateObject(stepItem.question, sindex)" :data-vv-scope="formScope" data-vv-value-path="value" :name="questionFieldName(stepItem.question)" v-model.trim="form['steps'][sindex]['questions'][stepItem.qindex]['value']" :placeholder="!stepItem.question.placeholder ? 'https://' : stepItem.question.placeholder" :id="questionFieldName(stepItem.question)" type="text" @keydown="onFormEnterKey($event)" @focus="saveVisitInteractedAt()"/>
                      <div class="lf-form__question__errors" v-if="currentStep === sindex && validation.showErrors">
                        <span v-if="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger" :style="warningColorStyle">This field is required</span>
                        <span class="is-danger" :style="warningColorStyle" v-else-if="checkErr(questionFieldName(stepItem.question), 'url')">The URL must be a valid URL</span>
                      </div>
                    </div>
                  </div>
                  <!-- NUMBER QUESTION -->
                  <div class="lf-form__question" v-if="stepItem.question.type === 'NUMBER'" :data-question-type="stepItem.question.type">
                    <div class="lf-form__question__inputfield">
                      <label v-if="!stepItem.question.hide_title && !theme.ui_elements.question.hide_question_labels" :for="questionFieldName(stepItem.question)" class="active"> {{stepItem.question.title}}
                        <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span>
                      </label>
                      <p class="lf-form__question__description" v-if="stepItem.question.description">{{ stepItem.question.description }}</p>
                        <ui-textbox v-validate="validateObject(stepItem.question, sindex)" :data-vv-scope="formScope" data-vv-value-path="value" :name="questionFieldName(stepItem.question)" v-model.trim="form['steps'][sindex]['questions'][stepItem.qindex]['value']" :placeholder="stepItem.question.placeholder" :id="questionFieldName(stepItem.question)" type="text" @keydown="onFormEnterKey($event)" @focus="saveVisitInteractedAt()"/>
                      <div class="lf-form__question__errors" v-if="currentStep === sindex && validation.showErrors">
                        <span v-if="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger" :style="warningColorStyle">This field is required</span>
                        <span class="is-danger" :style="warningColorStyle" v-else-if="checkErr(questionFieldName(stepItem.question), 'numberLimit')">{{checkErr(questionFieldName(stepItem.question), 'numberLimit')}}</span>
                        <span v-else-if="checkErr(questionFieldName(stepItem.question), 'numeric')" class="is-danger" :style="warningColorStyle">This Field must contain numbers only</span>
                      </div>
                    </div>
                  </div>
                  <!-- RANGE QUESTION -->
                  <div class="lf-form__question" v-if="stepItem.question.type === 'RANGE'" :data-question-type="stepItem.question.type">
                    <div  class="lf-form__question__inputfield">
                      <label v-if="!stepItem.question.hide_title && !theme.ui_elements.question.hide_question_labels" :for="questionFieldName(stepItem.question)" class="active"> {{stepItem.question.title}}
                        <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span>
                      </label>
                      <p class="lf-form__question__description" v-if="stepItem.question.description">{{ stepItem.question.description }}</p>
                      <!-- Slider Scale skin -->
                      <div v-if="stepItem.question.skin.id === rangeSkinIds.SLIDER_SCALE" class="lf-form__question__scale">
                        <div class="lf-form-scale-counter-wrapper">
                          <div class="lf-form-scale-counter">
                            <div class="unit-left" v-if="stepItem.question.enableUnitValues && stepItem.question.rangeFields.unitAlignment === 'left'">{{stepItem.question.rangeFields.unit}}</div>
                            <div style="font-weight:bold; display:flex;">
                            <input type="text"
                              v-validate="'required|between:' + stepItem.question.rangeFields.minScaleValue + ',' + stepItem.question.rangeFields.maxScaleValue"
                              :step="parseInt(stepItem.question.rangeFields.stepCount)"
                              :maxlength="parseInt(stepItem.question.rangeFields.maxScaleValue).toString().length"
                              v-model.trim="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                              :size="parseInt(form['steps'][sindex]['questions'][stepItem.qindex]['value']).toString().length"
                              :max="parseInt(stepItem.question.rangeFields.maxScaleValue)"
                              :min="parseInt(stepItem.question.rangeFields.minScaleValue)"
                              :style="{ width: (30 + (parseInt(form['steps'][sindex]['questions'][stepItem.qindex]['value']).toString().length - 2) * 10) + 'px' }"
                              >
                              <div class="lf-form__question__errors" v-show="errors.has(questionFieldName(stepItem.question))">
                                  <span class="is-danger" v-show="errors.has(questionFieldName(stepItem.question))">
                                      {{ errors.first(questionFieldName(stepItem.question)) }}
                                  </span>
                              </div>
                               <div class="unit-right"  v-if="stepItem.question.enableUnitValues && stepItem.question.rangeFields.unitAlignment === 'right'" >{{stepItem.question.rangeFields.unit}}</div>
                            </div>
                          </div>
                        </div>
                        <ui-slider
                          v-validate="validateObject(stepItem.question, sindex)"
                          class="slider"
                          :data-vv-scope="formScope" data-vv-value-path="value"
                          :name="questionFieldName(stepItem.question)"
                          :min="parseInt(stepItem.question.rangeFields.minScaleValue)"
                          :max="parseInt(stepItem.question.rangeFields.maxScaleValue)"
                          :step="parseInt(stepItem.question.rangeFields.stepCount)"
                          v-model.trim="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                          style="margin-bottom: 30px;">
                        </ui-slider>
                        <div class="lf-form__question__errors"  v-if="currentStep === sindex && validation.showErrors" >
                          <span class="is-danger" :style="warningColorStyle" v-if="form['steps'][sindex]['questions'][stepItem.qindex]['value'] < parseInt(stepItem.question.rangeFields.minScaleValue)">
                            {{ form['steps'][sindex]['questions'][stepItem.qindex]['value'] < parseInt(stepItem.question.rangeFields.minScaleValue) ? `Value needs to be higher than ${stepItem.question.rangeFields.minScaleValue}` : '' }}
                          </span>
                          <span class="is-danger" :style="warningColorStyle" v-if="isNaN(form['steps'][sindex]['questions'][stepItem.qindex]['value'])">
                            Please enter a valid number between {{ $(stepItem.question.rangeFields.minScaleValue)}} and {{ $(stepItem.question.rangeFields.maxScaleValue)}}
                          </span>
                          <span v-else-if="checkErr(questionFieldName(stepItem.question), 'numeric')" class="is-danger" :style="warningColorStyle">This Field must contain numbers only</span>
                            </div>
                      </div>
                      <!-- Multi Range Scale Skin -->
                      <div v-if="stepItem.question.skin.id === rangeSkinIds.RANGE_SCALE" class="lf-form__question__range_scale">
                        <div  class="lf-form-range-counter-wrapper">
                          <div class="lf-form-range-counter">
                            <div  class="unit-left" v-if="stepItem.question.enableUnitValues && stepItem.question.rangeFields.unitAlignment === 'left'">{{stepItem.question.rangeFields.unit}}</div>
                            <div class="scale-min-value">
                             <input
                             class="sliderCount"
                             type="text"
                             v-model="stepItem.question.rangeFields.valueMin"
                             @input="setInputValue($event, stepItem.question, 'left')"
                             :step="parseInt(stepItem.question.rangeFields.stepCount)"
                             :maxlength="parseInt(stepItem.question.rangeFields.maxScaleValue).toString().length"
                             :max="parseInt(stepItem.question.rangeFields.valueMax) - 1"
                             :style="{ width: (30 + (parseInt(stepItem.question.rangeFields.valueMin).toString().length - 2) * 10) + 'px' }"
                              v-validate="'required|between:' + stepItem.question.rangeFields.minScaleValue + ',' + stepItem.question.rangeFields.maxScaleValue"
                              >
                            </div>
                             <div v-if="stepItem.question.enableUnitValues && stepItem.question.rangeFields.unitAlignment === 'right'" class="unit-right">{{stepItem.question.rangeFields.unit}}</div>
                            <span class="lf-form-dash">-</span>
                            <div  class="unit-left" v-if="stepItem.question.enableUnitValues && stepItem.question.rangeFields.unitAlignment === 'left'">{{stepItem.question.rangeFields.unit}}</div>
                            <div class="scale-max-value">
                            <input
                             class="sliderCount"
                             type="text"
                             v-model="stepItem.question.rangeFields.valueMax"
                             @input="setInputValue($event, stepItem.question, 'right')"
                             :step="parseInt(stepItem.question.rangeFields.stepCount)"
                             :maxlength="parseInt(stepItem.question.rangeFields.maxScaleValue).toString().length"
                             :max="parseInt(stepItem.question.rangeFields.maxScaleValue)"
                             :style="{ width: (30 + (parseInt(stepItem.question.rangeFields.valueMax).toString().length - 2) * 10) + 'px' }"
                              v-validate="'required|between:' + stepItem.question.rangeFields.valueMin + ',' + stepItem.question.rangeFields.valueMax"
                              >
                            </div>
                            <div  v-if="stepItem.question.enableUnitValues && stepItem.question.rangeFields.unitAlignment === 'right'" class="unit-right">{{stepItem.question.rangeFields.unit}}</div>
                          </div>
                        </div>
                        <div class="lf-form-range-slider-wrapper">
                          <div class="lf-form-multi-range-slider">
                            <input
                             @focus="saveVisitInteractedAt()"
                             type="range"
                             :id="'input-left-' + stepItem.question.id"
                             :name="questionFieldName(stepItem.question)"
                             :min="parseInt(stepItem.question.rangeFields.minScaleValue)"
                             :max="parseInt(stepItem.question.rangeFields.maxScaleValue)"
                             :value="stepItem.question.rangeFields.valueMin"
                             :step="parseInt(stepItem.question.rangeFields.stepCount)"
                             @input="setInputValue($event, stepItem.question, 'left')"
                            >
		                        <input
                              type="range"
                              :id="'input-right-' + stepItem.question.id"
                              :name="questionFieldName(stepItem.question)"
                              :min="parseInt(stepItem.question.rangeFields.minScaleValue)"
                              :max="parseInt(stepItem.question.rangeFields.maxScaleValue)"
                              :value="stepItem.question.rangeFields.valueMax"
                              :step="parseInt(stepItem.question.rangeFields.stepCount)"
                              @input="setInputValue($event, stepItem.question, 'right')"
                            >
                            <div class="slider">
                              <div class="track"></div>
                              <div :id="'slider-range-' + stepItem.question.id" class="range"></div>
                              <div :id="'thumb-left-' + stepItem.question.id" class="thumb left"></div>
                              <div :id="'thumb-right-' + stepItem.question.id" class="thumb right"></div>
                            </div>
                          </div>
                        </div>
                          <div class="lf-form__question__errors"  v-if="currentStep === sindex && validation.showErrors" >
                              <span class="is-danger" :style="warningColorStyle" v-if="stepItem.question.rangeFields.valueMin < parseInt(stepItem.question.rangeFields.minScaleValue) || stepItem.question.rangeFields.valueMax < parseInt(stepItem.question.rangeFields.minScaleValue)">
                                {{ stepItem.question.rangeFields.valueMin < parseInt(stepItem.question.rangeFields.minScaleValue) ? `Min value needs to be higher than ${stepItem.question.rangeFields.minScaleValue}` : '' }} <br>
                                {{ stepItem.question.rangeFields.valueMax < parseInt(stepItem.question.rangeFields.minScaleValue) ? `Max value needs to be lower than ${stepItem.question.rangeFields.maxScaleValue}` : '' }}
                              </span>
                              <span class="is-danger" :style="warningColorStyle" v-if="stepItem.question.rangeFields.valueMin > parseInt(stepItem.question.rangeFields.valueMax)">
                                {{ stepItem.question.rangeFields.valueMax < parseInt(stepItem.question.rangeFields.valueMin) ? `Max value needs to be higher than Min Value` : '' }} <br>
                              </span>
                              <span class="is-danger" :style="warningColorStyle" v-if="stepItem.question.rangeFields.valueMin === '' || stepItem.question.rangeFields.valueMax === ''">
                                Please enter a value between {{ stepItem.question.rangeFields.minScaleValue }} and {{ stepItem.question.rangeFields.maxScaleValue }}.
                              </span>
                            </div>
                      </div>

                      <!-- Likert Radios skin -->
                      <div v-if="stepItem.question.skin.id === rangeSkinIds.LIKERT_SCALE">
                        <div class="lf-form-likert-radio-options">
                          <div v-for="(choice, cindex) in stepItem.question.rangeFields.likertRadios" :key="cindex">
                            <div class="lf-form-likert-radio-wrapper-start" v-if="stepItem.question.showHideOrientationScale">
                              <p class="lf-form-likert-radio-text">{{choice.id === 1 ? stepItem.question.rangeFields.lowerEndScaleText : ''}}</p>
                            </div>
                            <div class="lf-form-likert-radio-wrapper-end" v-if="stepItem.question.showHideOrientationScale">
                              <p class="lf-form-likert-radio-text">{{choice.id == (!stepItem.question.rangeFields.maxScaleLimit ? 5 : stepItem.question.rangeFields.maxScaleLimit ) ? stepItem.question.rangeFields.higherEndScaleText : ''}}</p>
                            </div>
                            <div style="margin-left:5px; padding-bottom: 11px;" class="lf-form-likert-radio-label">
                              {{ choice.label }}
                            </div>
                            <ui-radio
                              v-validate="validateObject(stepItem.question, sindex)"
                              :data-vv-scope="formScope"
                              data-vv-value-path="value"
                              :id="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex"
                              :name="questionFieldName(stepItem.question)"
                              :value="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                              @input="updateLikertSkinValue($event, form['steps'][sindex]['questions'][stepItem.qindex])"
                              :true-value="choice.id"
                              @focus="saveVisitInteractedAt()">
                            </ui-radio>
                          </div>
                        </div>
                      </div>

                      <!-- likert smileys skin -->
                      <div v-if="stepItem.question.skin.id === rangeSkinIds.LIKERT_SMILEYS_SCALE" class="lf-form-likert-smileys">
                        <div class="lf-form-smiley-wrapper">
                          <input class="lf-form-smiley-radio-choice" @click="updateSmiley('smiley1', stepItem.question)" v-validate="validateObject(stepItem.question, sindex)" type="radio" :name="questionFieldName(stepItem.question)" id="" :value="form['steps'][sindex]['questions'][stepItem.qindex]['value']">
                          <div
                            :class="stepItem.question.value === stepItem.question.rangeFields.veryUnsatisfied || stepItem.question.value === smileysText.VERY_UNSATISFIED  ? 'lf-form-active-class' : 'lf-form-smiley'"
                            style="width:50px; height:50px;"
                            v-html="getSvgIcon('material-icons-outlined ' + 'sentiment_very_dissatisfied')">
                          </div>
                          <div class="lf-form-smiley-text-wrapper">
                            <label class="lf-form-smiley-text " for="question-label">{{!stepItem.question.rangeFields.veryUnsatisfied ? smileysText.VERY_UNSATISFIED : stepItem.question.rangeFields.veryUnsatisfied}}</label>
                          </div>
                        </div>
                        <div class="lf-form-smiley-wrapper">
                          <input class="lf-form-smiley-radio-choice" @click="updateSmiley('smiley2', stepItem.question)" v-validate="validateObject(stepItem.question, sindex)" type="radio" :name="questionFieldName(stepItem.question)" id="" :value="form['steps'][sindex]['questions'][stepItem.qindex]['value']">
                          <div
                            :class="stepItem.question.value === stepItem.question.rangeFields.unsatisfied || stepItem.question.value === smileysText.UNSATISFIED ? 'lf-form-active-class' : 'lf-form-smiley'"
                            style="width:50px; height:50px"
                            v-html="getSvgIcon('material-icons ' + 'sentiment_very_dissatisfied')">
                          </div>
                          <div class="lf-form-smiley-text-wrapper">
                            <label class="lf-form-smiley-text" for="question-label">{{!stepItem.question.rangeFields.unsatisfied ? smileysText.UNSATISFIED : stepItem.question.rangeFields.unsatisfied}}</label>
                          </div>
                        </div>
                        <div class="lf-form-smiley-wrapper">
                          <input class="lf-form-smiley-radio-choice" @click="updateSmiley('smiley3', stepItem.question)" v-validate="validateObject(stepItem.question, sindex)" type="radio" :name="questionFieldName(stepItem.question)" id="" :value="form['steps'][sindex]['questions'][stepItem.qindex]['value']">
                          <div
                            :class="stepItem.question.value === stepItem.question.rangeFields.neutral || stepItem.question.value === smileysText.NEUTRAL ? 'lf-form-active-class' : 'lf-form-smiley'"
                            style="width:50px; height:50px"
                            v-html="getSvgIcon('material-icons-outlined ' + 'sentiment_neutral')">
                          </div>
                          <div class="lf-form-smiley-text-wrapper">
                            <label class="lf-form-smiley-text" for="question-label">{{!stepItem.question.rangeFields.neutral ? smileysText.NEUTRAL : stepItem.question.rangeFields.neutral}}</label>
                          </div>
                        </div>
                        <div class="lf-form-smiley-wrapper">
                          <input class="lf-form-smiley-radio-choice" @click="updateSmiley('smiley4', stepItem.question)" v-validate="validateObject(stepItem.question, sindex)" type="radio" :name="questionFieldName(stepItem.question)" id="" :value="form['steps'][sindex]['questions'][stepItem.qindex]['value']">
                          <div
                            :class="stepItem.question.value === stepItem.question.rangeFields.satisfied || stepItem.question.value === smileysText.SATISFIED ? 'lf-form-active-class' : 'lf-form-smiley'"
                            style="width:50px; height:50px"
                            v-html="getSvgIcon('material-icons-outlined ' + 'sentiment_satisfied_alt')">
                          </div>
                          <div class="lf-form-smiley-text-wrapper">
                            <label class="lf-form-smiley-text" for="question-label">{{!stepItem.question.rangeFields.satisfied ? smileysText.SATISFIED : stepItem.question.rangeFields.satisfied}}</label>
                          </div>
                        </div>
                        <div class="lf-form-smiley-wrapper">
                          <input  class="lf-form-smiley-radio-choice" @click="updateSmiley('smiley5', stepItem.question)" v-validate="validateObject(stepItem.question, sindex)" type="radio" :name="questionFieldName(stepItem.question)" id="" :value="form['steps'][sindex]['questions'][stepItem.qindex]['value']">
                          <div
                            :class="stepItem.question.value === stepItem.question.rangeFields.verySatisfied || stepItem.question.value === smileysText.VERY_SATISFIED ? 'lf-form-active-class' : 'lf-form-smiley'"
                            style="width:50px; height:50px"
                            v-html="getSvgIcon('material-icons ' + 'sentiment_very_satisfied')">
                          </div>
                          <div class="lf-form-smiley-text-wrapper">
                            <label class="lf-form-smiley-text" for="question-label">{{!stepItem.question.rangeFields.verySatisfied ? smileysText.VERY_SATISFIED : stepItem.question.rangeFields.verySatisfied}}</label>
                          </div>
                        </div>
                      </div>
                      <div class="lf-form__question__errors" v-if="currentStep === sindex && validation.showErrors">
                        <span v-if="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger" :style="warningColorStyle">This field is required</span>
                      </div>
                    </div>
                  </div>
                  <!-- DATE QUESTION -->
                  <div class="lf-form__question" v-if="stepItem.question.type === 'DATE'" :data-question-type="stepItem.question.type">
                    <div class="lf-form__question__inputfield">
                      <label :for="questionFieldName(stepItem.question)" class="active" v-if="!stepItem.question.hide_title && !theme.ui_elements.question.hide_question_labels"> {{stepItem.question.title}} <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span></label>
                      <p class="lf-form__question__description" v-if="stepItem.question.description" >{{ stepItem.question.description }} </p>
                      <div v-if="stepItem.question.skin.id === dateSkinIds.DATE_PICKER">
                        <ui-datepicker
                        @open="pickerOpened()"
                        @close="pickerClosed()"
                        v-validate="validateObject(stepItem.question, sindex)"
                        :data-vv-scope="formScope"
                        data-vv-value-path="value"
                        :name="questionFieldName(stepItem.question)"
                        v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                        :id="questionFieldName(stepItem.question)"
                        floating-label
                        :max-date ="stepItem.question.enableMinMax ? (stepItem.question.maxDate ? new Date(stepItem.question.maxDate) : null) : null"
                        :min-date="questionMinDate(stepItem.question)"
                        @focus="saveVisitInteractedAt()"/>
                      </div>
                      <div class="lf-form__question__date-skin" v-if="stepItem.question.skin.id === dateSkinIds.THREE_INPUT_BOXES">
                        <ui-textbox
                          v-validate="validateObject(stepItem.question, sindex, 'DD')"
                          :name="questionFieldName(stepItem.question)"
                          :data-vv-scope="formScope"
                          data-vv-value-path="value"
                          class="date-input-box"
                          :placeholder="!stepItem.question.placeholderDay ? threeInputBoxes.PLACEHOLDERDAY : stepItem.question.placeholderDay"
                          type="number"
                          autocomplete="off"
                          :value="dateQuestionSources[stepItem.question.id] ? dateQuestionSources[stepItem.question.id].selectedDay : '' "
                          @input="updateDateInputBoxes($event, stepItem.question, 'day')"
                          @focus="saveVisitInteractedAt()"
                        ></ui-textbox>
                        <ui-textbox
                          v-validate="validateObject(stepItem.question, sindex, 'MM')"
                          :name="questionFieldName(stepItem.question)"
                          :data-vv-scope="formScope"
                          data-vv-value-path="value"
                          class="date-input-box"
                          :placeholder="!stepItem.question.placeholderMonth ? threeInputBoxes.PLACEHOLDERMONTH : stepItem.question.placeholderMonth"
                          autocomplete="off"
                          :value="dateQuestionSources[stepItem.question.id] ? dateQuestionSources[stepItem.question.id].selectedMonth : '' "
                          @input="updateDateInputBoxes($event, stepItem.question, 'month')"
                          type="number"
                          @focus="saveVisitInteractedAt()"
                          :id="questionFieldName(stepItem.question)"
                        ></ui-textbox>
                        <ui-textbox
                          v-validate="validateObject(stepItem.question, sindex, 'YYYY')"
                          :name="questionFieldName(stepItem.question)"
                          :data-vv-scope="formScope"
                          data-vv-value-path="value"
                          class="date-input-box"
                          :placeholder="!stepItem.question.placeholderYear ? threeInputBoxes.PLACEHOLDERYEAR : stepItem.question.placeholderYear"
                          type="number"
                          autocomplete="off"
                          :value="dateQuestionSources[stepItem.question.id] ? dateQuestionSources[stepItem.question.id].selectedYear : '' "
                          @input="updateDateInputBoxes($event, stepItem.question, 'year')"
                          @focus="saveVisitInteractedAt()"
                          :id="questionFieldName(stepItem.question)"
                        ></ui-textbox>
                      </div>
                      <div v-if="stepItem.question.skin.id === dateSkinIds.ONE_INPUT_BOX" class="lf-form__question__date-skin">
                        <input
                          id="date-mask"
                          v-validate="validateObject(stepItem.question, sindex)"
                          :name="questionFieldName(stepItem.question)"
                          :data-vv-scope="formScope"
                          data-vv-value-path="value"
                          class="single-date-input__box"
                          v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                          data-inputmask-alias="datetime"
                          data-inputmask-inputformat="dd/mm/yyyy"
                          data-inputmask-clearmaskonlostfocus="false"
                          data-inputmask-placeholder="DD/MM/YYYY"
                          @focus="saveVisitInteractedAt()"
                        />
                      </div>
                       <div v-if="stepItem.question.skin.id === dateSkinIds.DROPDOWN" class="lf-form__question__date-skin">
                        <ui-select
                          v-validate="validateObject(stepItem.question, sindex)"
                          :name="questionFieldName(stepItem.question)"
                          :data-vv-scope="formScope"
                          data-vv-value-path="value"
                          :options=dateQuestionSources.days
                          :placeholder="!stepItem.question.placeholderDay ? dropdown.PLACEHOLDERDAY : stepItem.question.placeholderDay"
                          class="date-input-box"
                          @input="setDays"
                          @change="setDaysValue($event, stepItem.question)"
                          :value="dateQuestionSources[stepItem.question.id] ? dateQuestionSources[stepItem.question.id].selectedDay : '' "
                          @focus="saveVisitInteractedAt()"
                        ></ui-select>
                        <ui-select
                          v-validate="validateObject(stepItem.question, sindex)"
                          :name="questionFieldName(stepItem.question)"
                          :data-vv-scope="formScope"
                          data-vv-value-path="value"
                          :options=dateQuestionSources.months
                          :placeholder="!stepItem.question.placeholderMonth ? dropdown.PLACEHOLDERMONTH : stepItem.question.placeholderMonth"
                          class="date-input-box"
                          :value="dateQuestionSources[stepItem.question.id] ? dateQuestionSources[stepItem.question.id].selectedMonth : '' "
                          @change="setMonths($event, stepItem.question)"
                          @focus="saveVisitInteractedAt()"
                        ></ui-select>
                        <ui-select
                          v-validate="validateObject(stepItem.question, sindex)"
                          :name="questionFieldName(stepItem.question)"
                          :data-vv-scope="formScope"
                          data-vv-value-path="value"
                          :options=dateQuestionSources.years
                          :placeholder="!stepItem.question.placeholderYear ? dropdown.PLACEHOLDERYEAR : stepItem.question.placeholderYear"
                          class="date-input-box"
                          :value="dateQuestionSources[stepItem.question.id] ? dateQuestionSources[stepItem.question.id].selectedYear : '' "
                          @change="setYears($event, stepItem.question)"
                          @focus="saveVisitInteractedAt()"
                      ></ui-select>
                       </div>
                      <div class="lf-form__question__errors" v-if="currentStep === sindex && validation.showErrors">
                        <span  v-if="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger" :style="warningColorStyle">This field is required</span>
                        <span  v-else-if="checkErr(questionFieldName(stepItem.question), 'date')" class="is-danger" :style="warningColorStyle">{{checkErr(questionFieldName(stepItem.question), 'date')}}</span>
                        <span  v-show="checkErr(questionFieldName(stepItem.question), 'date_format')" class="is-danger" :style="warningColorStyle">The date must be valid and in the format dd/MM/yyyy</span>
                      </div>
                    </div>
                  </div>
                  <!-- SINGLE SELECT QUESTION -->
                  <div :class="'lf-form__question lf-form__single-select-question' + skinLayoutClass(stepItem.question.skin)" v-if="stepItem.question.type === 'SINGLE_CHOICE'" :data-question-type="stepItem.question.type">
                    <div class="lf-form__question__inputfield">
                      <label class="active" v-if="!stepItem.question.hide_title && !theme.ui_elements.question.hide_question_labels"> {{stepItem.question.title}}
                        <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span>
                      </label>
                      <p class="lf-form__question__description" v-if="stepItem.question.description">{{ stepItem.question.description }}</p>
                      <!-- button skin -->
                      <ul :class="'lf-form__radio-list' + alignmentClass(stepItem.question.skin)" v-if="!stepItem.question.skin || stepItem.question.skin.id === 'button'">
                        <li v-for="(choice, cindex) in stepItem.question.choices" :key="cindex">
                          <input v-validate="validateObject(stepItem.question, sindex)" :id="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex" :name="questionFieldName(stepItem.question)" type="radio" :value="choice" v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']" @focus="saveVisitInteractedAt()">
                          <label :for="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex" class="checkbox-label">{{choice.label}}</label>
                        </li>
                      </ul>
                       <!--- Line Radio Button--->
                       <ul :class="'lf-form__radio_outline-list' + alignmentClass(stepItem.question.skin)" v-if="!stepItem.question.skin || stepItem.question.skin.id === 'radio_outline'">
                        <li v-for="(choice, cindex) in stepItem.question.choices" :key="cindex" >
                           <input v-validate="validateObject(stepItem.question, sindex)" :id="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex" :name="questionFieldName(stepItem.question)" type="radio" :value="choice" v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']" @focus="saveVisitInteractedAt()">
                          <label :for="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex" >
                           <ui-radio
                            :class="{ 'ui-radio--width': form['steps'][sindex]['questions'][stepItem.qindex].skin.alignment === 'vertical_center' ||form['steps'][sindex]['questions'][stepItem.qindex].skin.alignment === 'vertical' }"
                            :data-vv-scope="formScope"
                            data-vv-value-path="value"
                            :value="form['steps'][sindex]['questions'][stepItem.qindex]['value'].id || ''"
                            @input="updateSingleChoiceRadioOutlineSkinValue($event, form['steps'][sindex]['questions'][stepItem.qindex])"
                            :true-value="choice.id"
                            @focus="saveVisitInteractedAt()">
                            {{ choice.label }}
                          </ui-radio>
                          </label>
                        </li>
                      </ul>
                      <!-- radio skin -->
                      <div :class="'lf-form__radio-options' + alignmentClass(stepItem.question.skin)" v-else-if="stepItem.question.skin.id === 'radio'">
                        <div class="lf-form__radio-options__item" v-for="(choice, cindex) in stepItem.question.choices" :key="cindex">
                          <ui-radio
                            v-validate="validateObject(stepItem.question, sindex)"
                            :data-vv-scope="formScope"
                            data-vv-value-path="value"
                            :id="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex"
                            :name="questionFieldName(stepItem.question)"
                            :value="form['steps'][sindex]['questions'][stepItem.qindex]['value'].id || ''"
                            @input="updateSingleChoiceRadioSkinValue($event, form['steps'][sindex]['questions'][stepItem.qindex])"
                            :true-value="choice.id"
                            @focus="saveVisitInteractedAt()">
                            {{ choice.label }}
                          </ui-radio>
                        </div>
                      </div>
                      <!-- dropdown skin -->
                      <div class="lf-form__dropdown-options" v-else-if="stepItem.question.skin.id === 'dropdown'">
                        <ui-select
                          v-validate="validateObject(stepItem.question, sindex)"
                          :data-vv-scope="formScope"
                          data-vv-value-path="value"
                          :id="'lf-form-question-' + stepItem.question.id"
                          :name="questionFieldName(stepItem.question)"
                          :placeholder="stepItem.question.placeholder"
                          :options="stepItem.question.choices"
                          :keys="{value: 'id', label: 'label'}"
                          v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                          @focus="saveVisitInteractedAt()">
                        </ui-select>
                      </div>
                      <!-- image skin -->
                      <div :class="'lf-form__image-options' + alignmentClass(stepItem.question.skin) + skinColumnClass(stepItem.question.skin)" v-else-if="stepItem.question.skin.id === 'image'">
                        <div class="lf-form__image-options__column"
                          v-for="(choice, cindex) in stepItem.question.choices"
                          :key="cindex">
                          <div
                            :class="{
                              'lf-form__image-options__item': true,
                              'lf-form__image-options__item--selected': form['steps'][sindex]['questions'][stepItem.qindex]['value'] &&  form['steps'][sindex]['questions'][stepItem.qindex]['value'].id === choice.id
                            }">
                            <div class="lf-form__image-options__item__image-wrapper">
                              <div class="lf-form__image-options__item__image" :style="'background-image: url(' + choice.image + ')'" v-if="choice.image">
                                <img :src="choice.image">
                              </div>
                              <input
                                v-validate="validateObject(stepItem.question, sindex)"
                                :id="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex"
                                :name="questionFieldName(stepItem.question)"
                                type="radio"
                                :value="choice"
                                v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                                @focus="saveVisitInteractedAt()">
                            </div>
                            <div class="lf-form__image-options__item__content-wrapper">
                              <span class="lf-form__image-options__item__label" v-if="choice.label">{{choice.label}}</span>
                              <span class="lf-form__image-options__item__desc" v-if="choice.description">{{choice.description}}</span>
                            </div>
                            <div class="lf-form__image-options__item__checked" v-html="form.svgIcons['fas fa-check-circle']['svg']"></div>
                          </div>
                        </div>
                      </div>
                      <!-- icon skin -->
                      <div :class="'lf-form__icon-options' + alignmentClass(stepItem.question.skin) + skinColumnClass(stepItem.question.skin)" v-else-if="stepItem.question.skin.id === 'icon'">
                        <div class="lf-form__icon-options__column"
                          v-for="(choice, cindex) in stepItem.question.choices"
                          :key="cindex">
                          <div
                            :class="{
                              'lf-form__icon-options__item': true,
                              'lf-form__icon-options__item--selected': form['steps'][sindex]['questions'][stepItem.qindex]['value'] &&  form['steps'][sindex]['questions'][stepItem.qindex]['value'].id === choice.id
                            }">
                            <div class="lf-form__icon-options__item__icon-wrapper">
                              <span :class="{'lf-form__icon-options__item__icon': true}" v-if="choice.icon" v-html="form.svgIcons[choice.icon]['svg']">
                              </span>
                              <input
                                v-validate="validateObject(stepItem.question, sindex)"
                                :id="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex"
                                :name="questionFieldName(stepItem.question)"
                                type="radio"
                                :value="choice"
                                v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                                @focus="saveVisitInteractedAt()">
                            </div>
                            <div class="lf-form__icon-options__item__content-wrapper">
                              <span class="lf-form__icon-options__item__label" v-if="choice.label">{{choice.label}}</span>
                              <span class="lf-form__icon-options__item__desc" v-if="choice.description">{{choice.description}}</span>
                            </div>
                            <div class="lf-form__icon-options__item__checked" v-html="form.svgIcons['fas fa-check-circle']['svg']"></div>
                          </div>
                        </div>
                      </div>
                      <div class="lf-form__question__errors" v-if="currentStep === sindex && validation.showErrors">
                        <span v-show="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger"
                        :style="warningColorStyle">This field is required</span>
                      </div>
                    </div>
                  </div>
                  <!-- MULTI SELECT QUESTION -->
                  <div :class="'lf-form__question lf-form__multi-select-question' + skinLayoutClass(stepItem.question.skin)" v-if="stepItem.question.type === 'MULTIPLE_CHOICE'" :data-question-type="stepItem.question.type">
                    <div class="lf-form__question__inputfield">
                      <label class="active" v-if="!stepItem.question.hide_title && !theme.ui_elements.question.hide_question_labels"> {{stepItem.question.title}}
                        <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span>
                      </label>
                      <p class="lf-form__question__description" v-if="stepItem.question.description">{{ stepItem.question.description }}</p>
                      <!-- button skin -->
                      <ul :class="'lf-form__radio-list' + alignmentClass(stepItem.question.skin)" v-if="!stepItem.question.skin || stepItem.question.skin.id === 'button'">
                        <li v-for="(choice, cindex) in stepItem.question.choices" :key="cindex">
                          <input v-validate="validateObject(stepItem.question, sindex)" type="checkbox" :value="choice" :name="questionFieldName(stepItem.question)" :id="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex" v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']" @focus="saveVisitInteractedAt()">
                          <label :for="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex" class="checkbox-label">{{choice.label}} </label>
                        </li>
                      </ul>
                      <!-- checkbox skin -->
                      <div :class="'lf-form__checkbox-options' + alignmentClass(stepItem.question.skin)" v-else-if="stepItem.question.skin.id === 'checkbox'">
                        <ui-checkbox-group
                          v-validate="validateObject(stepItem.question, sindex)"
                          :data-vv-scope="formScope"
                          :options="mapMultiSelectCheckboxOptions(stepItem.question.choices)"
                          data-vv-value-path="value"
                          :id="'lf-form-question-' + stepItem.question.id"
                          :name="questionFieldName(stepItem.question)"
                          v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                          :vertical="stepItem.question.skin.alignment === 'vertical'"
                          @focus="saveVisitInteractedAt()">
                        </ui-checkbox-group>
                      </div>
                      <!-- dropdown skin -->
                      <div class="lf-form__dropdown-options" v-else-if="stepItem.question.skin.id === 'dropdown'">
                        <ui-select
                          v-validate="validateObject(stepItem.question, sindex)"
                          :data-vv-scope="formScope"
                          data-vv-value-path="value"
                          :id="'lf-form-question-' + stepItem.question.id"
                          :name="questionFieldName(stepItem.question)"
                          :placeholder="stepItem.question.placeholder"
                          :options="stepItem.question.choices"
                          :keys="{value: 'id', label: 'label'}"
                          v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                          :multiple="true"
                          @focus="saveVisitInteractedAt()">
                        </ui-select>
                      </div>
                      <!-- image skin -->
                      <div :class="'lf-form__image-options' + alignmentClass(stepItem.question.skin) + skinColumnClass(stepItem.question.skin)" v-else-if="stepItem.question.skin.id === 'image'">
                        <div class="lf-form__image-options__column"
                          v-for="(choice, cindex) in stepItem.question.choices"
                          :key="cindex">
                          <div
                            :class="{
                              'lf-form__image-options__item': true,
                              'lf-form__image-options__item--selected': contains(form['steps'][sindex]['questions'][stepItem.qindex]['value'], {id: choice.id})
                            }">
                            <div class="lf-form__image-options__item__image-wrapper">
                              <div class="lf-form__image-options__item__image" :style="'background-image: url(' + choice.image + ')'" v-if="choice.image">
                                <img :src="choice.image">
                              </div>
                              <input
                                v-validate="validateObject(stepItem.question, sindex)"
                                :id="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex"
                                :name="questionFieldName(stepItem.question)"
                                type="checkbox"
                                :value="choice"
                                v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                                @focus="saveVisitInteractedAt()">
                            </div>
                            <div class="lf-form__image-options__item__content-wrapper">
                              <span class="lf-form__image-options__item__label" v-if="choice.label">{{choice.label}}</span>
                              <span class="lf-form__image-options__item__desc" v-if="choice.description">{{choice.description}}</span>
                            </div>
                            <div class="lf-form__image-options__item__checked" v-html="form.svgIcons['fas fa-check-circle']['svg']"></div>
                          </div>
                        </div>
                      </div>
                      <!-- icon skin -->
                      <div :class="'lf-form__icon-options' + alignmentClass(stepItem.question.skin) + skinColumnClass(stepItem.question.skin)" v-else-if="stepItem.question.skin.id === 'icon'">
                        <div class="lf-form__icon-options__column"
                          v-for="(choice, cindex) in stepItem.question.choices"
                          :key="cindex">
                          <div
                            :class="{
                              'lf-form__icon-options__item': true,
                              'lf-form__icon-options__item--selected': contains(form['steps'][sindex]['questions'][stepItem.qindex]['value'], {id: choice.id})
                            }">
                            <div class="lf-form__icon-options__item__icon-wrapper">
                              <span :class="{'lf-form__icon-options__item__icon': true}" v-if="choice.icon" v-html="form.svgIcons[choice.icon]['svg']"></span>
                              <input
                                v-validate="validateObject(stepItem.question, sindex)"
                                type="checkbox"
                                :value="choice"
                                :name="questionFieldName(stepItem.question)"
                                :id="'lf-form-question-' + stepItem.question.id + '-choice-' + cindex"
                                v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                                @focus="saveVisitInteractedAt()">
                            </div>
                            <div class="lf-form__icon-options__item__content-wrapper">
                              <span class="lf-form__icon-options__item__label" v-if="choice.label">{{choice.label}}</span>
                              <span class="lf-form__icon-options__item__desc" v-if="choice.description">{{choice.description}}</span>
                            </div>
                            <div class="lf-form__icon-options__item__checked" v-html="form.svgIcons['fas fa-check-circle']['svg']"></div>
                          </div>
                        </div>
                      </div>
                      <div class="lf-form__question__errors" v-if="currentStep === sindex && validation.showErrors">
                        <span  v-if="checkErr(questionFieldName(stepItem.question), 'required') && stepItem.question.required" class="is-danger"
                        :style="warningColorStyle">This field is required</span>
                        <div v-else-if="stepItem.question.enableMinMaxChoices && stepItem.question.required">
                          <span  v-show="checkErr(questionFieldName(stepItem.question) + '_between', 'between') && stepItem.question.required" class="is-danger" :style="warningColorStyle">
                            {{ (stepItem.question.minChoices > stepItem.question.value.length) ?  ('Please select at least ' + stepItem.question.minChoices + ' choices') : '' }}
                            {{ (stepItem.question.value.length > stepItem.question.maxChoices) && stepItem.question.maxChoices == 1 ? ('Please select only ' + stepItem.question.maxChoices + ' choice at maximum.') : '' }}
                            {{ (stepItem.question.value.length > stepItem.question.maxChoices) && stepItem.question.maxChoices > 1 ? ('Please select only ' + stepItem.question.maxChoices + ' choices at maximum.') : '' }}
                          </span>
                        </div>
                      </div>
                      <input v-if="stepItem.question.enableMinMaxChoices && stepItem.question.required" v-validate="betweenValidateString(stepItem.question, sindex, stepItem.question.minChoices, stepItem.question.maxChoices)" :name="questionFieldName(stepItem.question)+'_between'" type="hidden" :value="form['steps'][sindex]['questions'][stepItem.qindex]['value'].length || 0">
                    </div>
                  </div>
                  <!-- ADDRESS QUESTION -->
                  <div class="lf-form__question" v-if="stepItem.question.type === 'ADDRESS'" :data-question-type="stepItem.question.type">
                    <div class="lf-form__question__inputfield">
                      <label v-if="!stepItem.question.hide_title && !theme.ui_elements.question.hide_question_labels" :for="questionFieldName(stepItem.question)" class="active" > {{stepItem.question.title}}<span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span></label>
                      <p class="lf-form__question__description" v-if="stepItem.question.description" >{{ stepItem.question.description }}</p>
                      <div class="lf-form__question__fields" v-if="stepItem.question.skin.id === addressSkinIds.GOOGLE_AUTOCOMPLETE">
                        <div class="lf-form__question__field" v-if="stepItem.question.autocompleteMode === addressAutocompleteModesIds.SEARCH">
                          <ui-select
                            placeholder="Search..."
                            v-validate="validateObject(stepItem.question, sindex)"
                            :data-vv-scope="formScope"
                            data-vv-value-path="value"
                            data-vv-as="Address"
                            :name="questionFieldName(stepItem.question)"
                            :options="autocompleteAddressSearchOptions(stepItem.question)"
                            :hasSearch="true"
                            v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                            @query-change="(query) => autocompleteAddress(query, stepItem.question)"
                            @select="onAutocompleteAddressSelect($event, stepItem.question)"
                            @focus="saveVisitInteractedAt()"
                            :id="questionFieldName(stepItem.question)">
                          </ui-select>
                        </div>
                        <div class="lf-form__question__field" v-else>
                          <ui-textbox
                            placeholder="Enter Postcode"
                            v-validate="validateObject(stepItem.question, sindex)"
                            :data-vv-scope="formScope"
                            data-vv-value-path="value"
                            data-vv-as="Address"
                            :name="questionFieldName(stepItem.question)"
                            v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                            @input="autocompleteAddress($event, stepItem.question)"
                            @change="autocompleteAddress($event, stepItem.question)"
                            @focus="saveVisitInteractedAt()"
                            :id="questionFieldName(stepItem.question)">
                          </ui-textbox>
                          <ui-button buttonType="button" color="primary" @click="() => findAutocompleteAddress(stepItem.question)" :style="findAddressBtnStyle">
                            {{ findingAddress[stepItem.question.id] ? 'Finding...' : 'Find Address'}}
                          </ui-button>
                          <div
                            v-if="!showAddressFields(stepItem.question)"
                            :style="enterAddressManuallyTextStyle"
                            @click="() => enterAddressFieldsManually(stepItem.question)">Or enter your address manually</div>
                        </div>
                      </div>
                      <div class="lf-form__question__fields" v-if="showAddressFields(stepItem.question)">
                        <div class="lf-form__question__field" v-for="field in addressFieldsFilter(stepItem.question.fields)" :key="field.id" :data-field-id="field.id">
                          <ui-select
                            v-if="field.id === 'country'"
                            :label="field.label_value"
                            :placeholder="field.placeholder_value"
                            v-validate="validateObject(stepItem.question, sindex, field)"
                            :data-vv-scope="formScope"
                            data-vv-value-path="value"
                            :data-vv-as="field.label"
                            :name="addressFieldName(stepItem.question, field)"
                            :options="autocompleteCountryAddressField"
                            :hasSearch="true"
                            v-model="field.value"
                            @change="onCountryChange($event, stepItem.question)"
                            @focus="saveVisitInteractedAt()"
                            :id="addressFieldName(stepItem.question, field)">
                            <slot name="help">
                              <input :id="addressFieldName(stepItem.question, field) + '__hidden'">
                            </slot>
                          </ui-select>
                          <ui-select
                            v-else-if="field.id === 'state' && stepItem.question.fields.country.enabled"
                            :label="field.label_value"
                            :placeholder="field.placeholder_value"
                            v-validate="validateObject(stepItem.question, sindex, field)"
                            :data-vv-scope="formScope"
                            data-vv-value-path="value"
                            :data-vv-as="field.label"
                            :name="addressFieldName(stepItem.question, field)"
                            :options="autocompleteStateAddressField(stepItem.question.fields.country.value)"
                            :hasSearch="true"
                            v-model="field.value"
                            :id="addressFieldName(stepItem.question, field)"
                            @focus="saveVisitInteractedAt()"/>
                          <ui-textbox
                            v-else-if="field.id === 'state' && ! stepItem.question.fields.country.enabled"
                            :label="field.label_value"
                            :placeholder="field.placeholder_value"
                            v-validate="validateObject(stepItem.question, sindex, field)"
                            :data-vv-scope="formScope"
                            data-vv-value-path="value"
                            :data-vv-as="field.label"
                            :name="addressFieldName(stepItem.question, field)"
                            v-model="field.value"
                            :id="addressFieldName(stepItem.question, field)"
                            @focus="saveVisitInteractedAt()"/>
                          <ui-textbox
                            v-else
                            :label="field.label_value"
                            :placeholder="field.placeholder_value"
                            v-validate="validateObject(stepItem.question, sindex, field)"
                            :data-vv-scope="formScope"
                            data-vv-value-path="value"
                            :data-vv-as="field.label"
                            :name="addressFieldName(stepItem.question, field)"
                            v-model.trim="field.value"
                            :id="addressFieldName(stepItem.question, field)"
                            @focus="saveVisitInteractedAt()"/>
                        </div>
                      </div>
                      <div
                        class="lf-form__question__error"
                        v-if="(
                            stepItem.question.autocompleteMode === addressAutocompleteModesIds.MANUAL &&
                            checkErr(questionFieldName(stepItem.question), 'expression') &&
                            currentStep === sindex &&
                            validation.showErrors
                          )"
                        :style="warningColorStyle">
                          Please click Find Address
                      </div>
                      <div class="lf-form__question__error"
                           v-if="(
                             currentStep === sindex &&
                             validation.showErrors &&
                             stepItem.question.skin.id !== addressSkinIds.DEFAULT &&
                             (
                               (
                                stepItem.question.autocompleteMode === addressAutocompleteModesIds.SEARCH &&
                                !stepItem.question.autocompleteFieldsEdit
                               ) ||
                               (
                                 stepItem.question.autocompleteMode === addressAutocompleteModesIds.MANUAL &&
                                 !addressFieldsVisibilty[stepItem.question.id]
                               )
                             )
                           )">
                        <span
                          v-show="checkErr(questionFieldName(stepItem.question), 'required')"
                          class="is-danger"
                          :style="warningColorStyle">
                          {{ checkErr(questionFieldName(stepItem.question), 'required') }}
                        </span>
                      </div>
                      <div class="lf-form__question__errors" v-if="currentStep === sindex && validation.showErrors">
                        <div class="lf-form__question__error" v-for="field in stepItem.question.fields" :key="field.id">
                          <span
                            v-show="checkErr(addressFieldName(stepItem.question, field), 'required')"
                            class="is-danger"
                            :style="warningColorStyle">
                              {{ checkErr(addressFieldName(stepItem.question, field), 'required') }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- GDPR QUESTION -->
                  <div class="lf-form__question lf-form__gdpr-question" v-if="stepItem.question.type === 'GDPR' && stepItem.question.enabled" :data-question-type="stepItem.question.type">
                    <div class="lf-form__question__inputfield">
                      <label class="active" v-if="!stepItem.question.hide_title && !theme.ui_elements.question.hide_question_labels"> {{stepItem.question.title}} <span v-if="stepItem.question.required && theme.ui_elements.question.asterisk_for_required">*</span></label>
                      <p class="lf-form__question__description" v-if="stepItem.question.description"  v-html="stepItem.question.description" :style="questionDescriptionStyle">{{ stepItem.question.description }}</p>
                      <div class="default-radio-list" >
                        <lg-ui-checkbox-group
                          :name="questionFieldName(stepItem.question)"
                          :data-vv-scope="formScope"
                          data-vv-value-path="value"
                          v-validate="validateObject(stepItem.question, sindex)"
                          :options="stepItem.question.options.choices"
                          v-model="form['steps'][sindex]['questions'][stepItem.qindex]['value']"
                          vertical @focus="saveVisitInteractedAt()"></lg-ui-checkbox-group>
                      </div>
                      <div class="lf-form__question__errors" v-if="currentStep === sindex && validation.showErrors">
                        <span v-if="checkErr(questionFieldName(stepItem.question), 'required')" class="is-danger" :style="warningColorStyle">This field is required</span>
                        <span v-else-if="checkErr(questionFieldName(stepItem.question), 'gdpr')" class="is-danger" :style="warningColorStyle">{{checkErr(questionFieldName(stepItem.question), 'gdpr')}}</span>
                      </div>
                    </div>
                    <p class="lf-form__gdpr-terms" :style="{color: theme.general.colors.text_color}"  v-html="stepItem.question.legalText" >{{ stepItem.question.legalText }}</p>
                  </div>
                </div><!-- question -->
              </div>
          </div>
        </div>
        <!-- FORM SUMMARY -->
        <div :style="summarycss" class="lf-form__summary" v-if="currentStep === stepCount-1 && form.formSetting.steps_summary">
          <h5 class="lf-center-align">Please review your details then submit.</h5>
          <div class="lf-form__step-summary" v-for="(step, sindex) in summarySteps" :key="sindex">
            <div class="lf-form__question-summary__container" v-for="(question, qindex) in step.questions" :key="qindex">
              <div class="lf-form__question-summary" v-if="question.type === 'GDPR' ? question.enabled : true">
                <h6 class="titleStyle">{{question.title}}</h6>
                <div>
                  <span v-if="question.type === 'PARAGRAPH_TEXT'" v-html="nl2Br(questionSummary(question))"></span>
                  <span v-else-if="question.type === 'ADDRESS'">
                    <span v-for="field in question.fields" :key="field.id">
                      <span v-if="field.enabled"><b>{{ field.label }}:</b> {{ field.value }}</span>
                    </span>
                  </span>
                  <span v-else>{{ questionSummary(question) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-if="currentStep === 0 && ( form.formSetting.steps_summary || stepCount > 1) && partialLead"  class="consent-text">
          <p class="informed-consent" v-if="consentType === 'informed'">  By clicking to the “Next” step you agree to storing any information shared in this form.</p>
         <ui-checkbox  v-validate="{consent: !consentCheckbox}" @change="isConsentChecked($event)" v-if="consentType === 'expressed'" class="consent-text" :trueValue="true" v-model="consentChecked"  name="consent">I agree to storing any information I share in this form. </ui-checkbox>
         <div class="lf-form__question__errors" v-if="validation.showErrors">
            <span  v-show="checkErr('consent', 'consent')" class="is-danger" :style="warningColorStyle">The Consent field is required</span>
         </div>
        </div>
         <!-- FORM STEP NAVIGATION -->
        <div class="lf-form__step-nav">
          <div :style="'width:' + theme.ui_elements.step_navigation.next_button.width + '%'" class="lf-form__first-step-nav" v-if="currentStep === 0 && ( form.formSetting.steps_summary || stepCount > 1)">
            <ui-button
              @click="goNextStep"
              type="primary"
              name="action"
              color="primary"
              :loading="formSending"
              :style="nextButtonStyle"
              :class="{
                  'lf-shadow': getNextStep === -1 ? theme.ui_elements.step_navigation.submit_button.shadow: theme.ui_elements.step_navigation.next_button.shadow,
                  'lf-form__submit-step-btn': getNextStep === -1,
                  'lf-form__next-step-btn': getNextStep !== -1,
                  'fade-enter-active': theme.general.dynamic_fadein
              }"
              :disabled="!isValidStep">
                <span v-if="theme.ui_elements.step_navigation.next_button.icon && this.continueBtnText">{{ continueBtnText }} &nbsp; &nbsp;</span>
                <span v-else>{{ continueBtnText }}</span>
                <div
                  class="lf-form__submit-step-btn__icon"
                  v-html="getSvgIcon('material-icons ' + theme.ui_elements.step_navigation.submit_button.icon)"
                  v-if="getNextStep === -1"></div>
                <div
                  class="lf-form__next-step-btn__icon"
                  v-html="getSvgIcon('material-icons ' + theme.ui_elements.step_navigation.next_button.icon)"
                  v-else></div>
            </ui-button>
          </div>

          <div :class="{'lf-form__mid-step-nav': true, 'autonavigable': isStepAutoNavigable}" v-if="currentStep > 0 && currentStep < stepCount-1">
            <div class="back_button_wrapper">
              <div :style="'width:' + theme.ui_elements.step_navigation.back_button.width+ '%;' "  v-if="!theme.ui_elements.step_navigation.back_button.hide">
                  <ui-button
                    buttonType="button"
                    @click="goPrevStep"
                    type="primary"
                    name="action"
                    class="lf-form__prev-step-btn"
                    color="default"
                    :style="backButtonStyle"
                    :class="{'lf-shadow': theme.ui_elements.step_navigation.back_button.shadow,
                        'fade-enter-active': theme.general.dynamic_fadein
                    }">
                      <div
                        class="lf-form__prev-step-btn__icon"
                        v-html="getSvgIcon('material-icons ' + theme.ui_elements.step_navigation.back_button.icon)"></div>
                      &nbsp;{{backBtnText}}
                  </ui-button>
              </div>
            </div>
            <div class="continue_button_wrapper" v-if="continueButtonShow">
              <div :style="'width:100%;display:flex; justify-content:' + this.theme.ui_elements.step_navigation.next_button.alignment">
                <ui-button
                  @click="goNextStep"
                  type="primary"
                  name="action"
                  :class="{
                      'lf-shadow': getNextStep === -1 ? theme.ui_elements.step_navigation.submit_button.shadow: theme.ui_elements.step_navigation.next_button.shadow,
                      'lf-form__submit-step-btn': getNextStep === -1,
                      'lf-form__next-step-btn': getNextStep !== -1,
                      'fade-enter-active': theme.general.dynamic_fadein
                  }"
                  color="primary"
                  :loading="formSending"
                  :style="nextButtonStyle"
                  :disabled="!isValidStep">
                    <span v-if="theme.ui_elements.step_navigation.next_button.icon && this.continueBtnText">{{ continueBtnText }} &nbsp;</span>
                    <span v-else>{{ continueBtnText }}</span>
                    <!-- {{ continueBtnText }}&nbsp; -->
                    <div
                      class="lf-form__submit-step-btn__icon"
                      v-html="getSvgIcon('material-icons ' + theme.ui_elements.step_navigation.submit_button.icon)"
                      v-if="getNextStep === -1"></div>
                    <div
                      class="lf-form__next-step-btn__icon"
                      v-html="getSvgIcon('material-icons ' + theme.ui_elements.step_navigation.next_button.icon)"
                      v-else></div>
                </ui-button>
              </div>
            </div>
          </div>
          <div class="lf-form__last-step-nav center-align" v-if="currentStep === stepCount-1">
            <div  class="lf-form__mid-step-nav">
              <div class="back_button_wrapper" v-if="stepHistory.length > 0 && !theme.ui_elements.step_navigation.back_button.hide">
                <div :style="'width:' + theme.ui_elements.step_navigation.back_button.width+ '%;' "  >
                  <ui-button
                    buttonType="button"
                    @click="goPrevStep"
                    type="primary"
                    name="action"
                    class="lf-form__prev-step-btn"
                    color="default"
                    :style="backButtonStyle"
                    :class="{'lf-shadow': theme.ui_elements.step_navigation.back_button.shadow,
                        'fade-enter-active': theme.general.dynamic_fadein
                    }">
                      <div
                        class="lf-form__prev-step-btn__icon"
                        v-html="getSvgIcon('material-icons ' + theme.ui_elements.step_navigation.back_button.icon)"></div>
                   &nbsp;{{backBtnText}}
                  </ui-button>
                </div>
              </div>
              <div class="submit_button_wrapper" :class="{'sbmt_width': !stepHistory.length}">
                <div :style="'width:' + theme.ui_elements.step_navigation.submit_button.width + '%'" >
                  <ui-button
                    @click="goNextStep"
                    type="primary"
                    name="action"
                    color="primary"
                    :loading="formSending"
                    :style="submitButtonStyle"
                    :class="{
                        'lf-shadow': getNextStep === -1 ? theme.ui_elements.step_navigation.submit_button.shadow: theme.ui_elements.step_navigation.next_button.shadow,
                        'lf-form__submit-step-btn': getNextStep === -1,
                        'lf-form__next-step-btn': getNextStep !== -1,
                        'fade-enter-active': theme.general.dynamic_fadein
                    }"
                    :disabled="!isValidStep">
                    {{ continueBtnText }}&nbsp;
                      <div
                        class="lf-form__submit-step-btn__icon"
                        v-html="getSvgIcon('material-icons ' + theme.ui_elements.step_navigation.submit_button.icon)"
                        v-if="getNextStep === -1"></div>
                      <div
                        class="lf-form__next-step-btn__icon"
                        v-html="getSvgIcon('material-icons ' + theme.ui_elements.step_navigation.next_button.icon)"
                        v-else></div>
                  </ui-button>
                </div>
              </div>
            </div>
            <p class="lf-red-text lf-center-align" v-if="responseErrMsg" :style="theme.general.colors.warning_color">{{responseErrMsg}}</p>
          </div>
        </div>
        <!-- FORM FOOTER TEXT -->
        <div class="lf-form__footer" v-if="isFooterVisible">
          <div class="lf-form__footer__msg">
            <div class="ql-editor" v-html="footerText"></div>
          </div>
        </div>
        <div :id="'grecaptcha-' + formId"></div>
      </form>
      <!-- FORM THANKYOU MESSAGE -->
      <div class="lf-form__thankyou" v-else>
        <div class="lf-form__thankyou__msg">
          <div class="ql-editor" v-html="parsedMessage"></div>
        </div>
      </div>
      <div v-if="theme.ui_elements.step_progress.progressPosition === progressPositionIds.BOTTOM" class="lf-form__progress"  :style="progressStyle">
        <div v-if="theme.ui_elements.step_progress.showProgress" role="progressbar progress-striped"  class="progress-bar" :style="progressStep"></div>
      </div>
      <!-- FORM BRANDING -->
      <div :class="{'lf-form__branding': true, 'lf-hidden': !form.branding.show}">
        {{form.branding.prefix}} <a :href="form.branding.url" target="_blank">{{form.branding.title}}</a>
      </div>
    </div>
  </div>
</template>

<script>
/* eslint-disable no-eval */
import Vue from 'vue'
import _ from 'lodash'
import formSubmitActions from '../form-submit-actions'
import intlTelInput from 'intl-tel-input'
import UiCheckboxGroup from './ui/UiCheckboxGroup'
import UiSlider from 'keen-ui/src/UiSlider.vue';
import UiCheckbox from 'keen-ui/src/UiCheckbox.vue';
import { Validator } from 'vee-validate'
import errorTypes from '../error-types'
import smileysText from '../smileysText'
import trackingEvents from '../tracking-events'
import { addressSkinIds, addressAutocompleteModesIds, dateSkinIds, rangeSkinIds } from '../questionSkins'
import {threeInputBoxes, dropdown} from '../dateQuestionPlaceholder'
import {progressPositionIds} from '../progressPositions'
import Inputmask from "inputmask";

Validator.extend("numberLimit", {
  getMessage(field, args) {
    if (args[0].type === 'NUMBER') {
      return `Please enter a number between ${args[0].minNumber} and ${args[0].maxNumber}`
    }
  },
  validate(value, args) {
    if (args[0].type === 'NUMBER') {
      if (value < args[0].minNumber || value > args[0].maxNumber) {
        return false
      }
    }
    return true
  }
})

Validator.extend('consent', {
  getMessage(field) {
    return 'The consent field is required'
  },
  validate(value, args) {
    if (value) {
      return true
    }
    return false
  }
})

Validator.extend("validateEmailDomain", {
  getMessage(field, args) {
    if (args[0].restrictEmail === 'true') {
      return 'Emails from this domain are not allowed'
    }
  },
  validate(value, args) {
    let newValue = value.split('@')
    for (let item of args[0].restrictEmailFields) {
      if (newValue[1].toLowerCase() === item.email && item.allow === '0') {
        return false
      }
    }
    return true
  }
})

Validator.extend("date", {
  getMessage(field, args) {
    if (args[0] === "DD") {
      return "The Day field must be valid  (1 to 31).";
    } else if (args[0] === "MM") {
      return "The Month field must be valid (1 to 12).";
    } else if (args[0] === "YYYY") {
      return "The Year must be valid and contain exactly 4 digits.";
    }
    return "This field is required";
  },
  validate(value, args) {
    if (args[0] === "DD") {
      if (value < 1 || value > 31 || value.length > 2 || value.includes('-')) {
        return false;
      }
    } else if (args[0] === "MM") {
      if (value < 1 || value > 12 || value.length > 2 || value.includes('-')) {
        return false;
      }
    } else if (args[0] === "YYYY") {
      if (value.length < 4 || value.length > 4 || value.includes('-')) {
        return false;
      }
    }
    return true;
  }
})

Validator.extend('gdpr', {
  getMessage(field, args) {
    if (!args[0]) {
      return 'This field is required'
    }

    const choices = args[0].options.choices
    const value = args[0].value
    let message = []

    let index = 0

    for (let choice of choices) {
      if (choice.required && !_.find(value, { id: choice.id })) {
        message.push('option ' + (index + 1))
      }

      index++
    }

    if (choices.length === 1 & message.length > 0) {
      message = 'This field is required'
    } else {
      message = message.join(', ') + (message.length > 1 ? ' are' : ' is') + '  required'
    }

    return message
  },
  validate(value, args) {
    if (!args[0]) {
      return false
    }

    const choices = args[0].options.choices

    if (!value) {
      return false
    }

    for (let choice of choices) {
      if (choice.required && !_.find(value, { id: choice.id })) {
        return false
      }
    }
    return true
  }
})

Validator.extend('expression', {
  getMessage: function () {
    return 'Invalid expression'
  },
  validate: function (value, args) {
    return args ? args.value === true : true
  }
})

export default {
  props : {
    formKey : {
      type: String,
      default: 'LEADGEN_FORM_KEY'
    },
    formVariantId: {
      type: Number,
      default: null
    }
  },
  components: {
    'lg-ui-checkbox-group': UiCheckboxGroup,
    'ui-slider': UiSlider,
    'ui-checkbox': UiCheckbox
  },
  data : function() {
    return {
      consentCheckbox: false,
      consentType: null,
      partialLead: false,
      consentChecked: false,
      lead_id: null,
      overflowProperty: 'hidden',
      selectedArr: [],
      valueMin: 0,
      valueMax: 100,
      contactStates: [],
      isEnable: null,
      monthIndex: null,
      fetchingState: false,
      loading: true,
      errorMessage: '',
      form: {
        formSetting: {
        }
      },
      currentStep: 0,
      formScope: 'leadgen-form',
      siteKey: 'LEADGEN_FORM_RECAPTCHA_SITEKEY',
      recaptchaWidgetId: null,
      recaptchaResponse: null,
      extracss: '',
      summarycss: '',
      formSent: false,
      formSending: false,
      responseErrMsg: '',
      formVisitId: -1,
      stepHistory: [],
      fId: '',
      vId: '',
      customStyle: '',
      currentDate: new Date(),
      theme: '',
      themeLoaded: false,
      hiddenFields: [],
      formGeolocationForbidden: false,
      getNextStep: -1,
      itiInstances: {},
      postMessage: {
        prefix: 'lf__',
        parentOrigin: '',
        iframeViewportTop: 0,
        height: {
          value: -1,
          sent: false
        }
      },
      validation: {
        showErrors: false
      },
      isInteracted: false,
      savingVisitInteraction: false,
      autocompleteAddressResults: {},
      addressFieldsVisibilty: {},
      findingAddress: {},
      dateQuestionSources: {
        years:[],
        days:[],
        months:['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October','November', 'December']
      },
      ga4: {
        measurementId: ''
      }
    }
  },
  mounted : function() {
    if (this.insideIframe) {
      window.addEventListener('message', this.receivePostMessage, false)
    } else {
      this.fetchFormState()
    }
    this.setYears()
  },
  methods : {
    initializeComponent: function () {
      this.fetchingState = false
      this.loading = true
      this.errorMessage = ''
      this.form = {formSetting: {}}
      this.currentStep = 0
      this.formScope = 'leadgen-form'
      this.siteKey = 'LEADGEN_FORM_RECAPTCHA_SITEKEY'
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
      this.currentDate = new Date()
      this.theme = ''
      this.themeLoaded = false
      this.hiddenFields = []
      this.formGeolocationForbidden = false
      this.getNextStep = -1
      this.itiInstances = {}
      this.postMessage = {
        prefix: 'lf__',
        parentOrigin: '',
        parentUrl: '',
        iframeViewportTop: 0,
        height: {
          value: -1,
          sent: false
        }
      }
      this.validation = {
        showErrors: false
      }
      this.isInteracted = false
      this.savingVisitInteraction = false
      this.autocompleteAddressResults = {}
      this.addressFieldsVisibilty = {}
      this.findingAddress = {}
    },
    updateSmiley: function (smiley, question) {
      if (smiley === 'smiley1') {
        question.value = !question.rangeFields.veryUnsatisfied ? smileysText.VERY_UNSATISFIED : question.rangeFields.veryUnsatisfied
      } else if (smiley === 'smiley2') {
        question.value = !question.rangeFields.unsatisfied ? smileysText.UNSATISFIED : question.rangeFields.unsatisfied
      } else if (smiley === 'smiley3') {
        question.value = !question.rangeFields.neutral ? smileysText.NEUTRAL : question.rangeFields.neutral
      } else if (smiley === 'smiley4') {
        question.value = !question.rangeFields.satisfied ? smileysText.SATISFIED : question.rangeFields.satisfied
      } else if (smiley === 'smiley5') {
        question.value = !question.rangeFields.verySatisfied ? smileysText.VERY_SATISFIED : question.rangeFields.verySatisfied
      }
    },
     setInputValue: function (e, question, direction) {
      let inputLeft = document.querySelector(`#input-left-${question.id}`)
      let inputRight = document.querySelector(`#input-right-${question.id}`)
      let thumb = document.querySelector(`#thumb-${direction}-${question.id}`);
      let range = document.querySelector(`#slider-range-${question.id}`);
      if (direction === 'left') {
        e.target.value = Math.min(parseInt(e.target.value), parseInt(inputRight.value) - 1);
        if(isNaN(e.target.value))
        {
          e.target.value = ""
        }
        let percent = ((e.target.value - question.rangeFields.minScaleValue) / (question.rangeFields.maxScaleValue - question.rangeFields.minScaleValue)) * 100;
        if (e.target.value < parseInt(question.rangeFields.minScaleValue))
        {
          percent = 0 + "%"
        }
        thumb.style.left = percent + "%";
        range.style.left = percent + "%";
        question.rangeFields.valueMin = e.target.value
      }else if (direction === 'right') {
         e.target.value = Math.max(parseInt(e.target.value), parseInt(inputLeft.value) + 1);
         if(isNaN(e.target.value))
        {
          e.target.value = ""
        }
        let percent = ((e.target.value - question.rangeFields.minScaleValue) / (question.rangeFields.maxScaleValue - question.rangeFields.minScaleValue)) * 100;
          if(parseInt(question.rangeFields.valueMax) <= parseInt(question.rangeFields.valueMin))
        {
           e.target.value =  question.rangeFields.valueMax
        }
        else if(parseInt(question.rangeFields.valueMax) >  parseInt(question.rangeFields.maxScaleValue))
          {
            let diff =  parseInt(question.rangeFields.valueMax) - parseInt(question.rangeFields.maxScaleValue)
            let newValue = parseInt(question.rangeFields.valueMax) - diff
            e.target.value =  newValue
          }
        thumb.style.right = (100 - percent) + "%";
        range.style.right = (100 - percent) + "%";
        question.rangeFields.valueMax = e.target.value
      }
    },
    isConsentChecked: function (e) {
      this.consentCheckbox = e
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
        case this.postMessage.prefix + 'parent_url':
          this.postMessage.parentUrl = event.data.url
          this.fetchFormState()
          break
        case this.postMessage.prefix + 'parent_title':
          this.postMessage.parentTitle = event.data.title
          break
        case this.postMessage.prefix + 'iframe_viewport_top':
          this.postMessage.iframeViewportTop = event.data.top
          break
        case this.postMessage.prefix + 'parent_font' :
          this.postMessage.parentFont = event.data.family
          break
      }
    },
    fireTrackingEvent(eventName) {
      const event = _.find(this.form.formTrackingEvents, {event: eventName})

      if (!event || event.configured !== 1 || event.active === 0) {
        return;
      }

      const el = document.createElement('script');
      el.type = 'text/javascript';
      el.id = `lf-script-${this.formKey}-${eventName.toLowerCase()}`

      const removeEl = document.querySelector(`#${el.id}`);
      if (removeEl) {
        removeEl.remove()
      }

      try {
        el.appendChild(document.createTextNode(event.script));
        document.body.appendChild(el);
      } catch (e) {
        el.text = event.script;
        document.body.appendChild(el);
      }
    },
    initializeDateMask: function () {
      Inputmask().mask(document.querySelectorAll('#date-mask'))
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
    postFormDataUrlPostMessage: function (data, url, method) {
      let actionData = {
        action: this.postMessage.prefix + 'post_form_data_url',
        formKey: this.formKey,
        form: {
          method: method || 'post',
          url: url,
          data: data
        }
      }
      window.parent.postMessage(actionData, this.postMessage.parentOrigin || '*')
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
          } else if (question.type === 'DATE') {
            if (typeof value === 'string') {
              value = new Date(value)
            }

            if (value instanceof Date) {
              value = `${value.getFullYear()}-${value.getMonth() + 1}-${value.getDate()}`
            }
          }

          data.push({
            name: name,
            value: value
          })
        }
      }

      for (let hiddenField of this.hiddenFields) {
        data.push({
          name: hiddenField.name,
          value: hiddenField.default_value
        })
      }
      return data
    },
    prepareTheme: function () {
      this.fontFamily()
      this.inputStyle()
      this.questionTitleStyle()
      this.questionScaleStyle()
      this.questionDescriptionStyle()
      this.reviewSummaryStyle()
      this.bodyStyle()
      this.gdprStyle()
      this.progressStyle()
      this.sliderScaleStyle()
      this.buttonStyles()
      this.consentStyle()
    },
    fontFamily: function () {
      let fonts = {}

      for (const googleFontKey in this.form.googleFonts) {
        const googleFontValue = this.form.googleFonts[googleFontKey]

        if (
          googleFontValue.label === this.theme.general.font.family ||
          googleFontValue.label === this.theme.typography.question_description.font.family ||
          googleFontValue.label === this.theme.typography.question_title.font.family ||
          googleFontValue.label === this.theme.ui_elements.step_navigation.back_button.font.family ||
          googleFontValue.label === this.theme.ui_elements.step_navigation.next_button.font.family ||
          googleFontValue.label === this.theme.ui_elements.step_navigation.submit_button.font.family ||
          googleFontValue.label === this.theme.typography.input_box.font.family ||
          googleFontValue.label === this.theme.typography.text_box.family ||
          googleFontValue.label === this.theme.ui_elements.choice.image_icon_skin.title_font.family ||
          googleFontValue.label === this.theme.ui_elements.choice.image_icon_skin.description_font.family
        ) {
          fonts[googleFontKey] = googleFontValue
        }
      }
       if (this.theme.general.inherit_toggle !== true) {

        let fontsLink = 'https://fonts.googleapis.com/css?family='
        let fontStack = []

        for (let i in fonts) {
          fontStack.push(fonts[i].label.replace(/ /g, '+') + ':' + fonts[i].weights.join(','))
        }

        fontsLink += fontStack.join('|')
        fontsLink += '&display=swap'

        var link = document.createElement('link')
        link.href = fontsLink
        link.rel = 'stylesheet'
        document.head.appendChild(link)
      }
    },
    inputStyle: function () {
      // intl-tel-input
      this.customStyle += `
        .lf-form .intl-tel-input.allow-dropdown .flag-container .selected-flag,
        .lf-form .intl-tel-input.allow-dropdown .flag-container:hover .selected-flag
        .lf-form .iti--allow-dropdown .iti__flag-container:hover .iti__selected-flag,
        .lf-form .iti--allow-dropdown .iti__flag-container .iti__selected-flag {
          background: rgba(0,0,0,0);
        }
      `

      this.customStyle += `
        .lf-form__question[data-question-type="PHONE_NUMBER"] .intl-tel-input, .iti {
          height: ${this.theme.typography.input_box.font.height}px;
          width: 100%;
        }
      `

      this.customStyle += `
        .lf-form__question[data-question-type="ADDRESS"] div[data-field-id="country"] .intl-tel-input, .iti {
          height: 42px;
        }
        .lf-form__question[data-question-type="ADDRESS"] div[data-field-id="country"] .ui-select__display-value {
          padding-left: 45px;
        }
      `

      // Input Box
      this.customStyle += '.lf-form .ui-textbox .ui-textbox__content .ui-textbox__label .ui-textbox__input{' + (this.theme.typography.input_box.border.skin === 'all' ? 'border' : 'border-bottom') + ':' + this.theme.typography.input_box.border.width + 'px ' + ' ' + this.theme.typography.input_box.border.style + ' ' + this.theme.typography.input_box.border.color + '!important; box-shadow:' + 'none' + ';color:' + this.theme.typography.input_box.font.color + '!important; text-indent:' + this.theme.typography.input_box.font.text_intent + 'px !important;' + ';font-family:' + this.filterFontFamily(this.theme.typography.input_box.font.family) + '!important; font-size:' + this.theme.typography.input_box.font.font_size + 'px !important;' + ';background-color:' + this.theme.typography.input_box.font.background_color + ';border-radius:' + this.theme.typography.input_box.radius + 'px !important' +
      ';height:' + this.theme.typography.input_box.font.height + 'px !important;' + 'margin-bottom:' + this.theme.typography.input_box.font.spacing + 'px !important;' +  'text-align:' + this.theme.typography.input_box.text_align + '!important'+'}'
      this.customStyle += '.lf-form__question[data-question-type="PHONE_NUMBER"] .intl-tel-input, .iti {margin-bottom:' + this.theme.typography.input_box.font.spacing + 'px !important;' + '}'
      this.customStyle += `.lf-form .single-date-input__box {
        text-align: ${this.theme.typography.input_box.text_align}!important;
        font-family: ${this.filterFontFamily(this.theme.typography.input_box.font.family)};
        font-size: ${this.theme.typography.input_box.font.font_size}px;
        text-indent: ${this.theme.typography.input_box.font.text_intent}px;
        color: ${this.theme.typography.input_box.font.color}!important;
        background-color: ${this.theme.typography.input_box.font.background_color};
        height: ${this.theme.typography.input_box.font.height}px !important;
        margin-bottom: ${this.theme.typography.input_box.font.spacing}px !important;
        box-shadow: none;
        border-radius: ${this.theme.typography.input_box.radius}px !important;
        ${this.theme.typography.input_box.border.skin === 'all' ? 'border' : 'border-bottom'}: ${this.theme.typography.input_box.border.width}px ${this.theme.typography.input_box.border.style} ${this.theme.typography.input_box.border.color}!important;
      }`;

       // Remove default margin bottom of inputs
      this.customStyle += '.lf-form .ui-textbox { margin-bottom: 0px !important;} '
      //Input Box Placeholder Color
      this.customStyle += '.lf-form .ui-textbox .ui-textbox__content .ui-textbox__label .ui-textbox__input::placeholder, .ui-textbox__textarea::placeholder { color:' + this.theme.typography.input_box.placeholder.color + ';}'
      // Date dropdown placeholder color
      this.customStyle += `.lf-form__content form .lf-form__step .lf-form__question .lf-form__question__date-skin .date-input-box .ui-select__display-value.is-placeholder {color: ${this.theme.typography.input_box.placeholder.color}!important;}`
      // Dropdown placheolder color
      this.customStyle += `.lf-form__content form .lf-form__step .lf-form__question .lf-form__dropdown-options .ui-select__display-value.is-placeholder {color: ${this.theme.typography.input_box.placeholder.color}!important;}`
      // Input Box focus
      this.customStyle += `
      .lf-form .ui-textbox .ui-textbox__content .ui-textbox__label .ui-textbox__input:hover, .ui-textbox__textarea:hover, .lf-form .ui-select__display:hover,
      .lf-form .ui-textbox .ui-textbox__content .ui-textbox__label .ui-textbox__input:focus {
        ${this.theme.typography.input_box.border.skin === 'all' ? 'border' : 'border-bottom'}: ${this.theme.typography.input_box.border.width}px ${this.theme.typography.input_box.border.style} ${this.theme.general.colors.active_color} !important;
      }`
      // Select Country / date
      this.customStyle += '.lf-form .ui-select__display, .ui-datepicker__display{' + (this.theme.typography.input_box.border.skin === 'all' ? 'border' : 'border-bottom') + ':' + this.theme.typography.input_box.border.width + 'px ' + ' ' + this.theme.typography.input_box.border.style + ' ' + this.theme.typography.input_box.border.color + '!important; box-shadow:' + 'none' + ';color:' + this.theme.typography.input_box.font.color + '!important; text-indent:' + this.theme.typography.input_box.font.text_intent + 'px !important;' + ';font-family:' + this.filterFontFamily(this.theme.typography.input_box.font.family) + '!important; font-size:' + this.theme.typography.input_box.font.font_size + 'px !important;' + ';background-color:' + this.theme.typography.input_box.font.background_color + ';border-radius:' + this.theme.typography.input_box.radius + 'px !important' +
      ';height:' + this.theme.typography.input_box.font.height + 'px !important;' + 'margin-bottom:' + this.theme.typography.input_box.font.spacing + 'px !important;' + '}'
      // date Picker header
      this.customStyle += '.lf-form .ui-datepicker-calendar--color-primary .ui-datepicker-calendar__header, .ui-datepicker-calendar--color-primary .ui-calendar-week__date.is-selected { background-color:' + this.theme.general.colors.active_color + '!important; }'
      this.customStyle += '.lf-form .ui-datepicker-calendar--color-primary .ui-calendar-week__date.is-today { color:' + this.theme.general.colors.active_color + '!important; }'
      // text area
      this.customStyle += '.lf-form .ui-textbox__textarea{ ' + (this.theme.typography.input_box.border.skin === 'all' ? 'border' : 'border-bottom') + ':' + this.theme.typography.input_box.border.width + 'px ' + ' ' + this.theme.typography.input_box.border.style + ' ' + this.theme.typography.input_box.border.color + '!important; box-shadow:' + 'none' + ';color:' + this.theme.typography.input_box.font.color + '!important;  padding:' + this.theme.typography.input_box.font.text_intent + 'px!important;' + 'font-family:' + this.filterFontFamily(this.theme.typography.input_box.font.family) + '!important; font-size:' + this.theme.typography.input_box.font.font_size + 'px !important;' + ';background-color:' + this.theme.typography.input_box.font.background_color + ' !important;border-radius:' + this.theme.typography.input_box.radius + 'px !important' + ';margin-bottom:' + this.theme.typography.input_box.font.spacing + 'px !important;' + 'box-sizing: border-box !important; }'
      // Border radius select
      this.customStyle += '.lf-form__content form .lf-form__radio-list li label.checkbox-label{ border-radius:' + this.theme.ui_elements.radio_checkbox.radius + 'px !important; }'
      this.customStyle += `.lf-form__content form .lf-form__radio-list li label.checkbox-label { font-size: ${this.theme.ui_elements.radio_checkbox.font.size}px; line-height: ${this.theme.ui_elements.radio_checkbox.font.line_height}px;}`
      // Margin bottom of radio skin
      this.customStyle += '.lf-form .alignment-vertical > .lf-form__radio-options__item > label { margin-bottom:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; height:auto;}'
      // Choice alignment issue on mobile
      this.customStyle += '.lf-form .ui-radio__input-wrapper { margin-right: 5px; }'
      this.customStyle += '.lf-form .ui-checkbox__checkmark{ margin-right: 5px; }'
      // Margin right of radio alignment-horizontal
      this.customStyle += '.lf-form .alignment-horizontal > .lf-form__radio-options__item > label  { margin-right:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'
      // border radius of radio with outline
       this.customStyle += '.lf-form__content form .lf-form__radio_outline-list  li > label { border-radius:' + this.theme.ui_elements.radio_checkbox.radius + 'px !important; }'
       this.customStyle += '.lf-form__content form .lf-form__radio_outline-list  li > label { border-style:' + this.theme.ui_elements.radio_checkbox.style + 'px !important; }'
      //radio outer color
       this.customStyle += `.lf-form__radio_outline-list .ui-radio--color-primary  .ui-radio__outer-circle {border-color: ${this.theme.ui_elements.radio_checkbox.default_style.border.color} !important;}`
      //font size of radio with outline skin
       this.customStyle += `.lf-form__content form .lf-form__radio_outline-list  .ui-radio__label-text{ font-size: ${this.theme.ui_elements.radio_checkbox.font.size}px; line-height: ${this.theme.ui_elements.radio_checkbox.font.line_height}px;}`
      // margin bottom of radio with outline
      this.customStyle += '.lf-form__content form .lf-form__radio_outline-list  li > label {margin-bottom:' + this.theme.ui_elements.radio_checkbox.margin+ 'px !important; }'
      //margin right of radio alignment-horizontal
      this.customStyle += '.lf-form__content form .lf-form__radio_outline-list  li > label { margin-right:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'
      // Height of radio with ouline button
      this.customStyle += '.lf-form__content form .lf-form__radio_outline-list  li > label { height:' + this.theme.ui_elements.radio_checkbox.height + 'px !important; }'

      // Margin right of radio alignment-horizontal_center
      this.customStyle += '.lf-form .alignment-horizontal_center > div > label { margin-right:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'
      // Margin right alignment-horizontal button multiple selection
      this.customStyle += '.lf-form .alignment-horizontal > li  { margin-right:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'
      // Margin right of Buttons alignment-horizontal_center multiple selection
      this.customStyle += '.lf-form .alignment-horizontal_center > li { margin-right:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'
      //  alignment-vertical  Margin right of checkbox multiple selection
      this.customStyle += '.lf-form .alignment-vertical >.ui-checkbox-group.is-vertical .ui-checkbox-group__checkbox{ margin-bottom:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; height:auto }'
      this.customStyle += '.lf-form .alignment-vertical >.ui-checkbox-group.is-vertical .ui-checkbox-group__checkbox:last-child   { margin-bottom: 0px !important;}'
      //  alignment-horizontal  Margin right of checkbox multiple selection
      this.customStyle += '.lf-form .alignment-horizontal .ui-checkbox-group__checkboxes label { margin-right:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'
      //  alignment-horizontal_center Margin right of checkbox multiple selection
      this.customStyle += '.lf-form .alignment-horizontal_center .ui-checkbox-group__checkboxes label { margin-right:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'
      this.customStyle += '.lf-form .alignment-vertical>li  { margin-bottom:' + this.theme.ui_elements.radio_checkbox.margin + 'px !important; }'
      this.customStyle += '.lf-form .alignment-vertical>li:last-child   { margin-bottom: 0px !important;}'

      // image-icon style
      this.customStyle += `
        .lf-form__single-select-question .lf-form__image-options__item,
        .lf-form__multi-select-question .lf-form__image-options__item,
        .lf-form__single-select-question .lf-form__icon-options__item,
        .lf-form__multi-select-question .lf-form__icon-options__item {
          overflow: hidden;
        }
      `
      this.customStyle += `
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__image-options__item,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__image-options__item,
        .lf-form__single-select-question.skin-layout-inner_content .lf-form__image-options__item,
        .lf-form__multi-select-question.skin-layout-inner_content .lf-form__image-options__item,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__image-options__item .lf-form__image-options__item__image-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__image-options__item .lf-form__image-options__item__image-wrapper,
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__icon-options__item,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__icon-options__item,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__icon-options__item .lf-form__icon-options__item__icon-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__icon-options__item .lf-form__icon-options__item__icon-wrapper {
          border: ${this.theme.ui_elements.choice.image_icon_skin.border.width}px ${this.theme.ui_elements.choice.image_icon_skin.border.style} ${this.theme.ui_elements.choice.image_icon_skin.border.color} !important;
          background-color: ${this.theme.ui_elements.choice.image_icon_skin.background_color} !important;
        }
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__image-options__item:hover,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__image-options__item:hover,
        .lf-form__single-select-question.skin-layout-inner_content .lf-form__image-options__item:hover,
        .lf-form__multi-select-question.skin-layout-inner_content .lf-form__image-options__item:hover,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__image-options__item:hover .lf-form__image-options__item__image-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__image-options__item:hover .lf-form__image-options__item__image-wrapper,
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__icon-options__item:hover,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__icon-options__item:hover,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__icon-options__item:hover .lf-form__icon-options__item__icon-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__icon-options__item:hover .lf-form__icon-options__item__icon-wrapper {
          border: ${this.theme.ui_elements.choice.image_icon_skin.hover_style.border.width}px ${this.theme.ui_elements.choice.image_icon_skin.hover_style.border.style} ${this.theme.ui_elements.choice.image_icon_skin.hover_style.border.color} !important;
          background-color:${this.theme.ui_elements.choice.image_icon_skin.hover_style.background_color} !important;
        }
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__image-options__item--selected,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__image-options__item--selected,
        .lf-form__single-select-question.skin-layout-inner_content .lf-form__image-options__item--selected,
        .lf-form__multi-select-question.skin-layout-inner_content .lf-form__image-options__item--selected,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__image-options__item--selected .lf-form__image-options__item__image-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__image-options__item--selected .lf-form__image-options__item__image-wrapper,
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__icon-options__item--selected,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__icon-options__item--selected,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__icon-options__item--selected .lf-form__icon-options__item__icon-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__icon-options__item--selected .lf-form__icon-options__item__icon-wrapper {
          border: ${this.theme.ui_elements.choice.image_icon_skin.active_style.border.width}px
          ${this.theme.ui_elements.choice.image_icon_skin.active_style.border.style}
          ${this.theme.ui_elements.choice.image_icon_skin.active_style.border.color} !important;
          background-color: ${this.theme.ui_elements.choice.image_icon_skin.active_style.background_color} !important;
        }

        .lf-form__single-select-question.skin-layout-inner_content .lf-form__image-options__item__content-wrapper,
        .lf-form__multi-select-question.skin-layout-inner_content .lf-form__image-options__item__content-wrapper,
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__image-options__item,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__image-options__item,
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__icon-options__item,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__icon-options__item {
          padding: ${this.theme.ui_elements.choice.image_icon_skin.padding.top}px ${this.theme.ui_elements.choice.image_icon_skin.padding.right}px ${this.theme.ui_elements.choice.image_icon_skin.padding.bottom}px  ${this.theme.ui_elements.choice.image_icon_skin.padding.left}px;
        }
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__image-options__item__content-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__image-options__item__content-wrapper,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__icon-options__item__content-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__icon-options__item__content-wrapper {
          padding-left:${this.theme.ui_elements.choice.image_icon_skin.padding.left}px;
          padding-right:${this.theme.ui_elements.choice.image_icon_skin.padding.right}px;
        }

        .lf-form__multi-select-question .lf-form__icon-options__item .lf-form__icon-options__item__label,
        .lf-form__single-select-question .lf-form__icon-options__item .lf-form__icon-options__item__label,
        .lf-form__multi-select-question .lf-form__image-options__item .lf-form__image-options__item__label,
        .lf-form__single-select-question .lf-form__image-options__item .lf-form__image-options__item__label {
          font-family: ${this.filterFontFamily(this.theme.ui_elements.choice.image_icon_skin.title_font.family)} !important;
          font-weight: ${this.theme.ui_elements.choice.image_icon_skin.title_font.weight} !important;
          font-size: ${this.theme.ui_elements.choice.image_icon_skin.title_font.size}px !important;
          line-height: ${this.theme.ui_elements.choice.image_icon_skin.title_font.line_height}px !important;
          color: ${this.theme.ui_elements.choice.image_icon_skin.title_font.color} !important;
        }
        .lf-form__multi-select-question .lf-form__icon-options__item:hover .lf-form__icon-options__item__label,
        .lf-form__single-select-question .lf-form__icon-options__item:hover .lf-form__icon-options__item__label,
        .lf-form__multi-select-question .lf-form__image-options__item:hover .lf-form__image-options__item__label,
        .lf-form__single-select-question .lf-form__image-options__item:hover .lf-form__image-options__item__label {
          color: ${this.theme.ui_elements.choice.image_icon_skin.hover_style.title_font.color}!important;
        }
        .lf-form__multi-select-question .lf-form__icon-options__item--selected .lf-form__icon-options__item__label,
        .lf-form__single-select-question .lf-form__icon-options__item--selected .lf-form__icon-options__item__label,
        .lf-form__multi-select-question .lf-form__image-options__item--selected .lf-form__image-options__item__label,
        .lf-form__single-select-question .lf-form__image-options__item--selected .lf-form__image-options__item__label {
          color: ${this.theme.ui_elements.choice.image_icon_skin.active_style.title_font.color}!important;
        }

        .lf-form__multi-select-question .lf-form__icon-options__item .lf-form__icon-options__item__desc,
        .lf-form__single-select-question .lf-form__icon-options__item .lf-form__icon-options__item__desc,
        .lf-form__multi-select-question .lf-form__image-options__item .lf-form__image-options__item__desc,
        .lf-form__single-select-question .lf-form__image-options__item .lf-form__image-options__item__desc {
          font-family: ${this.filterFontFamily(this.theme.ui_elements.choice.image_icon_skin.description_font.family)} !important;
          font-weight: ${this.theme.ui_elements.choice.image_icon_skin.description_font.weight} !important;
          font-size: ${this.theme.ui_elements.choice.image_icon_skin.description_font.size}px !important;
          line-height: ${this.theme.ui_elements.choice.image_icon_skin.description_font.line_height}px !important;
          color: ${this.theme.ui_elements.choice.image_icon_skin.description_font.color} !important;
        }
        .lf-form__multi-select-question .lf-form__icon-options__item:hover .lf-form__icon-options__item__desc,
        .lf-form__single-select-question .lf-form__icon-options__item:hover .lf-form__icon-options__item__desc,
        .lf-form__multi-select-question .lf-form__image-options__item:hover .lf-form__image-options__item__desc,
        .lf-form__single-select-question .lf-form__image-options__item:hover .lf-form__image-options__item__desc {
          color: ${this.theme.ui_elements.choice.image_icon_skin.hover_style.description_font.color}!important;
        }
        .lf-form__multi-select-question .lf-form__icon-options__item--selected .lf-form__icon-options__item__desc,
        .lf-form__single-select-question .lf-form__icon-options__item--selected .lf-form__icon-options__item__desc,
        .lf-form__multi-select-question .lf-form__image-options__item--selected .lf-form__image-options__item__desc,
        .lf-form__single-select-question .lf-form__image-options__item--selected .lf-form__image-options__item__desc {
          color: ${this.theme.ui_elements.choice.image_icon_skin.active_style.description_font.color}!important;
        }

        .lf-form__single-select-question .lf-form__icon-options__item .lf-form__icon-options__item__icon svg,
        .lf-form__multi-select-question .lf-form__icon-options__item .lf-form__icon-options__item__icon svg{
           width: ${this.theme.ui_elements.choice.image_icon_skin.icon_size}px;
           fill: ${this.theme.ui_elements.choice.image_icon_skin.icon_color}
        }
        .lf-form__multi-select-question .lf-form__icon-options__item:hover .lf-form__icon-options__item__icon svg,
        .lf-form__single-select-question .lf-form__icon-options__item:hover .lf-form__icon-options__item__icon svg {
          fill: ${this.theme.ui_elements.choice.image_icon_skin.hover_style.icon_color}!important;
        }
        .lf-form__single-select-question .lf-form__icon-options__item--selected .lf-form__icon-options__item__icon svg,
        .lf-form__multi-select-question .lf-form__icon-options__item--selected .lf-form__icon-options__item__icon svg {
          fill: ${this.theme.ui_elements.choice.image_icon_skin.active_style.icon_color}!important;
        }

        .lf-form__single-select-question .lf-form__image-options__item__image,
        .lf-form__multi-select-question .lf-form__image-options__item__image {
          display: block;
          width: 100% !important;
          margin: auto;
        }

        .lf-form__single-select-question .lf-form__image-options__item__image img,
        .lf-form__multi-select-question .lf-form__image-options__item__image img {
          width: ${this.theme.ui_elements.choice.image_icon_skin.image_width}% !important;
        }

        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__image-options__item,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__image-options__item,
        .lf-form__single-select-question.skin-layout-inner_content .lf-form__image-options__item,
        .lf-form__multi-select-question.skin-layout-inner_content .lf-form__image-options__item,
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__icon-options__item,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__icon-options__item,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__image-options__item .lf-form__image-options__item__image-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__image-options__item .lf-form__image-options__item__image-wrapper,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__icon-options__item .lf-form__icon-options__item__icon-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__icon-options__item .lf-form__icon-options__item__icon-wrapper {
          width: ${this.theme.ui_elements.choice.image_icon_skin.width}% !important;
          min-height: ${this.theme.ui_elements.choice.image_icon_skin.height}px !important;
          border-radius: ${this.theme.ui_elements.choice.image_icon_skin.border.radius}px !important;
        }

        .lf-form__single-select-question .lf-form__image-options__item--selected > .lf-form__image-options__item__checked,
        .lf-form__multi-select-question .lf-form__image-options__item--selected > .lf-form__image-options__item__checked,
        .lf-form__single-select-question .lf-form__icon-options__item--selected > .lf-form__icon-options__item__checked,
        .lf-form__multi-select-question .lf-form__icon-options__item--selected > .lf-form__icon-options__item__checked {
          display: inline-block !important;
        }

        .lf-form__single-select-question .lf-form__image-options__item__checked svg path,
        .lf-form__multi-select-question .lf-form__image-options__item__checked svg path,
        .lf-form__single-select-question .lf-form__icon-options__item__checked svg path,
        .lf-form__multi-select-question .lf-form__icon-options__item__checked svg path {
          fill: ${this.theme.ui_elements.choice.image_icon_skin.tickbox.color || this.theme.general.colors.active_color};
        }
      `

      if (this.theme.ui_elements.choice.image_icon_skin.shadow) {
        this.customStyle += `
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__image-options__item,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__image-options__item,
        .lf-form__single-select-question.skin-layout-inner_content .lf-form__image-options__item,
        .lf-form__multi-select-question.skin-layout-inner_content .lf-form__image-options__item,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__image-options__item .lf-form__image-options__item__image-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__image-options__item .lf-form__image-options__item__image-wrapper,
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__icon-options__item,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__icon-options__item,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__icon-options__item .lf-form__icon-options__item__icon-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__icon-options__item .lf-form__icon-options__item__icon-wrapper {
             box-shadow: 0 0 5px lightgrey;
          }
        `
      }
      if (this.theme.ui_elements.choice.image_icon_skin.hover_style.shadow) {
        this.customStyle += `
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__image-options__item:hover,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__image-options__item:hover,
        .lf-form__single-select-question.skin-layout-inner_content .lf-form__image-options__item:hover,
        .lf-form__multi-select-question.skin-layout-inner_content .lf-form__image-options__item:hover,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__image-options__item:hover .lf-form__image-options__item__image-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__image-options__item:hover .lf-form__image-options__item__image-wrapper,
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__icon-options__item:hover,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__icon-options__item:hover,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__icon-options__item:hover .lf-form__icon-options__item__icon-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__icon-options__item:hover .lf-form__icon-options__item__icon-wrapper {
             box-shadow: 0 0 5px lightgrey;
          }
        `
      } else {
        this.customStyle += `
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__image-options__item:hover,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__image-options__item:hover,
        .lf-form__single-select-question.skin-layout-inner_content .lf-form__image-options__item:hover,
        .lf-form__multi-select-question.skin-layout-inner_content .lf-form__image-options__item:hover,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__image-options__item:hover .lf-form__image-options__item__image-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__image-options__item:hover .lf-form__image-options__item__image-wrapper,
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__icon-options__item:hover,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__icon-options__item:hover,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__icon-options__item:hover .lf-form__icon-options__item__icon-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__icon-options__item:hover .lf-form__icon-options__item__icon-wrapper {
             box-shadow: 0 0 0 lightgrey;
          }
        `
      }
      if (this.theme.ui_elements.choice.image_icon_skin.active_style.shadow) {
        this.customStyle += `
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__image-options__item--selected,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__image-options__item--selected,
        .lf-form__single-select-question.skin-layout-inner_content .lf-form__image-options__item--selected,
        .lf-form__multi-select-question.skin-layout-inner_content .lf-form__image-options__item--selected,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__image-options__item--selected .lf-form__image-options__item__image-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__image-options__item--selected .lf-form__image-options__item__image-wrapper,
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__icon-options__item--selected,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__icon-options__item--selected,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__icon-options__item--selected .lf-form__icon-options__item__icon-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__icon-options__item--selected .lf-form__icon-options__item__icon-wrapper {
             box-shadow: 0 0 5px lightgrey;
          }
        `
      } else {
        this.customStyle += `
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__image-options__item--selected,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__image-options__item--selected,
        .lf-form__single-select-question.skin-layout-inner_content .lf-form__image-options__item--selected,
        .lf-form__multi-select-question.skin-layout-inner_content .lf-form__image-options__item--selected,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__image-options__item--selected .lf-form__image-options__item__image-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__image-options__item--selected .lf-form__image-options__item__image-wrapper,
        .lf-form__single-select-question.skin-layout-boxed_content .lf-form__icon-options__item--selected,
        .lf-form__multi-select-question.skin-layout-boxed_content .lf-form__icon-options__item--selected,
        .lf-form__single-select-question.skin-layout-outer_content .lf-form__icon-options__item--selected .lf-form__icon-options__item__icon-wrapper,
        .lf-form__multi-select-question.skin-layout-outer_content .lf-form__icon-options__item--selected .lf-form__icon-options__item__icon-wrapper  {
             box-shadow: 0 0 0 lightgrey;
          }
        `
      }

      if (this.theme.general.dynamic_fadein) {
        this.customStyle += `
        .fade-enter-active{
          -webkit-animation: fade-in 1s cubic-bezier(0.230, 1.000, 0.320, 1.000) 0.3s both;
	        animation: fade-in 1s cubic-bezier(0.230, 1.000, 0.320, 1.000) 0.3s both;
        }
        .fade-inactive{
          animation: none !important
        }

        .fade-enter, .fade-leave-to {
          opacity: 0;
        }
        @-webkit-keyframes fade-in {
          0% {
            opacity: 0;
          }
          100% {
            opacity: 1;
          }
        }
        @keyframes fade-in {
          0% {
            opacity: 0;
          }
          100% {
            opacity: 1;
          }
        }
        `
      }
      if (!this.theme.general.dynamic_height && this.theme.general.dynamic_fadein) {
        this.customStyle += `
        .fade-enter-active{
          -webkit-animation: fade-in 1s cubic-bezier(0.230, 1.000, 0.320, 1.000) 0.3s both;
	        animation: fade-in 1s cubic-bezier(0.230, 1.000, 0.320, 1.000) 0.3s both;
        }
        .fade-inactive{
          animation: none !important
        }

        .lf-visibility-hidden {
          opacity:0 !important;
        }
        @-webkit-keyframes fade-in {
          0% {
            opacity: 0;
          }
          100% {
            opacity: 1;
          }
        }
        @keyframes fade-in {
          0% {
            opacity: 0;
          }
          100% {
            opacity: 1;
          }
        }
        `
      }

      // Single|Multi select Button Skin default
      this.customStyle += `
        .lf-form__question__inputfield input[type="checkbox"] + label,  .lf-form__question__inputfield input[type="radio"] + label {
          border: ${this.theme.ui_elements.radio_checkbox.default_style.border.width}px ${this.theme.ui_elements.radio_checkbox.default_style.border.style} ${this.theme.ui_elements.radio_checkbox.default_style.border.color} !important;
          color: ${this.theme.ui_elements.radio_checkbox.default_style.color} !important;
          background-color: ${this.theme.ui_elements.radio_checkbox.default_style.backgroundcolor} !important;
          font-family: ${this.filterFontFamily(this.theme.general.font.family)} !important;
        }
      `
      this.customStyle += `
        .lf-form__radio-options__item label .ui-radio__label-text, .ui-checkbox-group__checkboxes .ui-checkbox__label-text {
          color: ${this.theme.ui_elements.radio_checkbox.default_style.color} !important;
          font-family: ${this.filterFontFamily(this.theme.general.font.family)} !important;
          font-size: ${this.theme.ui_elements.radio_checkbox.font.size}px;
        }
      `
      // single radio with outline active|hover
      this.customStyle += `
        .lf-form__radio_outline-list .ui-radio--color-primary  .ui-radio__outer-circle {
          border-color: ${this.theme.ui_elements.radio_checkbox.default_style.border.color} !important;
        }
      `

      this.customStyle += `
        .lf-form__radio_outline-list .ui-radio--color-primary:hover .ui-radio__outer-circle {
          border-color: ${this.theme.ui_elements.radio_checkbox.hover_style.border.color} !important;
        }`

       this.customStyle += `
        .lf-form__radio_outline-list .ui-radio--color-primary.is-checked:not(.is-disabled) .ui-radio__outer-circle {
          border-color: ${this.theme.ui_elements.radio_checkbox.active_style.border.color} !important;
        }

        .lf-form__radio_outline-list .ui-radio--color-primary.is-checked:not(.is-disabled) .ui-radio__inner-circle {
          background-color: ${this.theme.ui_elements.radio_checkbox.active_style.border.color} !important;
        }
      `
      // Dropdown Skin
      this.customStyle += `
       .ui-select__options {
          color: ${this.theme.ui_elements.choice.dropdown_skin.default_style.color} !important;
          font-family: ${this.theme.general.font.family} !important;
        }
      `
      this.customStyle += `
       .tippy-popper .ui-select-option.is-selected .ui-select-option__checkbox {
          color: ${this.theme.ui_elements.radio_checkbox.active_style.border.color} !important;
          font-family: ${this.theme.general.font.family} !important;
        }
      `

      // Single|Multi select Button Skin hover
      if (this.theme.ui_elements.radio_checkbox.hover_style.border.width === '0' && this.theme.ui_elements.radio_checkbox.default_style.border.width) {
        this.customStyle += `
          .lf-form__question__inputfield input[type="checkbox"]:hover + label,  .lf-form__question__inputfield input[type="radio"]:hover + label {
            border: ${this.theme.ui_elements.radio_checkbox.default_style.border.width}px ${this.theme.ui_elements.radio_checkbox.hover_style.border.style} ${this.theme.ui_elements.radio_checkbox.hover_style.color} transparent !important;
            color: ${this.theme.ui_elements.radio_checkbox.hover_style.color} !important;
            background-color: ${this.theme.ui_elements.radio_checkbox.hover_color} !important;
            font-family: ${this.filterFontFamily(this.theme.general.font.family)} !important;
          }
        `
      } else {
        this.customStyle += `
            .lf-form__question__inputfield input[type="checkbox"]:hover + label,  .lf-form__question__inputfield input[type="radio"]:hover + label {
              border: ${this.theme.ui_elements.radio_checkbox.hover_style.border.width}px ${this.theme.ui_elements.radio_checkbox.hover_style.border.style} ${this.theme.ui_elements.radio_checkbox.hover_style.border.color} !important;
              color: ${this.theme.ui_elements.radio_checkbox.hover_style.color} !important;
              background-color: ${this.theme.ui_elements.radio_checkbox.hover_color} !important;
              font-family: ${this.filterFontFamily(this.theme.general.font.family)} !important;
          }
        `
      }

      // Single|Multi select Button Skin Checked|Active
      this.customStyle += `
        .lf-form__question__inputfield input[type="radio"]:checked + label, .lf-form__question__inputfield input[type="checkbox"]:checked + label {
            border: ${this.theme.ui_elements.radio_checkbox.active_style.border.width}px ${this.theme.ui_elements.radio_checkbox.active_style.border.style} ${this.theme.ui_elements.radio_checkbox.active_style.border.color}!important;
            color: ${this.theme.ui_elements.radio_checkbox.active_style.color} !important;
            background-color: ${this.theme.ui_elements.radio_checkbox.checked_color} !important;
            font-family: ${this.filterFontFamily(this.theme.general.font.family)} !important;
        }
      `

      // Single|Multi select Checkbox Skin default|checked|hover
      this.customStyle += `
        .lf-form__checkbox-options .ui-checkbox--color-primary .ui-checkbox__checkmark-background {
          border-color: ${this.theme.ui_elements.radio_checkbox.default_style.border.color} !important;
        }
      `

      this.customStyle += `
        .lf-form__checkbox-options .ui-checkbox--color-primary:hover .ui-checkbox__checkmark-background {
          border-color: ${this.theme.ui_elements.radio_checkbox.hover_style.border.color} !important;
        }
      `

      this.customStyle += `
        .lf-form__checkbox-options .ui-checkbox--color-primary.is-checked .ui-checkbox__checkmark-background {
          background-color: ${this.theme.ui_elements.radio_checkbox.active_style.border.color} !important;
          border-color: ${this.theme.ui_elements.radio_checkbox.active_style.border.color} !important;
        }
      `
      // Single|Multi select Radio skin default|checked|hover
      this.customStyle += `
        .lf-form__radio-options .ui-radio--color-primary  .ui-radio__outer-circle {
          border-color: ${this.theme.ui_elements.radio_checkbox.default_style.border.color} !important;
        }
      `
      this.customStyle += `
        .lf-form__radio-options .ui-radio--color-primary:hover .ui-radio__outer-circle {
          border-color: ${this.theme.ui_elements.radio_checkbox.hover_style.border.color} !important;
        }
      `
      this.customStyle += `
        .lf-form__radio-options .ui-radio--color-primary.is-checked:not(.is-disabled) .ui-radio__outer-circle {
          border-color: ${this.theme.ui_elements.radio_checkbox.active_style.border.color} !important;
        }

        .lf-form__radio-options .ui-radio--color-primary.is-checked:not(.is-disabled) .ui-radio__inner-circle {
          background-color: ${this.theme.ui_elements.radio_checkbox.active_style.border.color} !important;
        }
      `
      // likert radio skin default|checked|hover
      this.customStyle += `
        .lf-form-likert-radio-options .ui-radio--color-primary  .ui-radio__outer-circle {
          border-color: ${this.theme.ui_elements.radio_checkbox.default_style.border.color} !important;
        }
      `
      this.customStyle += `
        .lf-form-likert-radio-options .ui-radio--color-primary:hover .ui-radio__outer-circle {
          border-color: ${this.theme.ui_elements.radio_checkbox.hover_style.border.color} !important;
        }
      `
      this.customStyle += `
        .lf-form-likert-radio-options .ui-radio--color-primary.is-checked:not(.is-disabled) .ui-radio__outer-circle {
          border-color: ${this.theme.ui_elements.radio_checkbox.active_style.border.color} !important;
        }`
      //radio with outline
      this.customStyle += `
        .lf-form__radio_outline-options .ui-radio--color-primary  .ui-radio__outer-circle {
          border-color: ${this.theme.ui_elements.radio_checkbox.default_style.border.color} !important;
        }
      `
      this.customStyle += `
        .lf-form__radio-options .ui-radio--color-primary:hover .ui-radio__outer-circle {
          border-color: ${this.theme.ui_elements.radio_checkbox.hover_style.border.color} !important;
        }
      `
      this.customStyle += `
        .lf-form__radio_outline-options .ui-radio--color-primary.is-checked:not(.is-disabled) .ui-radio__outer-circle {
          border-color: ${this.theme.ui_elements.radio_checkbox.active_style.border.color} !important;
        }

        .lf-form-likert-radio-options .ui-radio--color-primary.is-checked:not(.is-disabled) .ui-radio__inner-circle {
          background-color: ${this.theme.ui_elements.radio_checkbox.active_style.border.color} !important;
        }


        .lf-form__radio_outline-options .ui-radio--color-primary.is-checked:not(.is-disabled) .ui-radio__inner-circle {
          background-color: ${this.theme.ui_elements.radio_checkbox.active_style.border.color} !important;
        }
      `
      this.customStyle += `
        .lf-form__radio_outline-options .ui-radio--color-primary:hover .ui-radio__outer-circle {
          border-color: ${this.theme.ui_elements.radio_checkbox.hover_style.border.color} !important;
        }
      `
      this.customStyle += `
        .lf-form__radio_outline-options .ui-radio--color-primary.is-checked:not(.is-disabled) .ui-radio__outer-circle {
          border-color: ${this.theme.ui_elements.radio_checkbox.active_style.border.color} !important;
        }

        .lf-form__radio_outline-options .ui-radio--color-primary.is-checked:not(.is-disabled) .ui-radio__inner-circle {
          background-color: ${this.theme.ui_elements.radio_checkbox.active_style.border.color} !important;
        }
      `

      // Dropdown icon
      this.customStyle += `
        .ui-select__dropdown-button, .ui-datepicker__dropdown-button{
          margin-right: 0.75rem !important;
        }
      `

      // Country Search Box Color
      this.customStyle += `
        .ui-select__search .ui-icon svg, .ui-select__dropdown .ui-select__options .ui-select-option.is-selected {
          color: ${this.theme.typography.input_box.font.color} !important;
        }
       `

      // Thank You message
      this.customStyle += `
        .lf-form .lf-form__thankyou__msg,
        .lf-form .lf-form__thankyou__msg p,
        .lf-form .lf-form__thankyou__msg h1,
        .lf-form .lf-form__thankyou__msg h2,
        .lf-form .lf-form__thankyou__msg h3,
        .lf-form .lf-form__thankyou__msg h4,
        .lf-form .lf-form__thankyou__msg h5,
        .lf-form .lf-form__thankyou__msg h6,
        .lf-form .lf-form__thankyou__msg a {
          font-family: ${this.filterFontFamily(this.theme.general.font.family)};
          font-weight: 300;
        }
      `

      // ql-editor
      this.customStyle += `
        .lf-form .lf-form__element .ql-editor * {
          font-family: ${this.theme.typography.text_box.family} !important;
          color: ${this.theme.typography.text_box.color} !important;
        }
      `
      // Footer text
      this.customStyle += `
      .lf-form__footer .lf-form__footer__msg .ql-editor {
        font-family:  ${this.filterFontFamily(this.theme.typography.text_box.family)} !important;
        color: ${this.theme.typography.text_box.color} !important;
      }`

     //iframe style fix for dropdown - country/state/phone
      if(this.insideIframe) {
        this.customStyle += `
          .ui-select__dropdown.is-raised {
            overflow-x: hidden;
          }
          .ui-select__dropdown {
            position: absolute;
            max-height: 150px;
          }
          .ui-select__options {
            max-height: fit-content;
          }
          .lf-form__question[data-question-type="PHONE_NUMBER"] .intl-tel-input .country-list {
            width: 90vw;
            max-height: 150px;
          }
          .lf-form__question[data-question-type="PHONE_NUMBER"] .iti .country-list {
            width: 90vw;
            max-height: 150px;
          }
        `
      }
    },
    consentStyle: function () {
       this.customStyle += `
        .consent-text {
          font-size: 14px;
        }
        .informed-consent {
          color: ${this.theme.typography.question_description.font.color} !important;
          font-family: ${this.filterFontFamily(this.theme.typography.question_description.font.family)} !important;
          font-weight: ${this.theme.typography.question_description.font.weight} !important;
        }
        .consent-text .ui-checkbox__label-text {
          font-size: 14px;
          color: ${this.theme.typography.question_description.font.color} !important;
          font-family: ${this.filterFontFamily(this.theme.typography.question_description.font.family)} !important;
          font-weight: ${this.theme.typography.question_description.font.weight} !important;
        }
        }
        .consent-text .ui-alert .ui-alert__body {
          min-height: 0;
        }
        .consent-text .ui-alert .ui-alert__content {
          font-size: 13px !important;
        }
       `
    },
     buttonStyles: function () {
      let temp = 'center;'
      if (this.theme.ui_elements.step_navigation.back_button.alignment === 'left') {
        temp = 'flex-start;'
      } else if (this.theme.ui_elements.step_navigation.back_button.alignment === 'right') {
        temp = 'flex-end;'
      }
      //in case of 50% button width
       if (this.theme.ui_elements.step_navigation.back_button.alignment === 'center') {
        this.customStyle += '.lf-form__content form .lf-form__step-nav .lf-form__mid-step-nav .back_button_wrapper { justify-content: center !important;} '
        } else if (this.theme.ui_elements.step_navigation.back_button.alignment === 'right') {
       this.customStyle += '.lf-form__content form .lf-form__step-nav .lf-form__mid-step-nav .back_button_wrapper { justify-content: flex-end !important;} '
        }
      this.customStyle += '.lf-form__mid-step-nav  { justify-content:' + temp + ';width:' + this.theme.ui_elements.step_navigation.back_button.width + '%; }'
      temp = 'center;'
      if (this.theme.ui_elements.step_navigation.next_button.alignment === 'left') {
        temp = 'flex-start;'
      } else if (this.theme.ui_elements.step_navigation.next_button.alignment === 'right') {
        temp = 'flex-end;'
      }
      this.customStyle += '.lf-form__mid-step-nav > .continue_button_wrapper { justify-content:' + temp + ';width:' + this.theme.ui_elements.step_navigation.next_button.width + '%; }'
      this.customStyle += '.lf-form__step-nav { justify-content:' + temp + ' }'
      this.customStyle += '.lf-form__first-step-nav { justify-content:' + temp + ' }'
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
        this.customStyle += '.lf-form__content form .lf-form__step-nav .lf-form__mid-step-nav .back_button_wrapper, .lf-form__content form .lf-form__step-nav .lf-form__mid-step-nav .continue_button_wrapper, .lf-form__content form .lf-form__step-nav .lf-form__mid-step-nav .submit_button_wrapper { width:48%; padding-right: 5px; } '
      }
      // back button hover
      this.customStyle += '.lf-form__content form .lf-form__step-nav .lf-form__mid-step-nav .back_button_wrapper>div>.ui-button:hover{ color:' + this.theme.ui_elements.step_navigation.back_button.hover_style.color + '!important; background-color:' + this.theme.ui_elements.step_navigation.back_button.hover_style.backgroundColor + '!important;border:' + this.theme.ui_elements.step_navigation.back_button.hover_style.border.width + 'px ' + this.theme.ui_elements.step_navigation.back_button.hover_style.border.style + ' ' + this.theme.ui_elements.step_navigation.back_button.hover_style.border.color + '!important}'
      // back button active
      this.customStyle += '.lf-form__content form .lf-form__step-nav .lf-form__mid-step-nav .back_button_wrapper>div>.ui-button:active{ color:' + this.theme.ui_elements.step_navigation.back_button.active_style.color + '!important; background-color:' + this.theme.ui_elements.step_navigation.back_button.active_style.backgroundColor + '!important;border:' + this.theme.ui_elements.step_navigation.back_button.active_style.border.width + 'px ' + this.theme.ui_elements.step_navigation.back_button.active_style.border.style + ' ' + this.theme.ui_elements.step_navigation.back_button.active_style.border.color + '!important}'

      // back button icon
      this.customStyle += `.lf-form__content .lf-form__step-nav .lf-form__prev-step-btn__icon svg { width: 24px; fill: ${this.theme.ui_elements.step_navigation.back_button.font.color}}`
      // back button icon active
      this.customStyle += `.lf-form__content form .lf-form__step-nav .lf-form__mid-step-nav .back_button_wrapper>div>.ui-button:active svg { fill: ${this.theme.ui_elements.step_navigation.back_button.active_style.color} !important;}`
      // back button icon hover
      this.customStyle += `.lf-form__content form .lf-form__step-nav .lf-form__mid-step-nav .back_button_wrapper>div>.ui-button:hover .lf-form__prev-step-btn__icon svg { fill: ${this.theme.ui_elements.step_navigation.back_button.hover_style.color} ;}`

      // continue button hover
      this.customStyle += '.lf-form__content form .lf-form__step-nav .lf-form__mid-step-nav .continue_button_wrapper>div>.ui-button:hover{ color:' + this.theme.ui_elements.step_navigation.next_button.hover_style.color + '!important; background-color:' + this.theme.ui_elements.step_navigation.next_button.hover_style.backgroundColor + '!important;border:' + this.theme.ui_elements.step_navigation.next_button.hover_style.border.width + 'px ' + this.theme.ui_elements.step_navigation.next_button.hover_style.border.style + ' ' + this.theme.ui_elements.step_navigation.next_button.hover_style.border.color + '!important}'
      this.customStyle += '.lf-form__content form .lf-form__step-nav .lf-form__first-step-nav>.ui-button:hover{ color:' + this.theme.ui_elements.step_navigation.next_button.hover_style.color + '!important; background-color:' + this.theme.ui_elements.step_navigation.next_button.hover_style.backgroundColor + '!important;border:' + this.theme.ui_elements.step_navigation.next_button.hover_style.border.width + 'px ' + this.theme.ui_elements.step_navigation.next_button.hover_style.border.style + ' ' + this.theme.ui_elements.step_navigation.next_button.hover_style.border.color + '!important}'
      // continue button active
      this.customStyle += '.lf-form__content form .lf-form__step-nav .lf-form__mid-step-nav .continue_button_wrapper>div>.ui-button:active{ color:' + this.theme.ui_elements.step_navigation.next_button.active_style.color + '!important; background-color:' + this.theme.ui_elements.step_navigation.next_button.active_style.backgroundColor + '!important;border:' + this.theme.ui_elements.step_navigation.next_button.active_style.border.width + 'px ' + this.theme.ui_elements.step_navigation.next_button.active_style.border.style + ' ' + this.theme.ui_elements.step_navigation.next_button.active_style.border.color + '!important}'
      this.customStyle += '.lf-form__content form .lf-form__step-nav .lf-form__first-step-nav>.ui-button:active{ color:' + this.theme.ui_elements.step_navigation.next_button.active_style.color + '!important; background-color:' + this.theme.ui_elements.step_navigation.next_button.active_style.backgroundColor + '!important;border:' + this.theme.ui_elements.step_navigation.next_button.active_style.border.width + 'px ' + this.theme.ui_elements.step_navigation.next_button.active_style.border.style + ' ' + this.theme.ui_elements.step_navigation.next_button.active_style.border.color + '!important}'

      // continue button icon
      this.customStyle += `.lf-form__content .lf-form__step-nav .lf-form__next-step-btn__icon svg { width: 24px; fill: ${this.theme.ui_elements.step_navigation.next_button.font.color}}`
      // continue button icon active
      this.customStyle += `.lf-form__content form .lf-form__step-nav .lf-form__next-step-btn:active .lf-form__next-step-btn__icon svg { fill: ${this.theme.ui_elements.step_navigation.next_button.active_style.color} !important;}`
      // continue button icon hover
      this.customStyle += `.lf-form__content form .lf-form__step-nav .lf-form__next-step-btn:hover .lf-form__next-step-btn__icon svg { fill: ${this.theme.ui_elements.step_navigation.next_button.hover_style.color} ;}`

      // submit button hover
      this.customStyle += '.lf-form__content form .lf-form__step-nav .lf-form__mid-step-nav .submit_button_wrapper>div>.ui-button:hover{ color:' + this.theme.ui_elements.step_navigation.submit_button.hover_style.color + '!important; background-color:' + this.theme.ui_elements.step_navigation.submit_button.hover_style.backgroundColor + '!important;border:' + this.theme.ui_elements.step_navigation.submit_button.hover_style.border.width + 'px ' + this.theme.ui_elements.step_navigation.submit_button.hover_style.border.style + ' ' + this.theme.ui_elements.step_navigation.submit_button.hover_style.border.color + '!important}'
      // submit button active
      this.customStyle += '.lf-form__content form .lf-form__step-nav .lf-form__mid-step-nav .submit_button_wrapper>div>.ui-button:active{ color:' + this.theme.ui_elements.step_navigation.submit_button.active_style.color + '!important; background-color:' + this.theme.ui_elements.step_navigation.submit_button.active_style.backgroundColor + '!important;border:' + this.theme.ui_elements.step_navigation.submit_button.active_style.border.width + 'px ' + this.theme.ui_elements.step_navigation.submit_button.active_style.border.style + ' ' + this.theme.ui_elements.step_navigation.submit_button.active_style.border.color + '!important}'

      // submit button icon
      this.customStyle += `.lf-form__content .lf-form__step-nav .lf-form__submit-step-btn__icon svg { width: 24px; fill: ${this.theme.ui_elements.step_navigation.submit_button.font.color}}`
      // submit button icon active
      this.customStyle += `.lf-form__content form .lf-form__step-nav .lf-form__submit-step-btn:active .lf-form__submit-step-btn__icon svg { fill: ${this.theme.ui_elements.step_navigation.submit_button.active_style.color} !important;}`
      // submit button icon hover
      this.customStyle += `.lf-form__content form .lf-form__step-nav .lf-form__submit-step-btn:hover .lf-form__submit-step-btn__icon svg { fill: ${this.theme.ui_elements.step_navigation.submit_button.hover_style.color} ;}`
    },
    filterFontFamily: function (family) {
      if (family === 'inherit') {
        return this.websiteFontFamily
      }
      return family
    },
    questionTitleStyle: function () {
      this.customStyle += `
        .lf-form__content form .lf-form__step .lf-form__step__item .lf-form__question__inputfield > label {
          color: ${this.theme.typography.question_title.font.color} !important;
          font-family: ${this.filterFontFamily(this.theme.typography.question_title.font.family)} !important;
          font-size: ${this.theme.typography.question_title.font.size}px !important;
          font-weight: ${this.theme.typography.question_title.font.weight} !important;
          line-height: ${this.theme.typography.question_title.font.line_height}px !important;
          text-align: ${this.theme.typography.question_title.text_align} !important;
        }
      `
    },
    questionScaleStyle: function () {
      this.customStyle += `
        .lf-form__content form .lf-form__step .lf-form__step__item .lf-form-scale-counter-wrapper {
          color: ${this.theme.typography.input_box.font.color}!important;
          font-family: ${this.filterFontFamily(this.theme.typography.question_title.font.family)} !important;
          font-size: ${this.theme.typography.question_title.font.size}px !important;
        }
      `
       this.customStyle += `
      .lf-form__content form .lf-form__step .lf-form__step__item .lf-form-scale-counter-wrapper input[type="text"] {
          color: ${this.theme.typography.input_box.font.color}!important;
          font-family: ${this.filterFontFamily(this.theme.typography.question_title.font.family)} !important;
          font-size: ${this.theme.typography.question_title.font.size}px !important;
        }
      `
      this.customStyle += `
        .lf-form__content form .lf-form__step .lf-form__step__item .lf-form-range-counter {
          color: ${this.theme.typography.input_box.font.color}!important;
          font-family: ${this.filterFontFamily(this.theme.typography.question_title.font.family)} !important;
          font-size: ${this.theme.typography.question_title.font.size}px !important;
        }
      `
      this.customStyle += `
        .lf-form__content form .lf-form__step .lf-form__step__item .lf-form-range-counter input[type="text"] {
          color: ${this.theme.typography.input_box.font.color}!important;
          border-color:  ${this.theme.typography.input_box.border}!important;
          font-family: ${this.filterFontFamily(this.theme.typography.question_title.font.family)} !important;
          font-size: ${this.theme.typography.question_title.font.size}px !important;
        }
      `
      this.customStyle += `
        .lf-form__content form .lf-form__step .lf-form__step__item .lf-form-likert-smileys .lf-form-smiley {
          fill: ${this.theme.ui_elements.scale.smileys_color} !important
        }
      `
      this.customStyle += `
        .lf-form__content form .lf-form__step .lf-form__step__item .lf-form-likert-radio-label {
          color: ${this.theme.typography.question_title.font.color} !important;
          font-family: ${this.filterFontFamily(this.theme.typography.question_title.font.family)} !important;
          font-size: ${this.theme.typography.question_title.font.size}px !important;
          margin-left:5px;
          padding-bottom: 11px;
        }
      `
      this.customStyle += `
        .lf-form__content form .lf-form__step .lf-form__step__item .lf-form-likert-radio-text {
          color: ${this.theme.typography.question_description.font.color};
          font-family: ${this.filterFontFamily(this.theme.typography.question_title.font.family)} !important;
        }
      `
      this.customStyle += `
        .lf-form__content form .lf-form__step .lf-form__step__item .lf-form-likert-smileys .lf-form-smiley-wrapper .lf-form-active-class {
          fill: ${this.theme.ui_elements.scale.active_style.smileys_active_color} !important;
          box-shadow: 0px 2px 10px 2px ${this.theme.ui_elements.scale.active_style.smileys_active_color} !important;
          border-radius:50px;

        }
      `
      this.customStyle += `
        .lf-form__content form .lf-form__step .lf-form__step__item .lf-form-likert-smileys .lf-form-smiley-wrapper:hover .lf-form-smiley,
        .lf-form__content form .lf-form__step .lf-form__step__item .lf-form-likert-smileys .lf-form-smiley-wrapper:hover .lf-form-smiley-text {
          fill: ${this.theme.ui_elements.scale.hover_style.smileys_hover_color} !important;
          color: ${this.theme.ui_elements.scale.hover_style.smileys_hover_color} !important;
        }
      `
      this.customStyle += `
        .lf-form__content form .lf-form__step .lf-form__step__item .lf-form-likert-smileys .lf-form-smiley-text-wrapper {
          width: 90px;
          position: absolute;
          margin-left: -19px;
          text-align: center;
        }
      `
      this.customStyle += `
        .lf-form__content form .lf-form__step .lf-form__step__item .lf-form-likert-smileys .lf-form-smiley-text {
          color: ${this.theme.ui_elements.scale.smileys_text_color} !important;
          font-family: ${this.filterFontFamily(this.theme.typography.question_title.font.family)} !important;
          font-size: 13px;
        }
      `
       this.customStyle += `
        .lf-form__content form .lf-form__step .lf-form__step__item .lf-form-likert-smileys .lf-form-smiley-wrapper:active .lf-form-smiley-text,
         .lf-form__content form .lf-form__step .lf-form__step__item .lf-form-likert-smileys .lf-form-smiley-wrapper:focus-within .lf-form-smiley-text  {
         color: ${this.theme.ui_elements.scale.active_style.smileys_active_text_color} !important;
        }
      `
    },
    questionDescriptionStyle: function () {
      this.customStyle += `
        .lf-form__content form .lf-form__step .lf-form__step__item .lf-form__question__description {
          color: ${this.theme.typography.question_description.font.color} !important;
          font-family: ${this.filterFontFamily(this.theme.typography.question_description.font.family)} !important;
          font-size: ${this.theme.typography.question_description.font.size}px !important;
          font-weight: ${this.theme.typography.question_description.font.weight} !important;
          line-height: ${this.theme.typography.question_description.font.line_height}px !important;
          text-align: ${this.theme.typography.question_description.text_align} !important;
        }
      `
    },
    bodyStyle: function () {
      if (this.insideIframe) {
       return
      }

      this.customStyle += `
        body {
          background-color: ${this.theme.ui_elements.background.color};
        }
      `
    },
    gdprStyle: function () {
      this.customStyle += `
        .lf-form__gdpr-question label {
          font-family: ${this.filterFontFamily(this.theme.general.font.family)} !important;
          color: ${this.theme.general.colors.text_color} !important;
        }
      `
      this.customStyle += `
        .lf-form__content form .lf-form__gdpr-terms p {
           text-align: justify !important;
        }
      `
    },
    reviewSummaryStyle: function () {
      this.customStyle += `
        .titleStyle {
          color: ${this.theme.typography.question_title.font.color} !important;
          font-family: ${this.filterFontFamily(this.theme.typography.question_title.font.family)} !important;
        }
      `
      this.customStyle += `
        .lf-form form .lf-form__summary .lf-center-align {
          color: ${this.theme.typography.question_title.font.color} !important;
          font-family: ${this.filterFontFamily(this.theme.typography.question_title.font.family)} !important;
        }
      `
      this.customStyle += `
        .lf-form form .lf-form__summary .lf-form__step-summary .lf-form__question-summary > div {
          color: ${this.theme.general.colors.active_color} !important;
          font-family: ${this.filterFontFamily(this.theme.general.font.family)} !important;
          border-bottom: 1.5px solid ${this.theme.general.colors.active_color} !important;
          background-color: #fff;
        }
      `
    },
    sliderScaleStyle: function () {
      // scale
      this.customStyle += `.lf-form__content form .lf-form__step .lf-form__step__item .slider .ui-slider__track-background {
        height: 8px !important;
        border-radius: 5px !important;
        box-shadow: inset 0 1px 2px rgb(0 0 0 / 10%) !important;
        border: 1px solid ${this.theme.ui_elements.scale.border_color} !important;
        background-color: ${this.theme.ui_elements.scale.config.stroke_color} !important;
      }`
      this.customStyle += `.lf-form__content form .lf-form__step .lf-form__step__item .slider .ui-slider__track-fill {
        height: 9px !important;
        border-radius: 5px !important;
        background: ${this.theme.ui_elements.scale.config.fill_color};
        box-shadow: inset 0 1px 2px rgb(0 0 0 / 10%);
      }`
      this.customStyle += `.lf-form__content form .lf-form__step .lf-form__step__item .slider .ui-slider__thumb {
       box-shadow: rgb(0 0 0 / 22%) 0px 0px 4px 0px !important;
       border: 10px solid ${this.theme.ui_elements.scale.selector_color} !important;
       margin-top: 5px !important;
       height: 17px !important;
       width: 17px !important;
       background-color: #ffffff;
      }`
      this.customStyle += `.lf-form__content form .lf-form__step .lf-form__step__item .slider .ui-slider__thumb::before {
       background-color: #f9f9f900 !important;
      }`
      this.customStyle += `.lf-form__content form .lf-form__step .lf-form__step__item .lf-form-range-slider-wrapper .slider > .track {
       background-color: ${this.theme.ui_elements.scale.config.stroke_color} !important;
       border: 1px solid ${this.theme.ui_elements.scale.border_color} !important;
      }`
      this.customStyle += `.lf-form__content form .lf-form__step .lf-form__step__item .lf-form-range-slider-wrapper .slider > .range {
       background-color: ${this.theme.ui_elements.scale.config.fill_color} !important;
      }`
      this.customStyle += `.lf-form__content form .lf-form__step .lf-form__step__item .lf-form-range-slider-wrapper .slider > .thumb {
        border: 9px solid ${this.theme.ui_elements.scale.selector_color} !important;
      }`

    },
    progressStyle: function () {
      if (this.theme.ui_elements.step_progress.progressPosition === this.progressPositionIds.TOP) {
        this.customStyle += `
          .lf-form__progress {
            border-radius: ${this.theme.ui_elements.background.form_border_radius}px ${this.theme.ui_elements.background.form_border_radius}px 0 0;
            background-color: ${this.theme.ui_elements.step_progress.config.stroke_color};
          }
        `
      }
      if (this.theme.ui_elements.step_progress.progressPosition === this.progressPositionIds.BOTTOM) {
        this.customStyle += `
          .lf-form__progress {
            border-radius: 0 0 ${this.theme.ui_elements.background.form_border_radius}px ${this.theme.ui_elements.background.form_border_radius}px;
            background-color: ${this.theme.ui_elements.step_progress.config.stroke_color};
          }
        `
      }
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
    skinLayoutClass: function (skin) {
      if (!skin || !skin.layout) {
        return ''
      }
      return ' skin-layout-' + skin.layout
    },
    skinColumnClass: function (skin) {
      if (!skin || !skin.column) {
        return ''
      }
      if (skin.id !== 'image' && skin.id !== 'icon') {
        return ''
      }
      return ' skin-columns-' + skin.column
    },
    scrollToTop: function () {
      let $formWrapper = document.getElementById('leadgen-form-wrap-' + this.form.key)
      let $form = document.getElementById('leadgenform_' + this.form.key)
      let element = $formWrapper || $form

      if (this.insideIframe) {
        window.parent.postMessage({
          action: this.postMessage.prefix + 'scrolltop',
          top: this.postMessage.iframeViewportTop,
          formKey: this.formKey
        }, this.postMessage.parentOrigin || '*')

        return
      }

      let $element = document.querySelector('#' + element.id)

      if ($element.getBoundingClientRect().top >= 0) {
        return
      }

      window.scrollTo({
        top: window.pageYOffset + element.getBoundingClientRect().top - 30,
        behavior: 'smooth'
      })
    },
    certify: function () {
      cscertify('send', { form_id: 'leadgenform_' + this.formKey })
        .then(function (claimUrl) {
          this.saveLead(claimUrl)
        }.bind(this))
        .catch(function (err) {
          console.error('Page script: There was an error!', err)
      });
    },
    goNextStep: function () {
      this.saveVisitInteractedAt()

      let self = this
      this.validateForm(this.formScope).then((result) => {
        this.validation.showErrors = true
        let invalid = false

        // Custom phone number validation.
        if (this.step) {
          for (let question of this.step.questions) {
            if (question.type === 'PHONE_NUMBER') {
              if (question.value === "" && question.required === false){
                invalid = false
                break
              }
              let count = 0
              if (!this.validatePhoneNumber(question)) {
                count++
              }

              if (count > 0) {
                invalid = true
                break
              }
            }
          }
        }

        if (invalid) {
          return
        }

        if (result) {
          this.getNextStep = this.computeNextStep(this.form)

          if (!(this.currentStep === 0 && this.stepCount === 1)) {
            this.stepHistory.push(this.currentStep)
          }

          if (this.getNextStep === -1) {
            this.submitForm()
          } else {
            if (this.partialLead) {
              if (this.consentType === 'expressed' && this.consentChecked) {
                this.savePartialLead()
              }
              if (this.consentType === 'informed') {
                this.savePartialLead()
              }
            }
            // this.savePartialLead()
            this.scrollToTop()
            this.currentStep = this.getNextStep
            this.validation.showErrors = false
            this.fireTrackingEvent(trackingEvents.STEP_COMPLETED)
            this.fireTrackingEvent(trackingEvents.STEP_CHANGED)
            this.ga4CustomEvent(`${this.form.formTitle} | Step ${this.currentStep} | Passed`, {
              event_label: 'Step ' + this.currentStep
            })
            if (this.form.currentExperiment) {
              this.ga4CustomEvent(`${this.form.formTitle} | ${this.form.currentExperiment.title} | Step ${this.currentStep} | Passed`, {
                event_label: 'Step ' + this.currentStep
              })
            }
          }

          this.getNextStep = this.computeNextStep(this.form)

          if (this.isStepAutoNavigable) {
            this.resetStepQuestions()
          }

          setTimeout(function () {
            self.$validator.reset(this.formScope)
          }, 10)
        }
        setTimeout(this.updateFormHeightPostMessage, 10)
        setTimeout(this.initializePhoneNumberQuestions, 10)
        setTimeout(this.initializeAddressQuestions, 10)
        setTimeout(this.initializeDateMask, 10)
      })
    },
    goPrevStep: function () {
      this.saveVisitInteractedAt()

      if (this.stepHistory.length > 0) {
        this.scrollToTop()
        this.currentStep = this.stepHistory.pop()
        this.fireTrackingEvent(trackingEvents.STEP_CHANGED)
        this.getNextStep = this.computeNextStep(this.form)

        setTimeout(this.updateFormHeightPostMessage, 10)
        setTimeout(this.initializePhoneNumberQuestions, 10)
        setTimeout(this.initializeAddressQuestions, 10)
        setTimeout(this.initializeDateMask, 10)

        if (this.isStepAutoNavigable) {
          this.resetStepQuestions()
        }
      }
    },
    submitForm: function () {
      this.formSending = true
      this.responseErrMsg = ''

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
          } else if (this.contactStates.length > 0 && this.isEnable) {
            this.certify()
          } else {
            this.saveLead()
          }
        } else {
          this.formSending = false
        }
      })
    },
    validateForm: function (scope) {
      return this.$validator.validateAll(scope)
    },
    checkErr: function (field, rule) {
      return this.errors.firstByRule(field, rule, this.formScope)
    },
    validateObject: function (q, sindex, option) {
      if (this.currentStep !== sindex) {
        return {}
      }
      let obj = {}

      if (q.type !== 'ADDRESS' && q.required) {
        obj.required = true
      }

      if (
        q.type === 'ADDRESS' &&
        q.skin.id === this.addressSkinIds.DEFAULT &&
        option.required
      ) {
        obj.required = true
      } else if (
        q.type === 'ADDRESS' &&
        q.skin.id === this.addressSkinIds.GOOGLE_AUTOCOMPLETE &&
        q.autocompleteMode === addressAutocompleteModesIds.SEARCH &&
        !q.autocompleteFieldsEdit &&
        q.required
      ) {
        obj.required = true
      } else if (
        q.type === 'ADDRESS' &&
        q.skin.id === this.addressSkinIds.GOOGLE_AUTOCOMPLETE &&
        q.autocompleteMode === addressAutocompleteModesIds.SEARCH &&
        q.autocompleteFieldsEdit &&
        option?.required
      ) {
        obj.required = true
      } else if (
        q.type === 'ADDRESS' &&
        q.skin.id === this.addressSkinIds.GOOGLE_AUTOCOMPLETE &&
        q.autocompleteMode === addressAutocompleteModesIds.MANUAL &&
        option?.required
      ) {
        obj.required = true
      } else if (
        q.type === 'ADDRESS' &&
        q.skin.id === this.addressSkinIds.GOOGLE_AUTOCOMPLETE &&
        q.autocompleteMode === addressAutocompleteModesIds.MANUAL &&
        Object.values(q.fields).map((f) => f.required).indexOf(true) >= 0 &&
        !this.addressFieldsVisibilty[q.id]
      ) {
        obj.required = true
        obj.expression = {value: this.hasRequiredAddressFieldsFilled(q)}
      }

      if (q.type === 'EMAIL_ADDRESS') {
        obj.email = true
      }
      if (q.type === 'NUMBER' && q.enableMinMaxLimit) {
        obj.numberLimit = [q]
      }
      if (q.type === 'NUMBER' || q.enableMinMaxLimit) {
        obj.numeric = true
      }
      if (q.type === 'URL') {
        obj.url = true
      }
      if (q.type === 'EMAIL_ADDRESS' && q.restrictEmail === 'true') {
        obj.validateEmailDomain = [q]
      }

      if (q.type === 'DATE' && q.skin.id === this.dateSkinIds.ONE_INPUT_BOX) {
        obj.date_format = 'dd/MM/yyyy'
      }
      if (q.type === 'DATE' && q.skin.id === this.dateSkinIds.THREE_INPUT_BOXES) {
        obj.date = [option,q]
      }

      if (q.type === 'GDPR') {
        obj.gdpr = [q]
      }
      return obj
    },
    betweenValidateString: function (q, sindex, min, max) {
      if (this.currentStep !== sindex) {
        return {}
      }

      return 'between: ' + min + ',' + max
    },
    fetchFormState: function () {
      if (this.fetchingState) {
        return
      }
      if (!this.formKey) {
        return
      }

      let apiUrl = `API_URL/forms/key/${this.formKey}`
      if (this.isPreviewMode) {
        apiUrl = `API_URL/forms/${this.formKey}/variants/${this.formVariantId}/preview`
      }

      const apiPayload = {
        'leadgen_visitor_id': Vue.ls.get('visitor_id'),
        'source_url': this.insideIframe ? this.postMessage.parentUrl : window.location.href
      }

      let apiHeaders = { 'Authorization': 'Bearer null' }
      if (this.isPreviewMode) {
        apiHeaders = { 'Authorization': `Bearer ${this.jwtToken}` }
      }

      this.fetchingState = true

      Vue.http.post(
        apiUrl,
        apiPayload,
        { emulateJSON: true, headers: apiHeaders })
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
                question.value = ''
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
          if (this.form.formConnections) {
            this.contactStates = this.form.formConnections
            for (let cs of this.contactStates) {
              if (cs.enable) {
                this.isEnable = cs.enable === 0 ? false : true
              }
            }
          }
          if (this.form.formPartialLead) {
            if (this.form.formPartialLead[0]) {
              this.consentType = this.form.formPartialLead[0].consent_type || 'informed';
              this.partialLead = this.form.formPartialLead[0].enabled || false;
            } else {
              this.consentType = 'informed';
              this.partialLead = false;
            }
          }
          // pre select choice values
          this.preSelectChoiceValues(response.body.data.steps)

          if (response.body.data.steps[0].questions.length > 1 & this.isStepAutoNavigable) {
            for (let question of response.body.data.steps[0].questions) {
              if (question.type === 'SINGLE_CHOICE') {
                question.value = question.choices[0]
                break
              }
            }
          }
          for (let step of response.body.data.steps) {
            for (let question of step.questions) {
              if (question.type === 'RANGE') {
                if (question.skin.id === this.rangeSkinIds.LIKERT_SCALE) {
                  question.rangeFields.likertRadios = [];
                  const maxRadioLimit = question.rangeFields.maxScaleLimit || 5;
                  for (let i = 0; i < parseInt(maxRadioLimit); i++) {
                    question.rangeFields.likertRadios.push({ id: i + 1, label: i + 1 });
                  }
                } else if (question.skin.id === this.rangeSkinIds.RANGE_SCALE) {
                  question.value = `${question.rangeFields.valueMin} - ${question.rangeFields.valueMax}`;
                }
              }
            }
          }

          this.initializeGa4()
          this.initializeTheme()
          this.initalizeHiddenFields()

          if (this.hasStaticHeight) {
            setTimeout(this.initalizeFormHeight, 10)
          }

          if (this.theme.ui_elements.background.formShadow) {
            setTimeout(this.applyFormShadow, 10)
          }

        this.fireTrackingEvent(trackingEvents.LOADED)
        this.fireTrackingEvent(trackingEvents.TRUSTEDFORM)
      }, (response) => {
        this.fetchingState = false
        if (
          response.status === 400 &&
          response.body.meta.error_type === errorTypes.FORM_GEOLOCATION_FORBIDDEN
        ) {
          this.loading = false
          this.formGeolocationForbidden = true
          this.updateFormHeightPostMessage(0)
        } else if (response.status === 404) {
          this.errorMessage = 'Form key is invalid.'
        } else {
          this.errorMessage = 'Form load error.'
        }
      })
    },
    preSelectChoiceValues: function(steps) {
      for (let step of steps) {
        for (let question of step.questions) {
          if (question.enablePreSelectChoices === 'true') {
            if (question.type === 'SINGLE_CHOICE') {
              const selectedChoice = question.choices.find((ch) => ch.selected);
              if (selectedChoice) {
                question.value = selectedChoice
              }
          } else if (question.type === 'MULTIPLE_CHOICE') {
            const selectedChoices = question.choices.filter((ch) => ch.selected);
              const uniqueArray = this.removeDuplicatesById(selectedChoices)
              question.value = uniqueArray
            }
          }
        }
      }
    },
    removeDuplicatesById: function (arr) {
      const uniqueIds = {}
      const uniqueArr = arr.filter((item) => {
        if (!uniqueIds[item.id]) {
          uniqueIds[item.id] = true
          return true
        }
        return false
      })

      return uniqueArr
    },
    initializeTheme: function () {
      this.theme = this.form.formVariantTheme
      this.prepareTheme()
      this.styleForm()
      this.addStyle()

      this.themeLoaded = true

      setTimeout(this.updateFormHeightPostMessage, 10)
      setTimeout(this.initializePhoneNumberQuestions, 10)
      setTimeout(this.initializeAddressQuestions, 10)
      setTimeout(this.initializeDateMask, 10)
    },
    initalizeHiddenFields: function () {
      this.hiddenFields = this.form.formHiddenFields || []
      let url = new URL(window.location.href)

      if (this.insideIframe) {
        url = new URL(this.postMessage.parentUrl)
      }

      for (let hiddenField of this.hiddenFields) {
        if (
          url.searchParams.get(hiddenField.name) &&
          hiddenField.capture_from_url_parameter
        ) {
          hiddenField.default_value = url.searchParams.get(hiddenField.name)
        }
      }
    },
    initalizeFormHeight: function () {
      const steps = document.querySelectorAll('.lf-form__step__container')
      let maxHeight = -1

      for (let step of steps) {
        if (maxHeight < step.offsetHeight) {
          maxHeight = step.offsetHeight
        }
      }

      const formContent = document.querySelector(`.lf-form__content`)
      const formHeight = formContent.offsetHeight + maxHeight - document.querySelector('.lf-form__step__container').offsetHeight

      formContent.style.minHeight = `${formHeight}px`
    },
    initializeGa4: function () {
      if (!this.form.formSetting.tracking_ga4_property) {
        return
      }

      this.ga4.measurementId = this.form.formSetting.tracking_ga4_property
      window.dataLayer = window.dataLayer || []
      window.gtag = function () {dataLayer.push(arguments)}
      window.gtag('js', new Date())
      window.gtag('config', this.ga4.measurementId, {
        page_location: this.insideIframe ? this.postMessage.parentUrl : window.location.href,
        page_title: this.insideIframe ? this.postMessage.parentTitle : window.document.title,
        debug_mode: this.isProductionEnv
      })
    },
    ga4CustomEvent: function (action, data) {
      if (!this.form.formSetting.tracking_ga4_property || !window.gtag) {
        return
      }

      let eventData = {
        event_category: this.formKey,
        event_label: this.form.formTitle,
        send_to: this.ga4.measurementId
      }

      if (data) {
        eventData = {
          ...eventData,
          ...data
        }
      }

      window.gtag('event', action, eventData)
    },
    applyFormShadow: function () {
      window.parent.postMessage({
        action: this.postMessage.prefix + 'formShadow',
        value: '0px 0px 10px grey',
        formKey: this.formKey
      }, this.postMessage.parentOrigin || '*')
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
      for (let i = 0; i < steps.length; i++) {
        steps[i].skipped = this.stepHistory.indexOf(i) === -1;
      }

      if (this.partialLead && steps.length === 1) {
        steps[0].skipped = false;
      }

      if (!this.partialLead && !this.form.formSetting.steps_summary) {
        steps[steps.length - 1].skipped = false;
      }
    },

    savePartialLead: function () {
      let form = _.cloneDeep(this.form)

      delete form.svgIcons
      delete form.countries
      delete form.googleFonts
      delete form.errorTypes

      // Update DATE & PHONE_NUMBER question type value.
      for (let step of form.steps) {
        if (step.elements) {
          delete step.elements
        }

        for (let question of step.questions) {
          if (question.type === 'RANGE' && question.skin.id === this.rangeSkinIds.RANGE_SCALE) {
            question.value = `${question.rangeFields.valueMin} - ${question.rangeFields.valueMax}`
          } else if (question.type === 'DATE') {
            const dateSkinId = question.skin.id
            const dateSource = this.dateQuestionSources[question.id]

            if (dateSkinId === this.dateSkinIds.DROPDOWN && dateSource) {
              const { selectedDay, selectedMonth, selectedYear } = dateSource
              if (selectedDay && selectedMonth && selectedYear) {
                const monthIndex = this.dateQuestionSources.months.indexOf(selectedMonth)
                const month = monthIndex !== -1 ? monthIndex : 0
                const date = new Date(Date.UTC(selectedYear, month, selectedDay, 12, 0, 0))
                question.value = this.isValidDate(date) ? date.toISOString() : ''
              } else {
                question.value = ''
              }
            } else if (dateSkinId === this.dateSkinIds.ONE_INPUT_BOX) {
              if (question.value) {
                const [day, month, year] = question.value.split('/')
                const date = new Date(Date.UTC(parseInt(year, 10), parseInt(month, 10) - 1, parseInt(day, 10)))
                question.value = this.isValidDate(date) ? date.toISOString() : ''
              }
            } else if (dateSkinId === this.dateSkinIds.THREE_INPUT_BOXES && dateSource) {
              const { selectedDay, selectedMonth, selectedYear } = dateSource
              if (selectedDay && selectedMonth && selectedYear) {
                const date = new Date(Date.UTC(parseInt(selectedYear, 10), parseInt(selectedMonth, 10) - 1, parseInt(selectedDay, 10)));
                question.value = this.isValidDate(date) ? date.toISOString() : ''
              } else {
                question.value = ''
              }
            } else if (dateSkinId === this.dateSkinIds.DATE_PICKER) {
              question.value = question.value ? question.value.toISOString() : ''
            }
          } else if (question.type === 'PHONE_NUMBER') {
            let itiInstance = this.itiInstances[this.questionFieldName(question)]
            if (itiInstance) {
              question.value = itiInstance.getNumber(window.intlTelInputUtils.numberFormat.E164)
            }
          }
        }
      }
      this.markSkipped(form.steps)
      Vue.http.post('API_URL/partial-leads', {
        ...form,
        visitorId: Vue.ls.get('visitor_id'),
        formVisitId: this.formVisitId,
        previewMode: this.isPreviewMode,
        lead_id: this.lead_id
      }, { emulateJSON: true, headers: { 'Authorization': 'Bearer null' } })
        .then((response) => {
          this.lead_id = response.body.data.lead_id
        })
    },

    saveLead: function (claimUrl) {
      let form = _.cloneDeep(this.form)

      if (!this.recaptchaResponse && form.formSetting.enable_google_recaptcha) {
        this.formSending = false
        return
      }

      delete form.svgIcons
      delete form.countries
      delete form.googleFonts
      delete form.errorTypes

      // Update DATE & PHONE_NUMBER question type value.
      for (let step of form.steps) {
        if (step.elements) {
          delete step.elements
        }

        for (let question of step.questions) {
          if (question.type === 'RANGE' && question.skin.id === this.rangeSkinIds.RANGE_SCALE) {
            question.value = `${question.rangeFields.valueMin} - ${question.rangeFields.valueMax}`
          } else if (question.type === 'DATE') {
            const dateSkinId = question.skin.id
            const dateSource = this.dateQuestionSources[question.id]

            if (dateSkinId === this.dateSkinIds.DROPDOWN && dateSource) {
              const { selectedDay, selectedMonth, selectedYear } = dateSource
              if (selectedDay && selectedMonth && selectedYear) {
                const monthIndex = this.dateQuestionSources.months.indexOf(selectedMonth)
                const month = monthIndex !== -1 ? monthIndex : 0
                const date = new Date(Date.UTC(selectedYear, month, selectedDay, 12, 0, 0))
                question.value = this.isValidDate(date) ? date.toISOString() : ''
              } else {
                question.value = ''
              }
            } else if (dateSkinId === this.dateSkinIds.ONE_INPUT_BOX) {
              if (question.value) {
                const [day, month, year] = question.value.split('/')
                const date = new Date(Date.UTC(parseInt(year, 10), parseInt(month, 10) - 1, parseInt(day, 10)))
                question.value = this.isValidDate(date) ? date.toISOString() : ''
              }
            } else if (dateSkinId === this.dateSkinIds.THREE_INPUT_BOXES && dateSource) {
              const { selectedDay, selectedMonth, selectedYear } = dateSource
              if (selectedDay && selectedMonth && selectedYear) {
                const date = new Date(Date.UTC(parseInt(selectedYear, 10), parseInt(selectedMonth, 10) - 1, parseInt(selectedDay, 10)));
                question.value = this.isValidDate(date) ? date.toISOString() : ''
              } else {
                question.value = ''
              }
            } else if (dateSkinId === this.dateSkinIds.DATE_PICKER) {
              question.value = question.value ? question.value.toISOString() : ''
            }
          } else if (question.type === 'PHONE_NUMBER') {
            let itiInstance = this.itiInstances[this.questionFieldName(question)]
            if (itiInstance) {
              question.value = itiInstance.getNumber(window.intlTelInputUtils.numberFormat.E164);
            }
          }
        }
      }
      this.markSkipped(form.steps)

      const trustedFormCertUrlField = this.hiddenFields.find(hiddenField => hiddenField.name === 'xxTrustedFormCertUrl');
      if (form.formTrackingEvents.length !== 0) {
        const trustedForm = form.formTrackingEvents.find(obj => obj.title === 'TRUSTEDFORM')
        if (trustedForm && trustedForm.active && trustedForm.configured && trustedFormCertUrlField) {
          const element = document.getElementById('xxTrustedFormCertUrl_0');
          if (element) {
            for (let hiddenField of this.hiddenFields) {
              hiddenField.default_value = hiddenField === trustedFormCertUrlField ? element.value : hiddenField.default_value;
            }
          }
        }
      }

      form.hiddenFields = this.hiddenFields

      this.markSkipped(form.steps);
      form.hiddenFields = this.hiddenFields;

      this.fireTrackingEvent(trackingEvents.SUBMIT);
      this.ga4CustomEvent(`${this.form.formTitle} | Submit`);
      if (this.form.currentExperiment) {
        this.ga4CustomEvent(`${this.form.formTitle} | ${this.form.currentExperiment.title} | Submit`);
      }

      Vue.http.post('API_URL/leads', {
        ...form,
        visitorId: Vue.ls.get('visitor_id'),
        formVisitId: this.formVisitId,
        recaptchaResponse: this.recaptchaResponse,
        previewMode: this.isPreviewMode,
        calculatorTotal: this.calculatorTotal(),
        claim_url: !claimUrl ? '' : claimUrl,
        lead_id: this.isLastStep ? this.lead_id : null
      }, { emulateJSON: true, headers: { 'Authorization': 'Bearer null' } })
        .then((response) => {
          this.fireTrackingEvent(trackingEvents.SUBMIT_SUCCESS);
          this.resetRecaptchaResponse();
          this.formSending = false;
          this.scrollToTop();
          this.afterSubmitAction(form);
          setTimeout(this.updateFormHeightPostMessage, 10);
        })
        .catch((error) => {
          this.fireTrackingEvent(trackingEvents.SUBMIT_ERROR);
          this.resetRecaptchaResponse();
          if (error.status === 400 && error.body.meta.error_type === 'recaptcha_invalid_response') {
            this.responseErrMsg = 'Unable to verify reCAPTCHA. Please submit again.';
          } else if (error.status === 400 && error.body.meta.error_type === 'accept_responses_disabled') {
            this.responseErrMsg = 'Form submission is closed for now.';
          } else if (error.status === 400 && error.body.meta.error_type === 'response_limit_reached') {
            this.responseErrMsg = 'Too many submissions are not allowed.';
          } else if (error.status === 400 && error.body.meta.error_type === 'domain_not_allowed') {
            this.responseErrMsg = 'You\'re not allowed to submit the form on this domain.';
          } else if (error.status === 400 && error.body.meta.error_type === 'leads_limit_reached') {
            this.responseErrMsg = 'Form submission is closed for now.';
          } else {
            this.responseErrMsg = 'An error occurred during submission. Please try again.';
          }
          this.formSending = false;
          setTimeout(this.updateFormHeightPostMessage, 10);
        });
    },

    isValidDate: function (date) {
      // Check if the date is a valid instance of Date
      if (!(date instanceof Date)) {
        return false
      }

      // Check if the date object is valid
      return !isNaN(date.getTime())
    },
    formatDate: function (dateValue) {
      const inputDateValue = dateValue.split('/')
      const day = parseInt(inputDateValue[0], 10)
      // Adjust month to zero-based
      const month = parseInt(inputDateValue[1], 10) - 1
      const year = parseInt(inputDateValue[2], 10)
      const date = new Date(Date.UTC(year, month, day))

      if (this.isValidDate(date)) {
        return date.toISOString()
      } else {
        return ''
      }
    },

    afterSubmitAction: function (form) {
      if (form.formSetting.submit_action === formSubmitActions.URL.value) {
        if (this.insideIframe) {
          if (form.formSetting.post_data_to_url) {
            this.postFormDataPostMessage(this.getFormData(form), this.getThankyouUrl(form))
          }
          if (form.formSetting.thankyou_url) {
            this.postFormDataUrlPostMessage(this.getFormData(form), this.getThankyouUrl(form))
          } else {
            window.location.href = this.getThankyouUrl(form)
          }
        } else {
          if (form.formSetting.post_data_to_url) {
            this.postHiddenForm(this.getThankyouUrl(form), this.getFormData(form), 'post')
          } else {
            window.location.href = this.getThankyouUrl(form)
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
    getThankyouUrl: function (form) {
      let url = new URL(form.formSetting.thankyou_url)

      if (form.formSetting.append_data_to_url) {
        let formData = this.getFormData(form)

        for (let field of formData) {
          url.searchParams.append(field.name, field.value)
        }
      }

      return url.toString()
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
        // box-shadow: 0px 0px 10px grey'
        this.extracss += 'color:' + this.theme.general.text_color + ';fontFamily:' + this.theme.general.font.family + '!important;'
        this.summarycss = 'max-height: initial; overflow: auto'
        this.summarycss += 'color:' + this.theme.general.colors.text_color + ';fontFamily:' + this.theme.general.font.family
      } else {
        if (window.self === window.top) {
          // this.extracss = 'box-shadow: 0 0 10px grey;'
        }
      }
    },
    onFormSubmit: function () {
      this.saveVisitInteractedAt()
    },

    saveVisitInteractedAt: function () {
      if(this.isInteracted || this.savingVisitInteraction) {
        return
      }

      this.fireTrackingEvent(trackingEvents.INTERACTED)

      this.savingVisitInteraction = true

      this.ga4CustomEvent(`${this.form.formTitle} | Interacted`);
      if (this.form.currentExperiment) {
        this.ga4CustomEvent(`${this.form.formTitle} | ${this.form.currentExperiment.title} | Interacted`);
      }
      const apiUrl = 'API_URL/forms/' + this.formKey + '/visits/' + this.formVisitId + '/updateinteractiontime'
      Vue.http.put(apiUrl, {},{emulateJSON : true, headers: {'Authorization': 'Bearer null'}})
        .then((response) => {
          this.isInteracted = true
          this.savingVisitInteraction = false
        }, (response) => {
          this.savingVisitInteraction = false
        })
    },
    onFormEnterKey: function (e) {
      this.saveVisitInteractedAt()

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
            let val = 0
            if (question.value) {
              val = parseFloat(this.getChoiceValue(question, question.value))
            }
            let field = 'S' + sIndex + '_Q' + qIndex
            parsedFormula = parsedFormula.replace(new RegExp(field, 'g'), val)
          }
          if (question.type === 'MULTIPLE_CHOICE') {
            let val
            if (!question.value || question.value.length === 0) {
              val = 0
            } else {
              let sum = 0
              for (let v of question.value) {
                sum += parseFloat(this.getChoiceValue(question, v))
              }
              val = sum
            }
            let field = 'S' + sIndex + '_Q' + qIndex
            parsedFormula = parsedFormula.replace(new RegExp(field, 'g'), val)
          }
          qIndex++
        }
        sIndex++
      }
      return parsedFormula
    },
    calculatorTotal: function () {
      try {
        let val = this.parsedChoiceFormula()
        val = val ? eval(val) : ''
        return val
      } catch (e) {
        return 0
      }
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
    questionFieldName: function (question) {
      if (!question.field_name || !question.field_name.trim()) {
        return 'question' + question.id
      }
      return question.field_name
    },
    questionMinDate: function (question) {
      if (!question.enableMinMax) {
        return null
      }
      if (question.autoIncrement) {
        return this.currentDate
      }
      if (question.minDate) {
        return new Date(question.minDate)
      }
      return null
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
      for (let c of this.form.countries) {
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

      this.initializeAddressQuestion(question, country)
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
                // Handle over jump.
                if (jump.step >= this.stepCount && this.form.formSetting.steps_summary) {
                  return -1
                }

                if (jump.step > this.stepCount && !this.form.formSetting.steps_summary) {
                  return -1
                }

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
                // Handle over jump.
                if (jump.step >= this.stepCount && this.form.formSetting.steps_summary) {
                  return -1
                }

                if (jump.step > this.stepCount && !this.form.formSetting.steps_summary) {
                  return -1
                }

                return jump.step !== -1 ? jump.step - 1 : jump.step
              }
            }
          }
        }
      }

      return false
    },
     stepJumpFilter: function () {
      if (!this.step || !this.step.jump) {
        return false
      }

      if (this.step.jump.step === -1) {
        return -1
      }

      // Handle over jump.
      if (this.step.jump.step >= this.stepCount && this.form.formSetting.steps_summary) {
        return -1
      }

      if (this.step.jump.step > this.stepCount && !this.form.formSetting.steps_summary) {
        return -1
      }

      return this.step.jump.step - 1
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
    updateLikertSkinValue: function (value, question) {
      let obj = question.rangeFields.likertRadios.filter(c => c.id === value).pop()
      question.value = obj.label
    },
    updateSingleChoiceRadioOutlineSkinValue: function (value, question) {
      question.value = question.choices.filter(c => c.id === value).pop()
    },
    mapMultiSelectCheckboxOptions: function (choices) {
      return choices.map((c) => {
        return {label: c.label, value: c}
      })
    },
    filterCountries: function (properties) {
      return this.form.countries.filter((country) => {
        let matched = true
        for (let property of properties) {
          if (country[property.name] !== property.value) {
            return false
          }
        }
        return matched
      })
    },
    initializeAddressQuestions: function () {
      if (!this.step) {
        return
      }
      for (let i = 0; i < this.step.questions.length; i++) {
        if (this.step.questions[i].type !== 'ADDRESS') {
          continue
        }
        this.initializeAddressQuestion(this.step.questions[i])
      }
    },
    initializeAddressQuestion: function (question, country) {
      for (let field of this.addressFieldsFilter(question.fields)) {
        if (field.id !== 'country') {
          continue
        }
        let fieldName = this.addressFieldName(question, field)
        let itiInstance = this.itiInstances[fieldName]
        let countryCode
        if (itiInstance) {
          countryCode = itiInstance.getSelectedCountryData().iso2
          itiInstance.destroy()
        }
        if (country) {
          countryCode = this.filterCountries([{name: 'name', value: country}])[0].iso2
        }

        let input = document.getElementById(fieldName + '__hidden')
        if (!input) {
          continue
        }
        itiInstance = intlTelInput(input, {
          initialCountry: countryCode || this.visitorCountry.iso2,
          separateDialCode: false,
          utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.1.6/js/utils.js'
        })

        let countries = this.filterCountries([
          {name: 'iso2', value: countryCode || this.visitorCountry.iso2}
        ])

        if (countries.length > 0) {
          question.fields.country.value = countries[0].name
        }
        this.itiInstances[fieldName] = itiInstance
      }
    },
    initializePhoneNumberQuestions: function () {
      if (!this.form.geolocation) {
        return
      }
      if (!this.step) {
        return
      }
      for (let i = 0; i < this.step.questions.length; i++) {
        let question = this.step.questions[i]
        let questionName = this.questionFieldName(question)
        if (question.type !== 'PHONE_NUMBER') {
          continue
        }
        let itiInstance = this.itiInstances[questionName]
        let countryCode
        if (itiInstance) {
          countryCode = itiInstance.getSelectedCountryData().iso2
          itiInstance.destroy()
        }
        let input = document.querySelector(`#leadgen-form-wrap-${this.formKey} input[name="${questionName}"]`)
        if (!input) {
          continue
        }
        let defaultCountry
        if (question.enableDefaultCode) {
          defaultCountry = question.defaultCountryCode
        }
        let selectedCountryValue = countryCode ? countryCode : defaultCountry
        itiInstance = intlTelInput(input, {
          initialCountry: selectedCountryValue || this.visitorCountry.iso2,
          separateDialCode: true,
          utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.1.6/js/utils.js',
          customPlaceholder: () => ''
        })
        this.itiInstances[questionName] = itiInstance
        let validate = () => {
          if (!question.required && question.value === '') {
            question.valid = true
            return
          }
          question.valid = itiInstance.isValidNumber()
        }
        input.addEventListener('countrychange', validate)
        input.addEventListener('change', validate)
        input.addEventListener('keyup', validate)
      }
    },
    validatePhoneNumber: function (question) {
      let itiInstance = this.itiInstances[this.questionFieldName(question)]
      if (!itiInstance) {
        return true
      }
      return itiInstance.isValidNumber()
    },
    autoNavigate: function () {
      if (!this.isStepAutoNavigable) {
        return
      }

      for (let question of this.step.questions) {
        if (question.type === 'SINGLE_CHOICE') {
          if (!question.value) {
            return
          }
        }
      }

      this.goNextStep()
    },
    resetStepQuestions: function () {
      for (let question of this.step.questions) {
        this.resetQuestion(question)
      }
    },
    resetQuestion: function (question) {
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
    },
    contains: function (values, value) {
      if (!values || !value) {
        return false
      }
      return _.find(values, value) !== undefined
    },
    questionSummary(question) {
      if (!question.value) {
        if (question.type === 'RANGE' && question.skin.id === this.rangeSkinIds.SLIDER_SCALE) {
          return question.value ? question.value : '0';
        }
        return '---'
      }

      if (question.type === 'SINGLE_CHOICE') {
        return question.value.label
      }

      if (question.type === 'MULTIPLE_CHOICE') {
        return question.value.map((v) => v.label).join(', ')
      }

      if (question.type === 'DATE') {
        const dateSkinId = question.skin.id
        const dateSource = this.dateQuestionSources[question.id]
        const options = {
          weekday: "short",
          month: "short",
          day: "2-digit",
          year: "numeric",
        }

        if (dateSkinId === this.dateSkinIds.DROPDOWN && dateSource) {
          const { selectedDay, selectedMonth, selectedYear } = dateSource
          if (selectedDay && selectedMonth && selectedYear) {
            const monthIndex = this.dateQuestionSources.months.indexOf(selectedMonth)
            const month = monthIndex !== -1 ? monthIndex : 0
            const date = new Date(Date.UTC(selectedYear, month, selectedDay, 12, 0, 0))
            question.value = this.isValidDate(date) ? date.toLocaleString("en-US", options) : ''
          } else {
            question.value = ''
          }
        } else if (dateSkinId === this.dateSkinIds.THREE_INPUT_BOXES && dateSource) {
          const { selectedDay, selectedMonth, selectedYear } = dateSource
          if (selectedDay && selectedMonth && selectedYear) {
            const date = new Date(Date.UTC(parseInt(selectedYear, 10), parseInt(selectedMonth, 10) - 1, parseInt(selectedDay, 10)));
            question.value = this.isValidDate(date) ? date.toLocaleString("en-US", options) : ''
          } else {
            question.value = ''
          }
        } else if (dateSkinId === this.dateSkinIds.ONE_INPUT_BOX && dateSource) {
          const formattedDate = this.formatDate(question.value);
          const parsedDate = new Date(formattedDate);
          return parsedDate.toLocaleString("en-US", options);
        }

        return this.toDateString(question.value);
      }

      if (question.type === 'GDPR') {
        return question
          .value
          .map((option, index) => 'option' + (index + 1))
          .join(', ')
      }

      if (question.type === 'RANGE' && question.skin.id === this.rangeSkinIds.RANGE_SCALE) {
        return question.value = `${question.rangeFields.valueMin} - ${question.rangeFields.valueMax}`;
      }

      return question.value
    },
    getSvgIcon (icon) {
      if (!this.form.svgIcons[icon]) {
        return ''
      }

      return this.form.svgIcons[icon].svg
    },
    autocompleteAddressSearchOptions: function (question) {
      let result = this.autocompleteAddressResults[question.id];
      if (!result) {
        return []
      }

      return result.predictions.map((place) => {
        return {
          label: place.description,
          value: place.place_id
        }
      })
    },
    onAutocompleteAddressSelect: function (address, question) {
      let apiUrl = `API_URL/google/placeapi/${question.autocompleteApiKeyValue}/place/${address.value}`
      let apiHeaders = {'Authorization': 'Bearer null'}
      Vue.http.get(
        apiUrl,
        {emulateJSON : true, headers: apiHeaders})
        .then((response) => {
          const address = this.parseAutocompleteSelectedAddress(response.body)
          Vue.set(question.fields.address_line_1, 'value', address.address_line_1)
          Vue.set(question.fields.address_line_2, 'value', address.address_line_2)
          Vue.set(question.fields.landmark, 'value', address.landmark)
          Vue.set(question.fields.post_code, 'value', address.post_code)
          Vue.set(question.fields.city, 'value', address.city)
          Vue.set(question.fields.state, 'value', address.state)
          Vue.set(question.fields.country, 'value', address.country)
          this.initializeAddressQuestion(question, address.country)
        })
    },
    parseAutocompleteSelectedAddress: function (response) {
      const address = {
        address_line_1: '',
        address_line_2: '',
        landmark: '',
        post_code: '',
        city: '',
        state: '',
        country: ''
      }

      if (!response || !response.data || !response.data.result) {
        return address
      }

      const addressComponents = response.data.result.address_components

      if (!addressComponents) {
        return address
      }

      for (const addressComponent of addressComponents) {
        if (addressComponent.types.indexOf('street_number') >= 0) {
          address.address_line_2 = addressComponent.long_name
        }

        if (
          addressComponent.types.indexOf('route') >= 0 ||
          addressComponent.types.indexOf('street_address') >= 0
        ) {
          address.address_line_1 = addressComponent.long_name
        }

        if (addressComponent.types.indexOf('landmark') >= 0) {
          address.landmark = addressComponent.long_name
        }

        if (addressComponent.types.indexOf('postal_code') >= 0) {
          address.post_code = addressComponent.long_name
        }

        if (
          addressComponent.types.indexOf('locality') >= 0 ||
          addressComponent.types.indexOf('postal_town') >= 0
        ) {
          address.city = addressComponent.long_name
        }

        if (addressComponent.types.indexOf('administrative_area_level_1') >= 0) {
          address.state = addressComponent.long_name
        }

        if (addressComponent.types.indexOf('country') >= 0) {
          address.country = addressComponent.long_name
        }
      }

      return address
    },
    autocompleteAddress: function (query, question) {
      if (!query || !query.trim() || !question) {
        return
      }

      const self = this
      let fetchAutocompleteAddresses = function (query, question) {
        let apiUrl = `API_URL/google/placeapi/${question.autocompleteApiKeyValue}/search/${query}`
        let apiHeaders = {'Authorization': 'Bearer null'}

        Vue.http.get(
          apiUrl,
          {emulateJSON : true, headers: apiHeaders})
          .then((response) => {
            Vue.set(self.autocompleteAddressResults, question.id, response.body.data)
          })
      }

      fetchAutocompleteAddresses = _.debounce(fetchAutocompleteAddresses, 500)
      fetchAutocompleteAddresses(query, question)
    },
    showAddressFields: function(question) {
      if (this.addressFieldsVisibilty && this.addressFieldsVisibilty[question.id]) {
        return true
      }

      if (question.skin.id === addressSkinIds.DEFAULT) {
        return true
      }

      if (question.autocompleteMode === addressAutocompleteModesIds.MANUAL) {
        return false
      }

      return question.autocompleteFieldsEdit
    },
    setAddressFieldsVisiblity: function (question, visibility) {
      Vue.set(this.addressFieldsVisibilty, question.id, visibility)
    },
    findAutocompleteAddress: function (question) {
      Vue.set(this.findingAddress, question.id, true)
      setTimeout(() => {
        Vue.set(this.findingAddress, question.id, false)

        if (!question.value || !question.value.trim()) {
          return
        }

        const options = this.autocompleteAddressSearchOptions(question)
        let address = _.find(options, {description: question.value})

        if (!address && options.length > 0) {
          address = options[0]
        }

        if (!address) {
          this.setAddressFieldsVisiblity(question, true)
          setTimeout(() => {
            this.initializeAddressQuestion(question, '')
          }, 100)
        } else {
          this.onAutocompleteAddressSelect(address, question)
          this.setAddressFieldsVisiblity(question, true)
        }
      }, 2000)
    },
    enterAddressFieldsManually: function (question) {
      this.setAddressFieldsVisiblity(question, true)
      setTimeout(() => this.initializeAddressQuestion(question),)
    },
    hasRequiredAddressFieldsFilled: function (question) {
      for (let field in question.fields) {
        if (
          !question.fields[field].enabled ||
          !question.fields[field].required
        ) {
          continue
        }

        if (
            !question.fields[field].value ||
            !question.fields[field].value.trim()
        ) {
          return false
        }
      }

      return true
    },
    initalizeDateQuestion: function (question) {
      if (this.dateQuestionSources[question.id]) {
        return
      }
      let newObj = {
        selectedYear: '',
        selectedMonth: '',
        selectedDay: ''
      }
      for (let i = 0; i < this.form.steps.length; i++) {
         let step = this.form.steps[i]
         for (let j = 0; j < step.questions.length; j++) {
           if (step.questions[j].id === question.id) {
             Vue.set(this.dateQuestionSources, step.questions[j].id, newObj)
           }
         }
       }
    },
    updateDateInputBoxes: function (value, question, type) {
      this.initalizeDateQuestion(question)
      if (this.dateQuestionSources[question.id]) {
        if (type === 'day') {
          this.dateQuestionSources[question.id].selectedDay = value.toString()
        } else if (type === 'month') {
          this.dateQuestionSources[question.id].selectedMonth = value.toString()
        } else if (type === 'year') {
          this.dateQuestionSources[question.id].selectedYear = value.toString()
        }
        this.updateInputBoxValue(question)
      }
    },
    updateInputBoxValue: function (question) {
      if (this.dateQuestionSources[question.id]) {
        const { selectedDay, selectedMonth, selectedYear } = this.dateQuestionSources[question.id];
        const formattedDay = selectedDay.toString().padStart(2, '0');
        const formattedMonth = selectedMonth.toString().padStart(2, '0');
        const formattedYear = selectedYear.toString();
        const dateValue = `${formattedYear}-${formattedMonth}-${formattedDay}`;
        question.value = dateValue;
      }
    },
    setDaysValue: function (value, question) {
      if (this.dateQuestionSources[question.id]) {
        this.dateQuestionSources[question.id].selectedDay = value.toString();
        this.updateInputBoxValue(question);
      }
    },
    setDays: function (value) {
      this.dateQuestionSources.days = []
      let num
      if (!value && !this.monthIndex) {
        num = 31
      } else {
        num = new Date(value, this.monthIndex, 0).getDate();
      }
      for (let i = 1; i <= num; i++) {
        this.dateQuestionSources.days.push(i)
      }
    },
    setMonths: function (value, question) {
      this.monthIndex = this.dateQuestionSources.months.indexOf(value) + 1;
      if (this.dateQuestionSources[question.id]) {
        this.dateQuestionSources[question.id].selectedMonth = value;
        let year = this.dateQuestionSources[question.id].selectedYear;
        this.setDays(year);
        this.updateInputBoxValue(question);
      }
    },
    setYears: function (value, question) {
      if (value == undefined) {
        return;
      }
      let year = new Date().getFullYear();
      // Make the previous 100 years be an option
      for (let i = 0; i < 101; i++) {
        this.dateQuestionSources.years.push(year - i);
      }
      if (this.dateQuestionSources[question.id]) {
        this.dateQuestionSources[question.id].selectedYear = value.toString();
        this.setDays(this.dateQuestionSources[question.id].selectedYear);
        this.updateInputBoxValue(question);
      } else {
        this.initalizeDateQuestion(question);
      }
    },
    pickerOpened: function () {
      this.overflowProperty = 'inherit'
    },
    pickerClosed: function () {
       this.overflowProperty = 'hidden'
    },
  },
  computed : {
    isProductionEnv: function () {
      return this.form.environment === 'production';
    },
    websiteFontFamily: function () {
      if (this.insideIframe) {
        return this.postMessage.parentFont
      }
      return window.getComputedStyle( document.body, null ).getPropertyValue( 'font-family' )
   },
    warningColorStyle: function () {
      return {
        color: this.theme.general.colors.warning_color,
        fontFamily: this.theme.general.font.family
      }
    },
    backButtonStyle: function () {
      return {
        backgroundColor: this.theme.ui_elements.step_navigation.back_button.backgroundColor,
        color: this.theme.ui_elements.step_navigation.back_button.font.color,
        fontFamily: this.filterFontFamily(this.theme.ui_elements.step_navigation.back_button.font.family),
        fontSize: this.theme.ui_elements.step_navigation.back_button.font.size + 'px',
        fontWeight: this.theme.ui_elements.step_navigation.back_button.font.weight,
        height: this.theme.ui_elements.step_navigation.back_button.font.height + 'px !important',
        padding: '0px',
        width: '100%',
        borderRadius: this.theme.ui_elements.step_navigation.back_button.borderRadius + 'px',
        textTransform: 'none',
        border: this.theme.ui_elements.step_navigation.back_button.default_style.border.width + 'px' + ' ' + this.theme.ui_elements.step_navigation.back_button.default_style.border.style + ' ' + this.theme.ui_elements.step_navigation.back_button.default_style.border.color,
        boxSizing: 'border-box'
      }
    },
    nextButtonStyle: function () {
      return {
        backgroundColor: this.theme.ui_elements.step_navigation.next_button.backgroundColor,
        color: this.theme.ui_elements.step_navigation.next_button.font.color,
        fontFamily: this.filterFontFamily(this.theme.ui_elements.step_navigation.next_button.font.family),
        fontSize: this.theme.ui_elements.step_navigation.next_button.font.size + 'px',
        fontWeight: this.theme.ui_elements.step_navigation.next_button.font.weight,
        height: this.theme.ui_elements.step_navigation.next_button.font.height + 'px',
        padding: '0px',
        width: this.theme.ui_elements.step_navigation.next_button.width + '%',
        borderRadius: this.theme.ui_elements.step_navigation.next_button.borderRadius + 'px',
        textTransform: 'none',
        border: this.theme.ui_elements.step_navigation.next_button.default_style.border.width + 'px' + ' ' + this.theme.ui_elements.step_navigation.next_button.default_style.border.style + ' ' + this.theme.ui_elements.step_navigation.next_button.default_style.border.color,
        boxSizing: 'border-box'
      }
    },
    submitButtonStyle: function () {
      return {
        backgroundColor: this.theme.ui_elements.step_navigation.submit_button.backgroundColor,
        color: this.theme.ui_elements.step_navigation.submit_button.font.color,
        fontFamily: this.filterFontFamily(this.theme.ui_elements.step_navigation.submit_button.font.family),
        fontSize: this.theme.ui_elements.step_navigation.submit_button.font.size + 'px',
        fontWeight: this.theme.ui_elements.step_navigation.submit_button.font.weight,
        height: this.theme.ui_elements.step_navigation.submit_button.font.height + 'px !important',
        padding: '0px',
        width: '100%',
        borderRadius: this.theme.ui_elements.step_navigation.submit_button.borderRadius + 'px',
        textTransform: 'none',
        border: this.theme.ui_elements.step_navigation.submit_button.default_style.border.width + 'px' + ' ' + this.theme.ui_elements.step_navigation.submit_button.default_style.border.style + ' ' + this.theme.ui_elements.step_navigation.submit_button.default_style.border.color,
        boxSizing: 'border-box'
      }
    },
    findAddressBtnStyle: function () {
      return {
        backgroundColor: this.theme.ui_elements.step_navigation.next_button.backgroundColor,
        color: this.theme.ui_elements.step_navigation.next_button.font.color,
        fontFamily: this.theme.ui_elements.step_navigation.next_button.font.family,
        fontSize: this.theme.ui_elements.step_navigation.next_button.font.size + 'px',
        fontWeight: this.theme.ui_elements.step_navigation.next_button.font.weight,
        borderRadius: this.theme.ui_elements.step_navigation.next_button.borderRadius + 'px',
        textTransform: 'none',
        border: this.theme.ui_elements.step_navigation.next_button.default_style.border.width + 'px' + ' ' + this.theme.ui_elements.step_navigation.next_button.default_style.border.style + ' ' + this.theme.ui_elements.step_navigation.next_button.default_style.border.color,
        boxSizing: 'border-box',
        marginBottom: '10px',
        marginTop: '-10px'
      }
    },
    enterAddressManuallyTextStyle: function () {
      return {
        textDecoration: 'underline',
        marginBottom: '10px',
        cursor: 'pointer',
        color: this.theme.general.colors.text_color,
        fontFamily: this.theme.general.font.family
      }
    },
    progressStep: function () {
      return {
        width: this.stepProgress + '%',
        animation: this.theme.ui_elements.step_progress.showAnimation ? 'progress-bar-stripes 2s linear infinite' : 'none',
        backgroundColor: this.theme.ui_elements.step_progress.config.fill_color + '!important',
        transition: 'width .6s ease',
        height: '17px',
        backgroundImage: 'linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 25%) 50%, rgba(255, 255, 255, 25%) 75%, transparent 75%, transparent)',
        backgroundSize: '40px 40px'
      }
    },
    formStyle: function () {
      return {
        backgroundColor: this.theme.ui_elements.background.formColor,
        border: this.theme.ui_elements.background.form_border_width + 'px' + ' ' + this.theme.ui_elements.background.form_border_style + ' ' + this.theme.ui_elements.background.form_border_color,
        borderRadius: this.theme.ui_elements.background.form_border_radius + 'px',
        overflow: this.overflowProperty
      }
    },
    formPadding: function () {
      return {
        padding: this.theme.ui_elements.background.form_padding_top + 'px' + ' ' + this.theme.ui_elements.background.form_padding_right + 'px' + ' ' + this.theme.ui_elements.background.form_padding_bottom + 'px' + ' ' + this.theme.ui_elements.background.form_padding_left + 'px'
      }
    },
    progressWrapperStyle: function () {
      return {
        paddingRight: this.theme.ui_elements.background.form_border_radius / 2 + 'px',
        paddingLeft: this.theme.ui_elements.background.form_border_radius / 2 + 'px'
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
      let newCount = this.currentStep + 1
      let progress = parseInt((newCount / stepCount) * 100)
      return progress
    },
    isFooterVisible: function () {
      return this.form.formSetting.all_steps_footer || this.isLastStep
    },
    footerText: function () {
      return this.form.formSetting.footer_text
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
        val = isNaN(val) || !isFinite(val) ? '' : parseFloat(val).toFixed(2)
        if (this.form.formSetting.trim_trailing_zeros) {
          val = val.toString().replace(/(\.0+|\.0+$)/, '')
        }
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
    isValidStep: function () {
      if (!this.isStepAutoNavigable) {
        return true
      }

      for (let question of this.step.questions) {
        if (question.type === 'SINGLE_CHOICE') {
          if (!question.value && question.required) {
            return false
          }
        }
      }

      return true
    },
     continueButtonShow: function () {
     if (!this.isStepAutoNavigable) {
        return true
      }
       for (let question of this.step.questions) {
        if (question.type === 'SINGLE_CHOICE' && this.theme.ui_elements.step_navigation.next_button.hidden === false) {
          return true
        } else {
          return false
        }
      }
    },
    backBtnText: function() {
       return this.theme.ui_elements.step_navigation.back_button.text
    },
    continueBtnText: function () {
      if (this.getNextStep === -1) {
        return this.theme.ui_elements.step_navigation.submit_button.text || 'SUBMIT'
      }
      return this.theme.ui_elements.step_navigation.next_button.text
    },
    autocompleteCountryAddressField: function () {
      return this.form.countries.map((country) => {
        return country.name
      })
    },
    insideIframe: function () {
      let regex = `${this.formKey}/iframe`

      return window.location.href.search(regex, 'g') >= 0
    },
    visitorCountry: function () {
      if (!this.form || !this.form.geolocation) {
        return null
      }
      let country = this.form.countries
        .filter((country) => this.form.geolocation.geoplugin_countryCode === country.iso2)
        .pop()
      return country || null
    },
    isSubmitStep: function () {
      if (this.form.formSetting.steps_summary) {
        return this.form.steps.length === this.currentStep
      }

      return (this.form.steps.length - 1) === this.currentStep
    },
    isLastStep: function () {
      return (this.form.steps.length - 1) === this.currentStep
    },
    isStepAutoNavigable: function () {
      if (this.isSubmitStep) {
        return false
      }

      for (let question of this.step.questions) {
        if (question.type === 'GDPR' && !this.isLastStep) {
          continue
        }

        if (question.type === 'GDPR' && this.isLastStep) {
          return !question.enabled
        }

        if (question.type !== 'SINGLE_CHOICE') {
          return false
        }
      }

      return this.step.autoNavigation
    },
    hasStaticHeight: function () {
      return this.theme && !this.theme.general.dynamic_height
      return this.theme && !this.theme.general.dynamic_fadein
    },
    isPreviewMode: function () {
      return !isNaN(parseInt(this.formVariantId))
    },
    jwtToken: function () {
      const url = new URL(window.location.href)

      return url.searchParams.get('token')
    },
    // ADDRESS QUESTION
    addressSkinIds: function () {
      return addressSkinIds
    },
    addressAutocompleteModesIds: function () {
      return addressAutocompleteModesIds
    },
    // Date Skins
    dateSkinIds: function () {
      return dateSkinIds
    },
    threeInputBoxes: function () {
      return threeInputBoxes
    },
    dropdown: function () {
      return dropdown
    },
    rangeSkinIds: function () {
      return rangeSkinIds
    },
    // Progress Bar Positions
    progressPositionIds: function () {
      return progressPositionIds
    },
    smileysText: function () {
      return smileysText
    }
  },
  watch: {
    form: {
      handler: function (to, from) {
        this.getNextStep = this.computeNextStep(to)

        setTimeout(() => this.autoNavigate(), 10)
      },
      deep: true
    }
  }
}
</script>

<style lang="scss">
@import '~intl-tel-input/build/css/intlTelInput.min.css';

/* mixins */
@mixin alignments() {
  &.alignment-horizontal {
    flex-direction: row;
    display: flex;
    flex-wrap: wrap;
    > label, > div, > li {
      margin-right: 15px;
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
      margin-right: 15px;
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
  &.alignment-vertical_center {
    flex-direction: column;
    align-items: center;
    > label, > div {
      margin-right: 0;
      margin-left: 0;
      margin-bottom: 15px;
      width: 120px;
    }
    > li {
      margin-right: 0;
      margin-left: 0;
      margin-bottom: 15px;
      width: 60%;
    }
    > label:last-child, > div:last-child, > li:last-child {
      margin-right: 0;
    }
  }
}

/* Date picker fix */
body > .drop.drop-element {
  z-index: 10000;
}

/* Dropdown Tippy topper fix */
body > .tippy-popper {
  z-index: 110;
}

/* Form styles */
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

  .lf-form__thankyou, .lf-form__footer {
    &__msg {
      padding: 30px 15px;
      font-weight: 300;
      font-size: 18px;

      p {
        line-height: 30px;
      }

      .ql-align-justify {
        text-align: justify;
      }

      .ql-align-center {
        text-align: center;
      }

      .ql-align-right {
        text-align: right;
      }
    }
  }

  label {
    font-size: 16px;
  }

  &__content {
    form {
      position: relative;
      z-index: 1;
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
          .lf-form__question {
            &:after {
              content: "";
              clear: both;
              display: block;
            }
            &__inputfield {
              > label {
                margin-bottom: 15px  !important;
                display: block;
                font-weight: 500;
                font-size: 18px;
              }

              .ui-select__search {
                overflow: hidden;
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
              display: flex;
              flex-direction: column;
              span {
                font-size: 14px;
                padding-bottom:15px;
              }
              span + span {
                margin-top: 5px;
              }
            }
            &__error {
              margin-bottom: 5px;
              span {
                font-size: 14px;
              }
            }
            &[data-question-type="ADDRESS"] {
              div[data-field-id="country"] {
                .intl-tel-input,
                .iti {
                  position: absolute;
                  padding: 0;
                  pointer-events: none;
                  input {
                    display: none;
                  }
                }
              }
            }
          }
          /* GDPR */
          .lf-form__gdpr-question {
            .ui-checkbox__label-text p {
              margin: 0;
            }
          }
          // Sinle / Multi Select
          .lf-form__single-select-question {
            .lf-form__radio-list {
              display: flex;
              flex-direction: column;
              flex-wrap: wrap;
              margin-bottom: 20px;
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
            .lf-form__radio_outline-list {
            display: flex;
              flex-direction: column;
              flex-wrap: wrap;
              margin-bottom: 40px;
              @include alignments();
              }
          }
          .lf-form__multi-select-question {
            .lf-form__radio-list {
              display: flex;
              flex-direction: column;
              flex-wrap: wrap;
              margin-bottom: 20px;
              @include alignments();
            }
            .lf-form__checkbox-options {
              .ui-checkbox-group__checkboxes {
                display: flex;
                flex-direction: column;
                flex-wrap: wrap;
                margin-bottom: 20px;
              }
              &.alignment-horizontal {
                .ui-checkbox-group__checkboxes {
                  flex-direction: row;
                  label {
                    margin-right: 15px;
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
                    margin-right: 15px;
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
              &.alignment-vertical_center {
                .ui-checkbox-group__checkboxes {
                  flex-direction: column;
                  align-items: center;
                  label {
                    margin-right: 0;
                    margin-left: 0;
                    margin-bottom: 15px;
                    width: 120px;
                  }
                  label:last-child {
                    margin-right: 0;
                  }
                }
              }
            }
          }
          .lf-form__single-select-question,
          .lf-form__multi-select-question {
            .lf-form__image-options {
              display: flex;
              flex-direction: column;
              flex-wrap: wrap;
              margin-bottom: 25px;
              @include alignments();
              &__item {
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
                align-items: center;
                text-align: center;
                box-sizing: border-box;
                position: relative;
                margin: 0 0 20px 0;
                z-index: 0;
                &__image {
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  width: 100%;
                  height: 100%;
                  img {
                    display: none;
                    max-width: 100%;
                    max-height: 100%;
                  }
                }
                &__label {
                  display: block;
                  font-size: 20px;
                  font-weight: bold;
                  margin-bottom: 15px;
                  word-break: break-word;
                }
                &__desc {
                  display: block;
                  font-size: 14px;
                  line-height: 22px;
                  word-break: break-word;
                }
                input[type="radio"],
                input[type="checkbox"]{
                  position: absolute !important;
                  z-index: 59;
                  left: 0;
                  right: 0;
                  top: 0;
                  bottom: 0;
                  width: 100%;
                  height: 100%;
                  opacity: 0.01;
                  cursor: pointer;
                }
                &__checked {
                  display: none;
                  position: absolute;
                  top: 3px;
                  right: 3px;
                  z-index: 100;
                  svg {
                    width: 16px;
                    height: 16px;
                    background: white;
                    border-radius: 50%;
                  }
                }
              }
            }
            .lf-form__icon-options {
              display: flex;
              flex-direction: column;
              flex-wrap: wrap;
              margin-bottom: 25px;
              @include alignments();
              &__item {
                display: block;
                flex-direction: column;
                justify-content: space-around;
                align-items: center;
                text-align: center;
                box-sizing: border-box;
                max-width: 300px;
                position: relative;
                margin: 0 0 20px 0;
                z-index: 0;
                &__icon {
                  font-size: 50px;
                  margin-bottom:  20px;
                }
                &__label {
                  display: block;
                  font-size: 20px;
                  font-weight: bold;
                  margin-bottom: 15px;
                  word-break: break-word;
                }
                &__desc {
                  display: block;
                  font-size: 14px;
                  line-height: 22px;
                  word-break: break-word;
                }
                input[type="radio"],
                input[type="checkbox"]{
                  position: absolute !important;
                  z-index: 59;
                  left: 0;
                  right: 0;
                  top: 0;
                  bottom: 0;
                  width: 100%;
                  height: 100%;
                  opacity: 0.01;
                  cursor: pointer;
                }
                &__checked {
                  display: none;
                  position: absolute;
                  top: 3px;
                  right: 3px;
                  z-index: 100;
                  svg {
                    width: 16px;
                    height: 16px;
                    background: white;
                    border-radius: 50%;
                  }
                }
              }
            }
            .lf-form__image-options,
            .lf-form__icon-options {
              &__column {
                margin-bottom: 0 !important;
              }
              &.skin-columns-4 {
                .lf-form__image-options__column,
                .lf-form__icon-options__column {
                  width: calc( (100% - 45px) / 4 );
                  display: inline-grid;
                  &:nth-child(4n+4) {
                    margin-right: 0;
                  }
                }
              }
              &.skin-columns-3 {
                .lf-form__image-options__column,
                .lf-form__icon-options__column{
                  width: calc( (100% - 30px) / 3 );
                  display: inline-grid;
                  &:nth-child(3n+3) {
                    margin-right: 0;
                  }
                }
              }
              &.skin-columns-2 {
                .lf-form__image-options__column,
                .lf-form__icon-options__column {
                  width: calc( (100% - 15px) / 2 );
                  display: inline-grid;
                  &:nth-child(2n+2) {
                    margin-right: 0;
                  }
                }
              }
              &.skin-columns-1 {
                .lf-form__image-options__column,
                .lf-form__icon-options__column {
                  width: calc( (58% - 15px) / 1 );
                  display: inline-grid;
                  &:nth-child(2n+2) {
                    // margin-right: 0;
                  }
                }
              }
            }
          }
          .lf-form__single-select-question.skin-layout-boxed_content,
          .lf-form__multi-select-question.skin-layout-boxed_content {
            .lf-form__image-options {
              &__item {
                padding: 15px;
                border: 1px solid transparent;
                border-radius: 5px;
                background: white;
                // box-shadow: 0 0 5px grey;
                &__image {
                  margin-bottom:  20px;
                  background-image: none !important;
                  img {
                    display: block;
                    max-height: 100px;
                  }
                }
              }
            }
            .lf-form__icon-options {
              &__item {
                padding: 15px;
                border: 1px solid transparent;
                border-radius: 5px;
                background: white;
                // box-shadow: 0 0 5px grey;
                &__icon {
                  margin-bottom:  20px;
                  img {
                    display: block;
                    max-height: 100px;
                  }
                }
              }
            }
          }
          .lf-form__single-select-question.skin-layout-outer_content,
          .lf-form__multi-select-question.skin-layout-outer_content {
            .lf-form__image-options {
              &__item {
                margin-bottom: 15px !important;
                &__image {
                  background-image: none !important;
                  img {
                    display: block;
                  }
                }
                &__image-wrapper {
                  border-radius: 5px;
                  background: white;
                  margin-bottom: 20px;
                  width: 100%;
                  height: 150px;
                  padding: 15px;
                  box-sizing: border-box;
                  border: 1px solid transparent;
                }
              }
            }
            .lf-form__icon-options {
              &__item {
                margin-bottom: 25px !important;
                &__icon {
                  margin-bottom: 0;
                }
                &__icon-wrapper {
                  display: flex;
                  justify-content: center;
                  align-items:center;
                  border-radius: 5px;
                  background: white;
                  margin-bottom: 20px;
                  width: 100%;
                  height: 120px;
                  padding: 15px;
                  box-sizing: border-box;
                  border: 1px solid transparent;
                }
              }
            }
          }
          .lf-form__single-select-question.skin-layout-inner_content,
          .lf-form__multi-select-question.skin-layout-inner_content  {
            .lf-form__image-options {
              &__item {
                position: relative;
                height: 150px;
                border: 1px solid transparent;

                &__image-wrapper {
                  position: absolute;
                  top: 0;
                  left: 0;
                  right: 0;
                  bottom: 0;
                  padding: 0;
                  border: 1px solid transparent;
                  border-radius: 5px;
                  background: white;
                }
                &__image {
                  background-size: cover;
                  background-repeat: no-repeat;
                  object-fit: cover;
                  background-position: center center;
                }
                &__content-wrapper {
                  position: absolute;
                  top: 0;
                  left: 0;
                  right: 0;
                  bottom: 0;
                  z-index: 1;
                  display: flex;
                  flex-direction: column;
                  justify-content: center;
                  align-items: center;
                }
              }
            }
          }
        }
        .lf-form__question {
          .lf-form__question__date-skin {
            display: flex;
            justify-content: space-evenly;
            padding-bottom: 20px;
            .date-input-box {
              padding: 2px;
              width: 28%;
            }
            .single-date-input__box {
              padding: 2px;
              width: 92%;
              letter-spacing: 2px;
              border: none;
              outline: none;
            }
          }
        }
      }
      .lf-form__step-nav {
        position: relative;
        z-index: -1;
        margin-top: 20px;
        display: flex;
        button {
          height: 40px;
          cursor: pointer;
        }
        .lf-form__first-step-nav {
          display: flex;
            width: 100% !important;
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
          width:100%;
          .back_button_wrapper, .continue_button_wrapper, .submit_button_wrapper {
            display: flex;
          }
        }
        .lf-form__last-step-nav {
          display: flex;
          flex-wrap: wrap;
          justify-content: center;
          width: 100%;
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
        opacity: 0.002 !important;
      }
      .lf-form__radio-list {
        list-style-type:none;
        text-align: center;
        padding: 0;
        li {
          position: relative;
          float: none;
          text-align: left;
          margin: 0px 5px 5px 0px;
          input[type="radio"],
          input[type="checkbox"]{
            position: absolute !important;
            z-index: 59;
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
            line-height: 20px;
            font-size: 14px;
            word-break: keep-all;
            cursor:pointer;
            z-index:90;
            &:before, &:after {
              display: none !important;
            }
          }
        }
      }
       .lf-form__radio_outline-list {
        list-style-type:none;
        text-align: center;
        padding: 0;
        li {
          position: relative;
          float: none;
          text-align: left;
          margin: 0px 5px 5px 0px;
          z-index: 1;
          input[type="radio"],
          input[type="checkbox"]{
            position: absolute !important;
            z-index: 59;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            width: 100%;
            height: 90%;
            opacity: 0.01;
            cursor: pointer;
          }
          label {
            padding:4px 5px;
            display: flex;
            height: auto;
            line-height: 20px;
            font-size: 14px;
            word-break: keep-all;
            cursor:pointer;
            z-index:90;
            &:before, &:after {
              display: none !important;
            }
          }
          .ui-radio--width {
            width: 450px;
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
      .lf-form-scale-counter-wrapper {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
        .lf-form-scale-counter {
          border: 1px solid grey;
          display: flex;
          padding: 13px;
          border-radius: 5px;
        }
        input[type="text"] {
            font-weight:bold;
            font-size:20px;
            border: none;
            background-color: transparent;
        }
        .unit-left{
          font-weight:bold;
          font-size:20px;
          padding-left: 5px;
        }
        .unit-right{
          font-weight:bold;
          font-size:20px;
        }
      }
      .lf-form-range-counter-wrapper {
        display: flex;
        justify-content: center;
        .lf-form-range-counter {
          border: 1px solid grey;
          display: flex;
          padding: 17px;
          border-radius: 5px;
          margin-bottom: 29px;

          .lf-form-dash {
            font-weight: bold;
            margin-left: 14px;
            margin-right: 14px;
           }
           .unit-left{
          font-weight:bold;
          font-size:20px;
          padding-left: 5px;
        }
        .unit-right{
          font-weight:bold;
          font-size:20px;
        }
        .scale-min-value{
          font-weight:bold;
        }
          }
          input[type="text"] {
            font-weight:bold;
            font-size:20px;
            border: none;
            background-color: transparent;
           }
        }
      .lf-form-range-slider-wrapper {
        position: relative;
        margin-bottom: 33px;
        .slider {
        position: relative;
        z-index: 1;
        height: 10px;
        margin: 0 15px;
      }
      .slider > .track {
        position: absolute;
        z-index: 1;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        border-radius: 5px;
      }
      .slider > .range {
        position: absolute;
        z-index: 2;
        left: 0%;
        right: 0%;
        top: 0;
        bottom: 0;
        border-radius: 5px;
      }
        .slider > .thumb {
          position: absolute;
          z-index: 3;
          width: 14px;
          height: 14px;
          background-color: #ffffff;
          border-radius: 50%;
          box-shadow: 0 0 0 0 rgba(98,0,238,.1);
          transition: box-shadow .3s ease-in-out;
        }
        .slider > .thumb.left {
          left: 0%;
          transform: translate(-15px, -10px);
        }
        .slider > .thumb.right {
          right: 0%;
          transform: translate(15px, -10px);
        }
        .slider > .thumb.hover {
          box-shadow: 0 0 0 20px rgba(98,0,238,.1);
        }
        .slider > .thumb.active {
          box-shadow: 0 0 0 40px rgba(98,0,238,.2);
        }

        input[type=range] {
          position: absolute;
          pointer-events: none;
          -webkit-appearance: none;
          z-index: 2;
          height: 10px;
          width: 100%;
          opacity: 0;
          cursor: pointer;
        }
        input[type=range]::-moz-range-track {
          position: absolute;
          pointer-events: none;
          -webkit-appearance: none;
          z-index: 2;
          height: 10px;
          width: 100%;
          opacity: 0;
        }
        input[type=range]::-webkit-slider-thumb {
          pointer-events: all;
          width: 30px;
          height: 30px;
          border-radius: 0;
          border: 0 none;
          background-color: red;
          -webkit-appearance: none;
        }
        input[type=range]::-moz-range-thumb {
          pointer-events: all;
          width: 30px;
          height: 30px;
          border-radius: 0;
          border: 0 none;
          background-color: red;
          -webkit-appearance: none;
        }
      }
      .lf-form-likert-radio-options {
        display: flex;
        justify-content: space-evenly;
        align-items: flex-end;
        margin-bottom: 27px;
        margin-top:  30px;
        .lf-form-likert-radio-wrapper-start {
          position: absolute;
          margin-top: -42px;
          margin-left: -31px;
          width: 76px;
          text-align: center;
        }
        .lf-form-likert-radio-wrapper-end {
          position: absolute;
          margin-top: -47px;
          margin-left: -25px;
          width: 76px;
          text-align: center;
        }
      }
      .unitRight {
        padding-right: 30px !important;
      }
      .unitLeft {
        padding-left: 30px !important;
      }
      .lf-form-likert-smileys {
        display: flex;
        justify-content: space-evenly;
        cursor: pointer;
        margin-bottom: 50px;
        .lf-form-smiley-wrapper {
          position: relative;
          // top: 20px;
          .lf-form-smiley-radio-choice {
            position: absolute;
            width: 100%;
            height: 100%;
            top: -4x;
            cursor: pointer;
            left: -5px;
            bottom: 0;
            right: 0;
            opacity: -1;
          }
        }
      }

    }
  }

  /* branding */
  &__branding {
    padding: 10px !important;
    text-align: center !important;
    font-family: 'Karla', sans-serif !important;
    font-size: 12px !important;
    font-weight: 400 !important;
    color: rgb(153, 151, 159) !important;

    a, a:focus, a:active, a:hover {
      color: rgb(89, 89, 89) !important;
      text-decoration: none !important;
      font-family: 'Karla', sans-serif !important;
      font-weight: 700 !important;
      font-size: 12px !important;
      box-shadow: none;
    }
  }

  /* progress bar */
  &__progress {
    margin: 0px;
    box-shadow: 11px 17px 50px 0px #727eb721;
    overflow: hidden;
  }

  @keyframes progress-bar-stripes {
    from  { background-position: 40px 0; }
    to  { background-position: 0 0; }
  }

  /* quill editor */
  .ql-editor {
    padding: 0;

    h1 {
      font-size: 45px;
      line-height: 48px;
      font-weight: 500;
      margin-bottom: 25px;
    }

    h2 {
      font-size: 36px;
      line-height: 40px;
      font-weight: 500;
      margin-bottom: 20px;
    }

    h3 {
      font-size: 30px;
      line-height: 45px;
      font-weight: 500;
      margin-bottom: 15px;
    }

    h4 {
      font-size: 24px;
      line-height: 36px;
      font-weight: 400;
      margin-bottom: 10px;
    }

    h5 {
      font-size: 20px;
      line-height: 30px;
      font-weight: 400;
      margin-bottom: 10px;
    }

    h6 {
      font-size: 16px;
      line-height: 25px;
      font-weight: 400;
      margin-bottom: 10px;
    }

    p {
      font-size: 16px;
      line-height: 30px;
      font-weight: 400;
      margin-bottom: 10px;

      img {
        max-width:100% !important;
      }
    }

    a {
      color: #039be5;
    }

    ul, ol {
      padding-left: 0;
      margin-left: 0;
    }

    li {
      font-size: 16px;
      line-height: 30px;
    }
  }

  /* helpers */
   .ui-button.is-disabled {
    opacity: 1;
  }
  .is-danger {
    color: red;
  }
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
    box-shadow: 11px 17px 50px 0px #727eb721;
  }
  .sbmt_width{
    width: 100% !important;
  }
  .lf-hidden {
    display: none;
  }
  .lf-visibility-hidden {
    visibility: hidden;
  }
  .lf-visibility-none {
    display: none;
  }

  /* Intel tel input Fixed */
  .iti-flag, .iti__flag {background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.1.6/img/flags.png");}

  @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    .iti-flag, .iti__flag {background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.1.6/img/flags@2x.png");}
  }

  /* Datepicker Fix */
  .ui-datepicker-calendar__body {
    table {
      border-collapse: collapse;
    }

    td, th {
      padding: 0;
    }
  }
}
.lf-form.lf-static-height {
  form {
    position: relative;

    .lf-form__step__container.lf-visibility-hidden {
      position: absolute;
      z-index: -10;
    }
  }
}
.lf-form__dropdown-options .ui-select.is-multiple .ui-select__display {
    overflow-x: hidden;
    height: auto !important;
    text-indent: 0px !important;
    padding-left: 10px;
}
.ui-select__dropdown-content {
    outline: none;
    max-height: 16rem;
    overflow-y: auto;
}
.ui-select__options {
    overflow-y: unset !important;
}
@media (max-width: 767px) {
  .skin-columns-3 .lf-form__image-options__column, .skin-columns-3 .lf-form__icon-options__column,
  .skin-columns-4 .lf-form__image-options__column, .skin-columns-4 .lf-form__icon-options__column {
    width: calc(50% - 8.25px) !important;
    display: inline-grid !important;
    margin-right: 0px !important;
    &:nth-child(odd) {
      margin-right: 15px !important;
    }
  }
}
.no-spinners::-webkit-inner-spin-button,
.no-spinners::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.no-spinners {
  -moz-appearance: textfield;
}
</style>
