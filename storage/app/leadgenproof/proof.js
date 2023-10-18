var styles = `
  .leadgen-proof {
    display: none;
    position: fixed;
    z-index: 2147483647;
    bottom: 20px;
    left: 20px;
    justify-content: space-between;
    align-items: center;
    background-color: white;
    padding: 10px;
    max-width: 350px;
    width: 100%;
    height: 60px;
    box-shadow: 0 0 2px 1px lightgrey;
    border-radius: 50px;
  }

  .leadgen-proof a {
    color: #E94047;
    text-decoration: none;
  }

  .leadgen-proof__img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    margin-right: 15px;
  }

  .leadgen-proof__check-icon {
    width: 12px;
    height: 12px;
    margin-bottom: -3px;
  }

  .leadgen-proof__customer {
    display: flex;
    flex-direction: column;
    flex: 1;
  }

  .leadgen-proof__customer > span:nth-of-type(1) {
    font-size: 15px;
    margin-bottom: 2px;
    padding: 4px 4px;
    font-weight: bold;
    line-height: 14px;
    color: #242328;
  }

  .leadgen-proof__customer > span:nth-of-type(2) {
    font-size: 13px;
    margin-bottom: 2px;
    padding: 2px 4px;
    line-height: 14px;
    color: #686b81;
  }

  .leadgen-proof__customer > span:nth-of-type(3) {
    font-size: 12px;
    color: grey;
    padding: 2px 4px;
  }

  .leadgen-proof__verification {
    font-size: 13px;
  }

  .leadgen-proof__ts {
    font-size: 12px;
    color: grey;
    display: inline-block;
    margin-right: 10px;
  }

  @media only screen and (max-width: 480px) {
    .leadgen-proof {
      bottom: 0;
      left: 0;
      width: 100%;
      max-width: 100%;
      border-radius: 0;
    }
  }

  /**
  * ----------------------------------------
  * animation slide-in-bottom
  * ----------------------------------------
  */
  .slide-in-bottom {
    display: flex;
    -webkit-animation: slide-in-bottom 0.5s cubic-bezier(0.250, 0.460, 0.450, 0.940) both;
            animation: slide-in-bottom 0.5s cubic-bezier(0.250, 0.460, 0.450, 0.940) both;
  }
  @-webkit-keyframes slide-in-bottom {
    0% {
      -webkit-transform: translateY(1000px);
              transform: translateY(1000px);
      opacity: 0;
    }
    100% {
      -webkit-transform: translateY(0);
              transform: translateY(0);
      opacity: 1;
    }
  }
  @keyframes slide-in-bottom {
    0% {
      -webkit-transform: translateY(1000px);
              transform: translateY(1000px);
      opacity: 0;
    }
    100% {
      -webkit-transform: translateY(0);
              transform: translateY(0);
      opacity: 1;
    }
  }

  /**
  * ----------------------------------------
  * animation slide-out-bottom
  * ----------------------------------------
  */
  .slide-out-bottom {
    -webkit-animation: slide-out-bottom 0.5s cubic-bezier(0.550, 0.085, 0.680, 0.530) both;
            animation: slide-out-bottom 0.5s cubic-bezier(0.550, 0.085, 0.680, 0.530) both;
  }
  @-webkit-keyframes slide-out-bottom {
    0% {
      -webkit-transform: translateY(0);
              transform: translateY(0);
      opacity: 1;
    }
    100% {
      -webkit-transform: translateY(1000px);
              transform: translateY(1000px);
      opacity: 0;
    }
  }
  @keyframes slide-out-bottom {
    0% {
      -webkit-transform: translateY(0);
              transform: translateY(0);
      opacity: 1;
    }
    100% {
      -webkit-transform: translateY(1000px);
              transform: translateY(1000px);
      opacity: 0;
    }
  }
`

// Insert styles.
var styleSheet = document.createElement("style")
styleSheet.type = "text/css"
styleSheet.innerText = styles
document.head.appendChild(styleSheet)

