import $ from "jquery";
import Ajax from "core/ajax";
import { remove } from "./store_current_attempt";

export const init = (props) => {
  let isCameraAllowed = false;

  $("#id_submitbutton").prop("disabled", true);
  $("#id_webproctoring").on("change", function () {
    if (this.checked && isCameraAllowed) {
      $("#id_submitbutton").prop("disabled", false);
    } else {
      $("#id_submitbutton").prop("disabled", true);
    }
  });

  // Skip for summary page
  if (
    document.getElementById("page-mod-quiz-summary") !== null &&
    document.getElementById("page-mod-quiz-summary").innerHTML.length
  ) {
    return false;
  }

  // Skip for review page
  if (
    document.getElementById("page-mod-quiz-review") !== null &&
    document.getElementById("page-mod-quiz-review").innerHTML.length
  ) {
    props.is_quiz_started = false;
    remove();
    return false;
  }

  let width = props.image_width; // We will scale the photo width to this
  let height = 0; // This will be computed based on the input stream

  let streaming = false;

  if (props.is_quiz_started) {
    // eslint-disable-next-line max-len
    $("#mod_quiz_navblock").append(
      '<div class="card-body p-3"><h3 class="no text-left">Webcam</h3> <br/>' +
        '<video id="exproctor_video_wb">Video stream not available.</video>' +
        '<canvas id="exproctor_canvas_wb" style="display:none;"></canvas>' +
        '<div class="exproctor_output_wb" style="display:none;">' +
        '<img id="exproctor_photo_wb" alt="The webcam capture will appear in this box."/></div></div>'
    );
  }

  let video = document.getElementById("exproctor_video_wb");
  let canvas = document.getElementById("exproctor_canvas_wb");
  let photo = document.getElementById("exproctor_photo_wb");

  const get_webcam_share_permission = () => {
    return navigator.mediaDevices
      .getUserMedia({ video: true, audio: false })
      .then((stream) => {
        video.srcObject = stream;
        video.play();
        isCameraAllowed = true;
      })
      .catch(() => {
        alert("This quiz requires Webcam permission to start!");
        get_webcam_share_permission();
      });
  };

  $("#id_submitbutton").click(function () {
    localStorage.removeItem("attemptId");
    localStorage.removeItem("bucketName");
    props.is_quiz_started = true;
    takepicture();
    setInterval(takepicture, props.proctoringmethod == 2 ? 1000 : props.screenshotdelay);
  });

  $(`#${$("[id^=single_button].btn.btn-primary")[0].id}`).click(function () {
    get_webcam_share_permission();
    props.is_quiz_started = true;
    takepicture();
    setInterval(takepicture, props.proctoringmethod == 2 ? 1000 : props.screenshotdelay);
  });

  const clearphoto = () => {
    const context = canvas.getContext("2d");
    context.fillStyle = "#AAA";
    context.fillRect(0, 0, canvas.width, canvas.height);

    const data = canvas.toDataURL("image/png");
    photo.setAttribute("src", data);
  };

  const takepicture = () => {
    props.id = localStorage.getItem("attemptId");

    if (props.is_quiz_started && props.id) {
      const context = canvas.getContext("2d");
      if (width && height) {
        canvas.width = width;
        canvas.height = height;
        context.drawImage(video, 0, 0, width, height);

        const data = canvas.toDataURL("image/png");
        photo.setAttribute("src", data);
        props.webcampicture = data;

        const api_function = "quizaccess_exproctor_send_webcam_shot";
        const params = {
          courseid: props.courseid,
          attemptid: props.id,
          quizid: props.quizid,
          webcamshot: data,
          bucketName: localStorage.getItem("bucketName"),
          aiproctoring: props.proctoringmethod == 2,
        };

        const request = {
          methodname: api_function,
          args: params,
        };

        window.console.log(params);

        Ajax.call([request])[0]
          .done((data) => {
            window.console.log(data);
            if (data.warnings.length !== 0) {
              if (video) {
                Notification.addNotification({
                  message: "Something went wrong during taking the image.",
                  type: "error",
                });
              }
            }
          })
          .fail((err) => {
            window.console.log("Webcam proctoring");
            window.console.log(err);
          });
      } else {
        clearphoto();
      }
    }
  };

  if (video) {
    video.addEventListener(
      "canplay",
      () => {
        if (!streaming) {
          if (props.is_quiz_started) {
            width = 270;
          }
          height = video.videoHeight / (video.videoWidth / width);

          if (isNaN(height)) {
            height = width / (4 / 3);
          }

          video.setAttribute("width", width);
          video.setAttribute("height", height);
          canvas.setAttribute("width", width);
          canvas.setAttribute("height", height);
          streaming = true;
        }
      },
      false
    );
  }
};
