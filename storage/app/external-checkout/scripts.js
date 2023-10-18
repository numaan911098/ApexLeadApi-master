let styles = `
   .lg-external-checkout {
	  max-width: 100%;
	  margin: 10px;
   }

   .lg-external-checkout__form-field {
	  margin-bottom: 1.75rem;
	  position: relative;
   }

   .lg-external-checkout__header {
      padding: 15px 15px 30px;
   }

   .lg-external-checkout__form-field label {
	  font-family: "Karla", sans-serif;
	  font-size: 16px;
	  font-weight: 400;
	  line-height: 20px;
	  text-align: left;
	  color: #252d3c;
	  margin-bottom: 8px;
	  display: block;
   }

   .lg-external-checkout__form-field input {
	  width: 100%;
	  height: 50px;
	  border-radius: 4px;
	  border: solid 1px #4dcd93;
	  font-family: "Karla", sans-serif;
	  font-size: 16px;
	  font-weight: 400;
	  line-height: 1.05;
	  text-align: left;
	  color: #252d3c;
	  text-indent: 20px;
	  position: relative;
	  font-family: "Karla", sans-serif;
   }

   .lg-external-checkout__form-field .lg-external-checkout__register-btn {
	  display: -webkit-box;
	  display: -ms-flexbox;
	  display: flex;
	  -webkit-box-pack: center;
	  -ms-flex-pack: center;
	  justify-content: center;
	  -webkit-box-align: center;
	  -ms-flex-align: center;
	  align-items: center;
	  width: 100%;
	  height: 50px;
	  border-radius: 5px;
	  background-color: #fb7574;
	  font-size: 20px;
	  font-family: "Karla", sans-serif;
	  font-weight: 700;
	  line-height: 1.05;
	  color: #ffffff;
	  text-decoration: none;
	  position: relative;
	  border: 0;
    margin: 0 auto;
    cursor: pointer;
    text-indent: 0px;
   }

   .lg-external-checkout__form-field .lg-external-checkout__red-text {
	  color: #f44336!important;
	  text-align: left;
	  font-family: "Karla", sans-serif;
   }

   .lg-external-checkout__header h3 {
	  font-size: 1.75rem;
	  text-align: center;
	  font-family: "Karla", sans-serif;
	  font-weight: 700;
	  line-height: 1.05;
	  color: #252d3c;
   }
`
let html = `
    <div class="lg-external-checkout">
      	<div class="lg-external-checkout__header">
        	<h3 id="ecm-heading"></h3>
      	</div>
    	<div class="lg-external-checkout__body">
			  <form class="lg-external-checkout__form">
          <div id="register-external-checkout">
          </div>
          <div class="lg-external-checkout__form-field">
            <input class="lg-external-checkout__register-btn" type="submit" value="Get Started">
          </div>
      	</form>
   		</div>
		<div class="lg-external-checkout__footer">
		</div>
  	</div>
`

// Insert styles.
let styleSheet = document.createElement("style")
styleSheet.type = "text/css"
styleSheet.innerText = styles
document.head.appendChild(styleSheet)

// Load jQuery.
if (!window.jQuery) {
  let script = document.createElement('script');
  script.type = "text/javascript";
  script.src = "https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js";
  document.getElementsByTagName('head')[0].appendChild(script);
}

// Load Paddle.
function include(file) {
  let script = document.createElement('script');
  script.src = file;
  script.type = 'text/javascript';
  script.defer = true;
  document.getElementsByTagName('head').item(0).appendChild(script);
}


