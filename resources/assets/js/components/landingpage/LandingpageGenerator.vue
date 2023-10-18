<template>
  <div id="template-content" :style="{'background-color': config.colors.body_bg.value}">
    <div v-if="formKey.length === 0" class="template-content-elements">
      <div
        class="template-element-media center-align"
        v-if="config.media_type.position !== 'above_description' && config.visibility.show_media.value === '1'"
        :style="{order: mediaOrder, width: (config.media_type.position === 'content_left_side' || config.media_type.position === 'content_right_side' ? '50%' : '100%')}">
        <div v-if="config.media_type.type === 'image'">
          <div v-if="config.media_type.image_url && config.media_type.image_url.length > 0">
            <img :src="config.media_type.image_url">
          </div>
        </div>
        <div v-else-if="config.media_type.type  === 'video'">
          <div v-if="config.media_type.is_youtube_video">
            <iframe v-if="config.media_type.video_url && config.media_type.video_url.length > 0" style="width: 90%;height: 250px;" :src="config.media_type.video_url" allowfullscreen></iframe>
          </div>
          <div v-else>
            <video style="width: 90%;height: 250px;" v-if="config.media_type.video_url && config.media_type.video_url.length > 0" controls>
              <source :src="config.media_type.video_url" :type="'video/' + videoExtension">
              Your browser does not support the video tag.
            </video>
          </div>
        </div>
      </div>
      <div
        class="template-element-mid-content"
        :style="{order: 2, width: (config.media_type.position === 'content_left_side' || config.media_type.position === 'content_right_side' ? '50%' : '100%')}">
        <div class="template-element-title"
        v-if="config.visibility.show_headline.value === '1'">
          <div class="ql-editor" v-html="config.title"></div>
        </div>
        <div
          class="template-element-media center-align"
          v-if="config.media_type.position === 'above_description' && config.visibility.show_media.value === '1'"
          :style="{order: mediaOrder, width: (config.media_type.position === 'content_left_side' || config.media_type.position === 'content_right_side' ? '50%' : '100%')}">
          <div v-if="config.media_type.type === 'image'">
            <div v-if="config.media_type.image_url && config.media_type.image_url.length > 0">
              <img :src="config.media_type.image_url">
            </div>
          </div>
          <div v-else-if="config.media_type.type === 'video'">
            <div v-if="config.media_type.is_youtube_video">
              <iframe v-if="config.media_type.video_url && config.media_type.video_url.length > 0" style="width: 90%;height: 250px;" :src="config.media_type.video_url" allowfullscreen></iframe>
            </div>
            <div v-else>
              <video style="width: 90%;height: 350px;" v-if="config.media_type.video_url && config.media_type.video_url.length > 0" controls>
                <source :src="config.media_type.video_url" :type="'video/' + videoExtension">
                Your browser does not support the video tag.
              </video>
            </div>
          </div>
        </div>
        <div class="template-element-description center-align" v-if="config.visibility.show_description.value === '1'">
          <div class="ql-editor" v-html="config.description"></div>
        </div>
      </div>
      <div class="template-element-cta center-align" :style="{order: 4}" v-if="config.visibility.show_cta1.value === '1'">
        <ui-button :size="config.cta.cta_size " @click="ctaAction" color="primary" type="primary" buttonType="button" :style="{'background-color': config.colors.cta1_bg.value, color: config.colors.cta1_color.value, 'width': config.cta.cta_fullwidth === '1' ? '100%' : 'auto'}" raised>
          {{ config.cta.cta_text }}
        </ui-button>
      </div>
    </div>
    <div class="leadgen-form-wrap" v-else>
      <form-generator :landing-page-id="page.id" :landing-page-visit-id="visitId" :form-key="formKey"></form-generator>
    </div>
  </div>
</template>
<script>
import Vue from 'vue'
import FormGenerator from '../form/FormGenerator'

export default {
  props: {
    pagejson: {
      default: {},
      required: true
    },
    pageId: {
      required: true
    }
  },
  components: {
    'form-generator' : FormGenerator
  },
  data: function () {
    return {
      formKey: '',
      visitId: null
    }
  },
  components: {
    'form-generator': FormGenerator
  },
  mounted: function () {
    this.pageVisit()
  },
  methods: {
    ctaAction: function () {
      if (this.config.visibility.show_cta1.value) {
        if (this.config.cta.url) {
          this.recordOptin()
          window.location.href = this.config.cta.url
        } else {
          this.formKey = this.config.cta.leadgen_form_id
        }
      }
    },
    pageVisit: function () {
      console.log(Vue.http.options)
      Vue.http.post('landingpage-visits', {
        'leadgen_visitor_id': Vue.ls.get('visitor_id'),
        'landing_page_id': this.pageId
      }, {
        emulateJSON: true,
        headers: {
          'Authorization': 'Bearer null'
        }
      })
        .then((response) => {
          if (!Vue.ls.get('visitor_id')) {
            Vue.ls.set('visitor_id', response.body.data.visitor.ref_id)
          }
          this.visitId = parseInt(response.body.data.id)
        }, (response) => {
        })
    },
    recordOptin: function () {
      Vue.http.post('landingpage-optins', {
        'landing_page_visit_id': this.visitId,
        'landing_page_id': this.page.id
      }, {
        emulateJSON: true,
        headers: {
          'Authorization': 'Bearer null'
        }
      })
        .then((response) => {
        }, (response) => {
        })
    }
  },
  computed: {
    config: function () {
      return this.page.config
    },
    videoExtension: function () {
      let urlParts = this.config.media_type.video_url.split('.')
      return urlParts[urlParts.length - 1]
    },
    mediaOrder: function () {
      let position = this.config.media_type.position
      if (position === 'above_headline') {
        return 1
      } else if (position === 'above_description') {
        return 1
      } else if (position === 'content_left_side') {
        return 3
      } else if (position === 'content_right_side') {
        return 1
      } else {
        return 1
      }
    },
    page: function() {
      if(this.pagejson) {
        return JSON.parse(this.pagejson)
      }
      return {}
    }
  }
}
</script>
