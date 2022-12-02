export const store = (attempt_id) => {
    localStorage.setItem("attemptId", attempt_id);
};

export const remove = () => {
    localStorage.removeItem("attemptId");
};