function initLeadgenScripts() {
  jQuery(document).ready(function ($) {
    include('https://cdn.paddle.com/paddle/paddle.js');
    let api = 'API_URL'
    let checkouts = []
    $.ajax({
      url: api,
      type: 'GET',
      success: function (response) {
        checkouts = response.data || []
        if (checkouts.enable > 0) {
          $('#external-checkout-registration-' + checkouts.ref_id).append(html);
        }

        let text = "";
        let htmlString = "";
        let inputString = JSON.parse(checkouts.fields);
        for (let i = 0; i < inputString.length; i++) {
          let counter = inputString[i];
          htmlString = '<div class="lg-external-checkout__form-field">'
          htmlString += '<label for="' + counter.name + '">' + counter.label + '</label><input  class="lg-external-checkout__register-' + counter.name + '" name="' + counter.name + '" type="' + counter.type + '">'
          htmlString += '<p class="lg-external-checkout__red-text" id="lg-external-checkout__register-' + counter.name + '"></p></div>'
          text += htmlString;
        }

        document.getElementById("register-external-checkout").innerHTML = text;
        let inputHeading = checkouts.form_heading;
        document.getElementById("ecm-heading").innerHTML = inputHeading;
        showCheckouts()
      },
      beforeSend: setHeader
    })

    function setHeader(xhr) {
      xhr.setRequestHeader('Authorization', 'Bearer null');
      xhr.setRequestHeader('Accept', 'application/json, text/plain, */*')
    }

    function showCheckouts() {
      jQuery(".lg-external-checkout__register-btn").click(function (e) {
        e.preventDefault();
        $(".lg-external-checkout__register-btn").val("Starting...");

        let registerApi = 'REGISTER_URL'
        let name = jQuery(".lg-external-checkout__register-name").val()
        let email = jQuery(".lg-external-checkout__register-email").val()
        let password = jQuery("input[name=password]").val()

        let isValidName = validateName(name);
        let isValidEmail = validateEmail(email);
        let isValidPassword = validatePassword(password);

        if (!isValidName || !isValidEmail || !isValidPassword) {
          $(".lg-external-checkout__register-btn").val("Get Started");
          return false;
        }

        let $this = $(this);
        $this.toggleClass('lg-external-checkout__ext-loading')
        if ($this.hasClass('lg-external-checkout__ext-loading')) {
          this.value = 'Starting..'
        }

        let registerData = {
          name: name,
          password: password,
          email: email,
          agree_terms: 1,
          subscribe_newsletter: 1,
          source: "external_checkout"
        };

        if ((checkouts.plans.public_id === "free_trial")) {
          registerData.isFreeTrial = true;
        } else {
          registerData.planId = checkouts.plans.paddle_plan_id
        }

        jQuery.ajax({
          type: 'POST',
          dataType: 'json',
          url: registerApi,
          data: registerData,
          success: function (data) {
            checkoutLogs()
            if ((checkouts.plans.public_id === "free") || (checkouts.plans.public_id === "free_trial")) {
              if (checkouts.login > 0) {
                window.location.href = "LOGIN_URL?token=" + data.data.token
              } else {
                window.location.href = checkouts.redirect_url
              }
            } else {
              let vendorId = VENDOR_ID
              Paddle.Setup({ vendor: vendorId })
              let metdata = {
                user_id: data.data.id
              }
              let checkoutConfig = {
                product: checkouts.plans.paddle_plan_id, /* Your Subscription Plan Id */
                theme: 'none',
                email: data.data.email, /* User Email address you registered before */
                passthrough: JSON.stringify(metdata),
                marketingConsent: data.data.subscribe_newsletter,
                successCallback: () => {
                  if (checkouts.login > 0) {
                    setTimeout(function () {
                      window.location.href = "LOGIN_URL?token=" + data.data.token
                    }, 3000)
                  } else {
                    window.location.href = checkouts.redirect_url
                  }
                },
                closeCallback: () => {
                  $(".lg-external-checkout__register-btn").removeClass('lg-external-checkout__ext-loading');
                  $(".lg-external-checkout__register-btn").val("Get Started");
                }
              }
              if (vendorId === 1189) {
                window.Paddle.Environment.set("sandbox")
              }
              window.Paddle.Checkout.open(checkoutConfig)
            }
          },
          error: function(xhr, status, error){
            if (xhr.status === 400) {
              if (xhr.responseJSON.meta.error_type === 'email_already_exist') {
                $('#lg-external-checkout__register-email').text("The email has already been taken.")
              } else if (xhr.responseJSON.meta.error_type === 'too_many_accounts') {
                $('#lg-external-checkout__register-email').text("Too many accounts are not allowed.")
              }
              $(".lg-external-checkout__register-btn").removeClass('lg-external-checkout__ext-loading')
              $(".lg-external-checkout__register-btn").val("Get Started");
              return false;
            }
          }

        })
      })
    }

    function checkoutLogs() {
      let userData = JSON.stringify($('form').serializeArray());
      let logApi = 'SCRIPTS_DOMAIN';
      jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        url: logApi,
        data:{response:userData, external_checkout_id:JSON.stringify(checkouts.id)},
        success:function(data) {}
      })
    }

    function validateName(nameValue) {
      let nameRegex = /^[a-zA-Z\s]+$/;
      if (nameValue === undefined) {
        $("#lg-external-checkout__register-name").text("The Name field is required.");
        return false;
      } else if (nameValue.length === 0) {
        $("#lg-external-checkout__register-name").text("The Name field is required.");
        return false;
      } else if (nameValue.length > 50) {
        $("#lg-external-checkout__register-name").text("The Name field must not exceed 50 characters.");
        return false;
      } else if (!nameValue.match(nameRegex)) {
        $("#lg-external-checkout__register-name").text("The Name field must contain alpha characters only.");
        return false;
      } else {
        $("#lg-external-checkout__register-name").text("");
        return true;
      }
    }

    function validateEmail(emailValue) {
      let emailRegex = /^[\w\-\.\+]+\@[a-zA-Z0-9\.\-]+\.[a-zA-z0-9]{2,4}$/;
      if (emailValue === undefined) {
        $("#lg-external-checkout__register-email").text("The Email field is required.");
        return false;
      } else if (emailValue.length === 0) {
        $("#lg-external-checkout__register-email").text("The Email field is required.");
        return false;
      }  else if (!emailValue.match(emailRegex)) {
        $("#lg-external-checkout__register-email").text("The Email field must be a valid email.");
        return false;
      } else {
        $("#lg-external-checkout__register-email").text("");
        return true;
      }
    }

    function validatePassword(passwordValue) {
      if (passwordValue === undefined) {
        $("#lg-external-checkout__register-password").text("The Password field is required.");
        return false;
      } else if (passwordValue.length === 0) {
        $("#lg-external-checkout__register-password").text("The Password field is required.");
        return false;
      }  else if (passwordValue.length < 8) {
        $("#lg-external-checkout__register-password").text("The Password field must be at least 8 characters.");
        return false;
      } else {
        $("#lg-external-checkout__register-password").text("");
        return true;
      }
    }

  })
}

window.addEventListener('load', () => {
  setTimeout(function () {
    initLeadgenScripts()
  }, 1000)
})
