document.addEventListener("DOMContentLoaded", function () {
    const forms = document.querySelectorAll("form");

    forms.forEach((form) => {
        const select = form.querySelector("select[name='status']");
        const electionIdInput = form.querySelector("input[name='election_id']");
        const updateButton = form.querySelector("button[name='update_election']");

        if (updateButton) {
            updateButton.addEventListener("click", function (e) {
                e.preventDefault();

                const status = select.value;
                const election_id = electionIdInput.value;

                fetch("update_status.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `election_id=${election_id}&status=${status}`
                })
                .then((res) => res.json())
                .then((data) => {
                    const msgDiv = document.createElement("div");
                    msgDiv.className = "status-message";

                    if (data.status === "success") {
                        msgDiv.textContent = "Status updated!";
                        msgDiv.style.color = "green";
                    } else {
                        msgDiv.textContent = "Update failed!";
                        msgDiv.style.color = "red";
                    }

                    form.appendChild(msgDiv);
                    setTimeout(() => msgDiv.remove(), 3000);
                })
                .catch((err) => {
                    console.error("AJAX error:", err);
                });
            });
        }
    });
});
