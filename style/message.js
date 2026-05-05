document.addEventListener("DOMContentLoaded", () => {
    const msgs = document.querySelectorAll('.error, .success');

    msgs.forEach(msg => {
        setTimeout(() => {
            msg.style.transition = "opacity 2s ease";
            msg.style.opacity = "0";

            setTimeout(() => {
                msg.remove();
            }, 2000);
        }, 3000);
    });
});