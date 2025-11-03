document.addEventListener("DOMContentLoaded", () => {
  const tableBody = document.querySelector("#audioTable tbody");
  const exportBtn = document.getElementById("exportBtn");
  const messageDiv = document.getElementById("message");

  // Charger les audios
  fetch("backend/get_audios.php")
    .then(async res => {
      if (!res.ok) throw new Error("Erreur HTTP " + res.status);
      const data = await res.json();
      if (data.status === "success") {
        tableBody.innerHTML = data.data.map(a => `
          <tr>
            <td>${a.id}</td>
            <td><audio controls src="${a.audio_path}"></audio></td>
            <td>${a.transcription}</td>
            <td>${a.traduction}</td>
            <td><button class="delete-btn" onclick="deleteAudio('${a.id}')">Supprimer</button></td>
          </tr>
        `).join("");
      } else {
        showPopup(data.message, "error");
      }
    })
    .catch(err => {
      console.error("Erreur chargement audios :", err);
      showPopup("Erreur lors du chargement des données.", "error");
    });

  // Exporter en JSON
  if (exportBtn) {
    exportBtn.addEventListener("click", async () => {
      try {
        const res = await fetch("backend/export_dataset.php");
        const result = await res.json();
        if (result.status === "success") {
          showPopup(result.message, "success");
        } else {
          showPopup("Erreur : " + result.message, "error");
        }
      } catch (err) {
        console.error("Erreur export :", err);
        showPopup("Erreur lors de l’export! Essayez dans 1 min", "error");
      }
    });
  }
});

async function deleteAudio(id) {
  if (!confirm("Supprimer cet audio ?")) return;
  const formData = new FormData();
  formData.append("id", id);

  try {
    const res = await fetch("backend/delete_audio.php", { method: "POST", body: formData });
    const result = await res.json();
    if (result.status === "success") {
      showPopup("Audio supprimé avec succès.", "success");
      setTimeout(() => location.reload(), 1000);
    } else {
      console.error("Erreur serveur suppression :", result.message);
    }
  } catch (err) {
    console.error("Erreur suppression :", err);
    showPopup("Erreur lors de la suppression.", "error");
  }
}

// Fonction de popup stylée
function showPopup(message, type = "info") {
  const popup = document.createElement("div");
  popup.className = `popup ${type}`;
  popup.textContent = message;
  document.body.appendChild(popup);
  setTimeout(() => popup.classList.add("visible"), 100);
  setTimeout(() => {
    popup.classList.remove("visible");
    setTimeout(() => popup.remove(), 500);
  }, 3000);
}
