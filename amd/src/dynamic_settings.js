import $ from 'jquery';

const settings = (getValue) => {
    if (getValue === "AWS(S3)") {
        $("#admin-localpath").hide();
        $("#admin-awsregion").show();
        $("#admin-awsaccessid").show();
        $("#admin-awsaccesskey").show();
    } else {
        $("#admin-localpath").show();
        $("#admin-awsregion").hide();
        $("#admin-awsaccessid").hide();
        $("#admin-awsaccesskey").hide();
    }
};

export const init = () => {
    // Current selected value
    settings($("#id_s_quizaccess_examproctoring_storagemethod").val());
    // Event trigger when dropdown value change
    $("#id_s_quizaccess_examproctoring_storagemethod").change((e) => {
        const $this = $(e.currentTarget);
        settings($this.val());
    });
};