// Load jQuery.
if(!window.jQuery) {
  var script = document.createElement('script');
  script.type = "text/javascript";
  script.src = "https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js";
  document.getElementsByTagName('head')[0].appendChild(script);
}

function initLeadgenProof() {
  jQuery(document).ready(function($) {
    $('body').append(' \
    <div class="leadgen-proof slide-out-bottom"> \
        <img class="leadgen-proof__img" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB2ZXJzaW9uPSIxLjEiIGlkPSJDYXBhXzEiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgNTggNTgiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDU4IDU4OyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIgd2lkdGg9IjUxMiIgaGVpZ2h0PSI1MTIiIGNsYXNzPSIiPjxnPjxjaXJjbGUgc3R5bGU9ImZpbGw6IzQyNEE2MCIgY3g9IjI5IiBjeT0iMjkiIHI9IjI5IiBkYXRhLW9yaWdpbmFsPSIjNDI0QTYwIiBjbGFzcz0iIiBkYXRhLW9sZF9jb2xvcj0iIyNFOTQwNCI+PC9jaXJjbGU+PHBhdGggc3R5bGU9ImZpbGw6I0ZCQ0U5RDsiIGQ9Ik01Mi45MzIsNDUuMzc2QzUyLjI3NSwzOC45ODUsNDYuODc2LDM0LDQwLjMxMSwzNGgtNS45NDZDMzMuNjExLDM0LDMzLDMzLjM4OSwzMywzMi42MzVWMzEuOTkgIGMwLTAuNTgzLDAuMzc5LTEuMDgyLDAuOTI1LTEuMjg3YzUuODA0LTIuMTgyLDkuNzc4LTExLjcwNCw4Ljk3MS0xOC40MzNDNDIuMTM0LDUuOTE5LDM2Ljk3LDAuODAxLDMwLjYxNCwwLjA5ICBjLTAuNTE3LTAuMDU4LTEuMDI5LTAuMDg2LTEuNTM1LTAuMDg4Yy0wLjAxNiwwLTAuMDMyLTAuMDAxLTAuMDQ4LTAuMDAxQzIxLjI4NS0wLjAxNiwxNSw2LjI1OCwxNSwxNCAgYzAsNi4wMjQsMy44MDcsMTQuNzU1LDkuMTQ1LDE2LjcyOUMyNC42NjgsMzAuOTIyLDI1LDMxLjQ0MiwyNSwzMnYwLjYzNUMyNSwzMy4zODksMjQuMzg5LDM0LDIzLjYzNSwzNGgtNS45NDYgIGMtNi41NjUsMC0xMS45NjQsNC45ODUtMTIuNjIxLDExLjM3NkMxMC4yOTMsNTIuOTk4LDE5LjA2MSw1OCwyOSw1OFM0Ny43MDcsNTIuOTk4LDUyLjkzMiw0NS4zNzZ6IiBkYXRhLW9yaWdpbmFsPSIjRkJDRTlEIiBjbGFzcz0iIj48L3BhdGg+PHBhdGggc3R5bGU9ImZpbGw6IzZDNzk3QTsiIGQ9Ik0zMy40OTIsNi42MWMzLjcxNSwxLjAyMSw3LjIxMywzLjMwNiw5LjQ1Myw2LjMyMmMtMC4wMTYtMC4yMjEtMC4wMjMtMC40NDctMC4wNDktMC42NjMgIEM0Mi4xMzQsNS45MTksMzYuOTcsMC44MDEsMzAuNjE0LDAuMDljLTAuNTE3LTAuMDU4LTEuMDI5LTAuMDg2LTEuNTM1LTAuMDg4Yy0wLjAxNiwwLTAuMDMyLTAuMDAxLTAuMDQ4LTAuMDAxICBjLTYuOTItMC4wMTUtMTIuNjYsNC45OTUtMTMuODA4LDExLjU4M2wwLjAwNSwwYzAuMTc5LDAuMjUyLDAuMzU0LDAuNTA3LDAuNTQ1LDAuNzVjMC4wNy0wLjA4NywwLjE0MS0wLjE3MywwLjIxMy0wLjI1OCAgYzItMi4zOCw1LjM0MS0yLjkzMSw4LjE4My0xLjY3MUMyNS4wMzQsMTAuNzg3LDI1Ljk5MiwxMSwyNywxMUMyOS45NDIsMTEsMzIuNDU2LDkuMTgyLDMzLjQ5Miw2LjYxeiIgZGF0YS1vcmlnaW5hbD0iIzZDNzk3QSI+PC9wYXRoPjxwYXRoIHN0eWxlPSJmaWxsOiNFN0VDRUQ7IiBkPSJNNTIuOTMyLDQ1LjM3NkM1Mi4yNzUsMzguOTg1LDQ2Ljg3NSwzNCw0MC4zMTEsMzRIMzlsLTYsNmgtOGwtMy0ybC0zLjQyOS00aC0wLjg4MiAgYy02LjU2NCwwLTExLjk2NCw0Ljk4NS0xMi42MjEsMTEuMzc2QzEwLjI5Myw1Mi45OTgsMTkuMDYxLDU4LDI5LDU4UzQ3LjcwNyw1Mi45OTgsNTIuOTMyLDQ1LjM3NnoiIGRhdGEtb3JpZ2luYWw9IiNFN0VDRUQiIGNsYXNzPSIiPjwvcGF0aD48cGF0aCBzdHlsZT0iZmlsbDojQ0NENUQ2OyIgZD0iTTQzLDU0LjM5MmMwLjY4NS0wLjM3OSwxLjM1LTAuNzg5LDItMS4yMlY0N2gtMlY1NC4zOTJ6IiBkYXRhLW9yaWdpbmFsPSIjQ0NENUQ2Ij48L3BhdGg+PHBhdGggc3R5bGU9ImZpbGw6I0NDRDVENjsiIGQ9Ik0xNSw1NC4zOTJWNDdoLTJ2Ni4xNzJDMTMuNjUsNTMuNjAzLDE0LjMxNSw1NC4wMTMsMTUsNTQuMzkyeiIgZGF0YS1vcmlnaW5hbD0iI0NDRDVENiI+PC9wYXRoPjxyZWN0IHg9IjI2IiB5PSIzOCIgc3R5bGU9ImZpbGw6IzM4NDU0RjsiIHdpZHRoPSI2IiBoZWlnaHQ9IjYiIGRhdGEtb3JpZ2luYWw9IiMzODQ1NEYiIGNsYXNzPSIiPjwvcmVjdD48cGF0aCBzdHlsZT0iZmlsbDojNTQ2QTc5OyIgZD0iTTI0Ljk2MSw1Ny43MTRDMjYuMjgxLDU3Ljg5OCwyNy42MjksNTgsMjksNThjMS4zODIsMCwyLjczOS0wLjEwMyw0LjA2OS0wLjI5TDMxLDQ0aC00ICBMMjQuOTYxLDU3LjcxNHoiIGRhdGEtb3JpZ2luYWw9IiM1NDZBNzkiPjwvcGF0aD48cGF0aCBzdHlsZT0iZmlsbDojRkZGRkZGIiBkPSJNMjYsMzguOEwyMiwzNGgtNC44NTZjLTAuMDY5LDAtMC4xMTYsMC4wNy0wLjA5MSwwLjEzNEwyMSw0NGw1LTMuMzMzVjM4Ljh6IiBkYXRhLW9yaWdpbmFsPSIjRkZGRkZGIiBjbGFzcz0iYWN0aXZlLXBhdGgiPjwvcGF0aD48cGF0aCBzdHlsZT0iZmlsbDojRkZGRkZGIiBkPSJNNDAuODU2LDM0SDM2bC00LDQuOHYxLjg2N0wzNyw0NGwzLjk0Ni05Ljg2NkM0MC45NzIsMzQuMDcsNDAuOTI1LDM0LDQwLjg1NiwzNHoiIGRhdGEtb3JpZ2luYWw9IiNGRkZGRkYiIGNsYXNzPSJhY3RpdmUtcGF0aCI+PC9wYXRoPjwvZz4gPC9zdmc+" /> \
        <div class="leadgen-proof__customer"> \
            <span class="leadgen-proof__customer-name"></span> \
            <span class="leadgen-proof__customer-desc"></span> \
            <span class="leadgen-proof__customer-time"></span> \
        </div> \
    </div> \
    ')

    var delay = DELAY * 1000 // 5 seconds delay
    var api = 'API_URL' // api url
    var proofs = []
    var proofIndex = 0

    var verification = ' <a class="leadgen-proof__verification" href="https://leadgenapp.io" target="_blank"><img class="leadgen-proof__check-icon" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB2ZXJzaW9uPSIxLjEiIHZpZXdCb3g9IjAgMCA0NCA0NCIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAwIDAgNDQgNDQiIHdpZHRoPSI1MTIiIGhlaWdodD0iNTEyIiBjbGFzcz0iIj48Zz48cGF0aCBkPSJtMjIsMGMtMTIuMiwwLTIyLDkuOC0yMiwyMnM5LjgsMjIgMjIsMjIgMjItOS44IDIyLTIyLTkuOC0yMi0yMi0yMnptMTIuNywxNS4xbDAsMC0xNiwxNi42Yy0wLjIsMC4yLTAuNCwwLjMtMC43LDAuMy0wLjMsMC0wLjYtMC4xLTAuNy0wLjNsLTcuOC04LjQtLjItLjJjLTAuMi0wLjItMC4zLTAuNS0wLjMtMC43czAuMS0wLjUgMC4zLTAuN2wxLjQtMS40YzAuNC0wLjQgMS0wLjQgMS40LDBsLjEsLjEgNS41LDUuOWMwLjIsMC4yIDAuNSwwLjIgMC43LDBsMTMuNC0xMy45aDAuMWMwLjQtMC40IDEtMC40IDEuNCwwbDEuNCwxLjRjMC40LDAuMyAwLjQsMC45IDAsMS4zeiIgZGF0YS1vcmlnaW5hbD0iIzAwMDAwMCIgY2xhc3M9ImFjdGl2ZS1wYXRoIiBzdHlsZT0iZmlsbDojRUU2RTczIiBkYXRhLW9sZF9jb2xvcj0iI2VlNmU3MyI+PC9wYXRoPjwvZz4gPC9zdmc+" /> verified by Leadgen</a>'

    $.ajax({
      url: api,
      type: 'GET',
      success: function(response) {
        proofs = response.data || []
        showProofs()
      },
      beforeSend: setHeader
    })

    function setHeader(xhr) {
      xhr.setRequestHeader('Authorization', 'Bearer null');
      xhr.setRequestHeader('Accept', 'application/json, text/plain, */*')
    }

    function showProofs() {
      if (proofs.length === 0) {
        return
      }

      setInterval(function () {
        if (proofIndex >= proofs.length) {
          proofIndex = 0
        }

        if ($('.leadgen-proof').hasClass('slide-in-bottom')) {
          $('.leadgen-proof')
            .removeClass('slide-in-bottom')
            .addClass('slide-out-bottom')
          return
        }

        $('.leadgen-proof__customer-name')
          .text(proofs[proofIndex].title)

          $('.leadgen-proof__customer-desc')
          .text(proofs[proofIndex].description)

        if (proofs[proofIndex].created_at) {
          $('.leadgen-proof__customer-time')
            .html('<span class="leadgen-proof__ts">' + proofs[proofIndex].created_at + '</span>' + verification)
        } else {
          $('.leadgen-proof__customer-time')
            .html(verification)
        }

        $('.leadgen-proof')
          .removeClass('slide-out-bottom')
          .addClass('slide-in-bottom')
          .css('display', 'flex')

        proofIndex++
      }, delay)
    }
  })
}

window.addEventListener('load', () => {
  setTimeout(function () {
    initLeadgenProof()
  }, 1000)
})
