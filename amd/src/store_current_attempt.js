export const store = (attempt_id) => {
    localStorage.setItem("attemptId", attempt_id);
    return true;
};

export const remove = () => {
    localStorage.removeItem("attemptId");
    return true;
};