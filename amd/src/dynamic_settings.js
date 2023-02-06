import $ from "jquery";

let previousSelect;

const settings = (getValue) => {
  if (getValue === "AWS(S3)") {
    $("#admin-awsregion").show();
    $("#admin-awssecretkey").show();
    $("#admin-awsaccesskey").show();
  } else {
    $("#admin-awsregion").hide();
    $("#admin-awssecretkey").hide();
    $("#admin-awsaccesskey").hide();
  }

  window.console.log(previousSelect);

  if (
    previousSelect !== getValue &&
    previousSelect !== undefined &&
    $("#id_s_quizaccess_exproctor_awsaccesskey").val() &&
    $("#id_s_quizaccess_exproctor_awssecretkey").val()
  ) {
    $("[class='btn btn-primary']").prop("disabled", false);
  } else {
    $("[class='btn btn-primary']").prop("disabled", true);
  }

  previousSelect = getValue;
};

// eslint-disable-next-line camelcase
const enable_submit_button = (storageMethod, s3AccessKey, s3SecretKey) => {
  if (
    storageMethod === "AWS(S3)" &&
    s3AccessKey.val().length >= 16 &&
    s3AccessKey.val().length <= 128 &&
    s3SecretKey.val().length >= 16 &&
    s3SecretKey.val().length <= 128
  ) {
    $("[class='btn btn-primary']").prop("disabled", false);
  } else {
    $("[class='btn btn-primary']").prop("disabled", true);
  }
};

export const init = () => {
  const storageTextElement = $("#id_s_quizaccess_exproctor_storagemethod");
  let storageMethod = storageTextElement.val();

  // Current selected value
  settings(storageMethod);

  // Event trigger when dropdown value change
  storageTextElement.change((e) => {
    const $this = $(e.currentTarget);
    storageMethod = $this.val();
    settings(storageMethod);
  });

  const s3AccessKey = $("#id_s_quizaccess_exproctor_awsaccesskey");
  const s3SecretKey = $("#id_s_quizaccess_exproctor_awssecretkey");

  // Event trigger when s3 access-key input field value change
  s3AccessKey.on("input", function() {
    enable_submit_button(storageMethod, s3AccessKey, s3SecretKey);
  });

  // Event trigger when s3 secret-key input field value change
  s3SecretKey.on("input", function() {
    enable_submit_button(storageMethod, s3AccessKey, s3SecretKey);
  });
};
