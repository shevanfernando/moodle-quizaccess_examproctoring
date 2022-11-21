import $ from 'jquery';
import Ajax from 'core/ajax';

// let captureStream = new Object();

export const webcam_proctoring = (props) => {
    let isCameraAllowed = false;

    window.console.log(isCameraAllowed);

    $(() => {
        $('#id_submitbutton').prop("disabled", true);
        $('#id_web_proctoring').on('change', function() {
            if (this.checked && isCameraAllowed) {
                $('#id_submitbutton').prop("disabled", false);
            } else {
                $('#id_submitbutton').prop("disabled", true);
            }
        });
    });

    // Skip for summary page
    if (document.getElementById("page-mod-quiz-summary") !== null &&
        document.getElementById("page-mod-quiz-summary").innerHTML.length) {
        return false;
    }
    // Skip for review page
    if (document.getElementById("page-mod-quiz-review") !== null &&
        document.getElementById("page-mod-quiz-review").innerHTML.length) {
        return false;
    }

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
    }

    const vidOff = () => {
        video.srcObject.getVideoTracks().forEach((track) => track.stop());
        isCameraAllowed = false;
    };

    if (props.is_close) {
        vidOff();
        return false;
    }
};

export const screen_proctoring = async(props) => {
    let isCameraAllowed = false;

    $(() => {
        $('#id_submitbutton').prop("disabled", true);
        $('#id_screen_proctoring').on('change', function() {
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

    // Skip for summary page
    if (document.getElementById("page-mod-quiz-summary") !== null &&
        document.getElementById("page-mod-quiz-summary").innerHTML.length) {
        return false;
    }
    // Skip for review page
    if (document.getElementById("page-mod-quiz-review") !== null &&
        document.getElementById("page-mod-quiz-review").innerHTML.length) {
        return false;
    }

    if (props.is_quiz_started) {
        // eslint-disable-next-line max-len
        $('#mod_quiz_navblock').append('<div class="card-body p-3"><h3 class="no text-left">Screen</h3> <br/>' + '<video id="video_sc">Screen stream not available.</video><canvas id="canvas_sc" style="display:none;"></canvas>' + '<div class="output" style="display:none;">' + '<img id="photo_sc" alt="The picture will appear in this box."/></div></div>');
    }

    let video = document.getElementById('video_sc');
    let canvas = document.getElementById('canvas_sc');
    let photo = document.getElementById('photo_sc');

    // const firstcalldelay = 3000; // 3 seconds after the page load
    const takepicturedelay = props.frequency;

    // const captureStream = await navigator.mediaDevices.getDisplayMedia({video: {cursor: "always"}, audio: false});
    //
    // video.srcObject = captureStream;
    // context.drawImage(video, 0, 0, width, height);
    // const frame = canvas.toDataURL("image/png");
    //
    // // const take_screen_shot = () => {
    // //
    // // };
    //
    // if (props.is_quiz_started) {
    //     window.console.log("quizaccess_exproctor_send_screen_shot");
    //     const api_function = 'quizaccess_exproctor_send_screen_shot';
    //     const params = {
    //         'courseid': props.courseid,
    //         'screenshotid': props.id,
    //         'quizid': props.quizid,
    //         'screenpicture': frame,
    //     };
    //
    //     const request = {
    //         methodname: api_function,
    //         args: params
    //     };
    //
    //     window.console.log(params);
    //
    //     window.console.log(Ajax.call([request]));
    //
    //     Ajax.call([request])[0].done((data) => {
    //         window.console.log(data);
    //         if (data.warnings.length !== 0) {
    //             if (video) {
    //                 Notification.addNotification({
    //                     message: 'Something went wrong during taking the screen-shot.', type: 'error'
    //                 });
    //             }
    //         }
    //     }).fail(Notification.exception);
    // }

    const captureStream = navigator.mediaDevices
        .getDisplayMedia({
            video: {
                cursor: "always"
            }, audio: false
        })
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

    const takescreenshot = () => {
        window.console.log("Before if");
        if (props.is_quiz_started) {
            const context = canvas.getContext("2d");
            window.console.log(props);
            if (width && height) {
                canvas.width = width;
                canvas.height = height;
                context.drawImage(video, 0, 0, width, height);

                const data = canvas.toDataURL("image/png");
                photo.setAttribute("src", data);
                props.webcampicture = data;

                const api_function = 'quizaccess_exproctor_send_screen_shot';
                const params = {
                    'courseid': props.courseid,
                    'screenshotid': props.id,
                    'quizid': props.quizid,
                    'screenpicture': data,
                };

                const request = {
                    methodname: api_function,
                    args: params
                };

                window.console.log(params);

                window.console.log(Ajax.call([request]));

                Ajax.call([request])[0].done((data) => {
                    window.console.log(data);
                    if (data.warnings.length !== 0) {
                        if (video) {
                            Notification.addNotification({
                                message: 'Something went wrong during taking the screen-shot.', type: 'error'
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
        window.console.log(`Bottom: ${props}`);
        window.console.log(props);
        window.console.log(`Bottom: ${props}`);
        window.console.log(isCameraAllowed);
        if (props.is_quiz_started) {
            window.console.log("Inside trigger");
            setInterval(takescreenshot, takepicturedelay);
        }
    }

    const vidOff = () => {
        captureStream.getTracks().forEach(track => track.stop());
        isCameraAllowed = false;
    };

    if (props.is_close) {
        vidOff();
        return false;
    }
};

export const init = (props) => {
    // window.console.log($('.quizstartbuttondiv'));

    const submit_button_id = $('.quizstartbuttondiv').find("button")[0].id;
    let data = {
        image_width: 320,
        frequency: 3000,
        is_quiz_started: false,
        is_close: true
    };

    $(`#${submit_button_id}`).click(async function() {
        data.is_close = false;

        if (props.webcamproctoringrequired) {
            webcam_proctoring(data);
        }

        if (props.screenproctoringrequired) {

            screen_proctoring(data);
        }
    });

    $('#id_cancel').click(function() {
        data.is_close = true;
        if (props.webcamproctoringrequired) {
            webcam_proctoring(data);
        }

        if (props.screenproctoringrequired) {
            screen_proctoring(data);
        }
    });
};