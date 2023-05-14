export const store = (attempt_id, bucketName) => {
    localStorage.setItem("attemptId", attempt_id);
    localStorage.setItem("bucketName", bucketName);
};

export const remove = () => {
    localStorage.clear();
};