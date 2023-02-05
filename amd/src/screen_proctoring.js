import $ from 'jquery';
import Ajax from 'core/ajax';

export const init = async(props) => {
    let width = props.image_width; // We will scale the photo width to this
    let height = 0; // This will be computed based on the input stream

    let streaming = false;

    // Skip for summary page
    if (document.getElementById("page-mod-quiz-summary") !== null &&
        document.getElementById("page-mod-quiz-summary").innerHTML.length) {
        return false;
    }

    // Skip for review page
    if (document.getElementById("page-mod-quiz-review") !== null &&
        document.getElementById("page-mod-quiz-review").innerHTML.length) {
        props.is_quiz_started = false;
        return false;
    }

    if (props.is_quiz_started) {
        // eslint-disable-next-line max-len
        $('#mod_quiz_navblock').append(
            '<div class="card-body p-3" style="display: none"><h3 class="no text-left">Screen</h3> <br/>' +
            '<video id="exproctor_video_sc">Screen stream not available.</video>' +
            '<canvas id="exproctor_canvas_sc" style="display:none;"></canvas>' +
            '<div class="exproctor_output_sc" style="display:none;">' +
            '<img id="exproctor_photo_sc" alt="The picture will appear in this box."/></div></div>');
    }

    let video = document.getElementById('exproctor_video_sc');
    let canvas = document.getElementById('exproctor_canvas_sc');
    let photo = document.getElementById('exproctor_photo_sc');

    const get_screen_share_permission = () => {
        return navigator.mediaDevices
            .getDisplayMedia({
                video: {
                    cursor: "always"
                }, audio: false
            })
            .then((stream) => {
                video.srcObject = stream;
                video.play();
            })
            .catch(() => {
                alert("This quiz requires Screen Sharing permission to start!");
                get_screen_share_permission();
            });
    };

    $("#id_submitbutton").click(function() {
        props.is_quiz_started = true;
        takescreenshot();
        setInterval(takescreenshot, props.screenshotdelay);
    });

    $("[id^=single_button].btn.btn-primary").click(function() {
        get_screen_share_permission();
    });

    const clearphoto = () => {
        const context = canvas.getContext("2d");
        context.fillStyle = "#AAA";
        context.fillRect(0, 0, canvas.width, canvas.height);

        const data = canvas.toDataURL("image/png");
        photo.setAttribute("src", data);
    };

    const takescreenshot = () => {
        props.id = localStorage.getItem("attemptId");

        if (props.is_quiz_started && props.id) {
            const context = canvas.getContext("2d");
            window.console.log(props);
            if (width && height) {
                canvas.width = width;
                canvas.height = height;
                context.drawImage(video, 0, 0, width, height);

                const data = canvas.toDataURL("image/png");
                photo.setAttribute("src", data);
                props.webcampicture = data;

                const params = {
                    'courseid': props.courseid,
                    'attemptid': props.id,
                    'quizid': props.quizid,
                    'screenshot': data,
                    'bucketName': localStorage.getItem("bucketName"),
                };

                const request = {
                    methodname: 'quizaccess_exproctor_send_screen_shot',
                    args: params
                };

                window.console.log(params);

                Ajax.call([request])[0].done((data) => {
                    window.console.log(data);
                    if (data.warnings.length !== 0) {
                        if (video) {
                            Notification.addNotification({
                                message: 'Something went wrong during taking the screen-shot.', type: 'error'
                            });
                        }
                    }
                }).fail((err) => {
                    window.console.log(`An error occurred (screen random): ${err}`);
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