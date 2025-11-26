document.addEventListener("DOMContentLoaded", () => {
  const tableBody = document.querySelector("#audioTable tbody");
  const exportBtn = document.getElementById("exportBtn");
  const deleteAllBtn = document.getElementById("deleteAllBtn");

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
          showPopup( + result.total + " audios exportés", "success");
          setTimeout(() => location.reload(), 1000);
        } else {
          showPopup("Erreur : " + result.message, "error");
        }
      } catch (err) {
        console.error("Erreur export :", err);
        showPopup("Erreur lors de l’export! Essayez dans 1 min", "error");
      }
    });
  }

  // SUPPRESSION TOTALE
  if (deleteAllBtn) {
    deleteAllBtn.addEventListener("click", async () => {
      const confirmDelete = await confirmModal("Voulez-vous vraiment supprimer TOUS les audios ?");
      if (!confirmDelete) return;

      try {
        // Utiliser FormData (compatible avec PHP et cohérent)
        const fd = new FormData();
        fd.append("action", "delete_all");

        const res = await fetch("backend/delete_audio.php", {
          method: "POST",
          body: fd
        });

        // parser réponse
        const result = await res.json();

        if (result.status === "success") {
          showPopup(result.message, "success");
          setTimeout(() => location.reload(), 700);
        } else {
          showPopup(result.message || "Erreur suppression massive", "error");
        }
      } catch (error) {
        console.error("Erreur suppression massive :", error);
        showPopup("Erreur serveur lors de la suppression massive", "error");
      }
    });
  }
});

// suppression d'un audio
async function deleteAudio(id) {
  const confirmDelete = await confirmModal("Supprimer cet audio ?");
  if (!confirmDelete) return;

  const formData = new FormData();
  formData.append("id", id);

  try {
    const res = await fetch("backend/delete_audio.php", { method: "POST", body: formData });
    const result = await res.json();
    if (result.status === "success") {
      showPopup("Audio supprimé avec succès.", "success");
      setTimeout(() => location.reload(), 1000);
    } else {
      showPopup(result.message || "Erreur lors de la suppression.", "error");
      console.error("Erreur serveur suppression :", result);
    }
  } catch (err) {
    console.error("Erreur suppression :", err);
    showPopup("Erreur lors de la suppression.", "error");
  }
}

  // popup (inchangé)
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

  // model de confirmation personnalisée
  function confirmModal(message) {
    return new Promise((resolve) => {

      const overlay = document.createElement("div");
      overlay.className = "confirm-overlay";

      const box = document.createElement("div");
      box.className = "confirm-box";

      box.innerHTML = `
        <h3>${message}</h3>
        <div class="confirm-actions">
          <button class="confirm-no">Non</button>
          <button class="confirm-yes">Oui</button>
        </div>
      `;

      overlay.appendChild(box);
      document.body.appendChild(overlay);

      overlay.querySelector(".confirm-no").onclick = () => {
        overlay.remove();
        resolve(false);
      };

      overlay.querySelector(".confirm-yes").onclick = () => {
        overlay.remove();
        resolve(true);
      };

    });
  }
