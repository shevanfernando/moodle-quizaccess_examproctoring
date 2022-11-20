// const webcam_proctoring = () => {
//     const width = 320; // We will scale the photo width to this
//     let height = 0; // This will be computed based on the input stream
//
//     let streaming = false;
//
//     let video = document.getElementById('video');
//     let canvas = document.getElementById('canvas');
//     let photo = document.getElementById('photo');
//
//     navigator.mediaDevices
//         .getUserMedia({video: true, audio: false})
//         .then((stream) => {
//             video.srcObject = stream;
//             video.play();
//         })
//         .catch((err) => {
//             window.console.error(`An error occurred: ${err}`);
//         });
//
//     const clearphoto = () => {
//         const context = canvas.getContext("2d");
//         context.fillStyle = "#AAA";
//         context.fillRect(0, 0, canvas.width, canvas.height);
//
//         const data = canvas.toDataURL("image/png");
//         photo.setAttribute("src", data);
//     };
//
//     const takepicture = () => {
//         const context = canvas.getContext("2d");
//         if (width && height) {
//             canvas.width = width;
//             canvas.height = height;
//             context.drawImage(video, 0, 0, width, height);
//
//             const data = canvas.toDataURL("image/png");
//             photo.setAttribute("src", data);
//         } else {
//             clearphoto();
//         }
//     };
//
//     if (video) {
//         video.addEventListener(
//             "canplay",
//             () => {
//                 if (!streaming) {
//                     height = (video.videoHeight / video.videoWidth) * width;
//
//                     video.setAttribute("width", width);
//                     video.setAttribute("height", height);
//                     canvas.setAttribute("width", width);
//                     canvas.setAttribute("height", height);
//                     streaming = true;
//                 }
//             },
//             false
//         );
//     }
// };

export const init = (data) => {


    window.console.log(data);


};