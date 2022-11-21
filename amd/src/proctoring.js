import $ from 'jquery';
import Ajax from 'core/ajax';

export const webcam_proctoring = (props) => {
    let isCameraAllowed = false;

    $(() => {
        $('#id_submitbutton').prop("disabled", true);
        $('#id_proctoring').on('change', function() {
            if (this.checked && isCameraAllowed) {
                $('#id_submitbutton').prop("disabled", false);
            } else {
                $('#id_submitbutton').prop("disabled", true);
            }
        });
    });

    let width = props.image_width; // We will scale the photo width to this
    let height = 0; // This will be computed based on the input stream

    let streaming = false;

    // const firstcalldelay = 3000; // 3 seconds after the page load
    const takepicturedelay = props.frequency;

    if (props.is_quiz_started) {
        // eslint-disable-next-line max-len
        $('#mod_quiz_navblock').append('<div class="card-body p-3"><h3 class="no text-left">Webcam</h3> <br/>' + '<video id="video">Video stream not available.</video><canvas id="canvas" style="display:none;"></canvas>' + '<div class="output" style="display:none;">' + '<img id="photo" alt="The picture will appear in this box."/></div></div>');
    }

    let video = document.getElementById('video');
    let canvas = document.getElementById('canvas');
    let photo = document.getElementById('photo');

    navigator.mediaDevices
        .getUserMedia({video: true, audio: false})
        .then((stream) => {
            video.srcObject = stream;
            video.play();
            isCameraAllowed = true;
        })
        .catch((err) => {
            window.console.error(`An error occurred: ${err}`);
        });

    const clearphoto = () => {
        const context = canvas.getContext("2d");
        context.fillStyle = "#AAA";
        context.fillRect(0, 0, canvas.width, canvas.height);

        const data = canvas.toDataURL("image/png");
        photo.setAttribute("src", data);
    };

    const takepicture = () => {
        if (props.is_quiz_started) {
            const context = canvas.getContext("2d");
            if (width && height) {
                canvas.width = width;
                canvas.height = height;
                context.drawImage(video, 0, 0, width, height);

                const data = canvas.toDataURL("image/png");
                photo.setAttribute("src", data);
                props.webcampicture = data;

                const api_function = 'quizaccess_exproctor_send_webcam_shot';
                const params = {
                    'courseid': props.courseid,
                    'webcamshotid': props.id,
                    'quizid': props.quizid,
                    'webcampicture': data,
                };

                const request = {
                    methodname: api_function,
                    args: params
                };

                window.console.log(params);

                // window.console.log(Ajax.call([request]));

                Ajax.call([request])[0].done((data) => {
                    window.console.log(data);
                    if (data.warnings.length !== 0) {
                        if (video) {
                            Notification.addNotification({
                                message: 'Something went wrong during taking the image.', type: 'error'
                            });
                        }
                    }
                }).fail(Notification.exception);
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
                    } else {
                        width = 320;
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
        if (props.is_quiz_started) {
            // setTimeout(takepicture, firstcalldelay);
            setInterval(takepicture, takepicturedelay);
        }

        window.console.log(props);
    }
};