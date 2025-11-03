document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("dataForm");
  const audioInput = document.getElementById("audio");

  // Validate client-side audio type
  audioInput.addEventListener("change", () => {
    const file = audioInput.files[0];
    if (file) {
      const allowedTypes = ["audio/wav", "audio/x-wav", "audio/mpeg"];
      if (!allowedTypes.includes(file.type)) {
        showPopup("Seuls les fichiers WAV ou MP3 sont acceptés.", "error");
        audioInput.value = "";
      }
    }
  });

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const formData = new FormData(form);
    const audioFile = formData.get("audio");
    const transcription = (formData.get("transcription") || "").trim();
    const traduction = (formData.get("traduction") || "").trim();

    // Quick client-side validation
    if (!audioFile || !transcription || !traduction) {
      showPopup("Tous les champs sont obligatoires.", "error");
      return;
    }

    showPopup("Envoi en cours...", "info");

    try {
      const response = await fetch("backend/upload.php", {
        method: "POST",
        body: formData,
        cache: "no-store"
      });

      const text = await response.text();

      let result;
      try {
        result = JSON.parse(text);
      } catch (parseErr) {
        console.error("Réponse serveur invalide (brute) :", text);
        showPopup("Le serveur a renvoyé une réponse invalide. Veuillez réessayer.", "error");
        return;
      }

      // If server indicates success or error
      if (result && result.status === "success") {
        showPopup(result.message || "✅ Formulaire enregistré.", "success");
        form.reset();
        setTimeout(() => window.location.reload(), 1500);
      } else {
        const userMsg = result && result.message ? result.message : "Une erreur est survenue. Veuillez réessayer.";
        showPopup("❌ " + userMsg, "error");

        if (result && result._debug) {
          console.debug("Détails debug côté serveur :", result._debug);
        }
      }

    } catch (networkErr) {
      // Network / fetch error: show generic message, log detailed in console
      console.error("Erreur réseau / fetch :", networkErr);
      showPopup("Erreur de connexion au serveur. Vérifiez votre connexion.", "error");
    }
  });
});

// Fonction pop-up 
function showPopup(message, type = "info") {
  const popup = document.createElement("div");
  popup.className = `popup ${type}`;
  popup.textContent = message;
  document.body.appendChild(popup);

  setTimeout(() => popup.classList.add("visible"), 50);

  setTimeout(() => {
    popup.classList.remove("visible");
    setTimeout(() => popup.remove(), 400);
  }, 3000);
}
