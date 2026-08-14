document.addEventListener("DOMContentLoaded", async () => {
  const svg = document.querySelector("#managementOverlay");
  if (!svg) return;
  const esc = (s) =>
    String(s ?? "").replace(
      /[&<>"']/g,
      (c) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        })[c],
    );
  const clean = (s) =>
    String(s ?? "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "");
  const cls = (s) =>
    ({
      premium: "premium",
      estandar: "standard",
      economico: "economic",
      pymes: "pymes",
        "sin categoria": "uncategorized",
    })[clean(s.categoria)] || "uncategorized";
  const filters = window.MANAGEMENT_FILTERS || {};
  const categoryLabel = (value) =>
    ({
      estandar: "Estándar",
      economico: "Económico",
      "sin categoria": "Sin categoría",
    })[clean(value)] || String(value ?? "");
  const matches = (s) => {
    const query = clean(filters.q).trim();
    return (!query || clean(`${s.numero} ${s.empresa || ""}`).includes(query)) &&
      (!filters.categoria || clean(s.categoria) === clean(filters.categoria)) &&
      (!filters.estado || s.estado === filters.estado);
  };
  const standNumberLabel = (s) => {
    // Normaliza la etiqueta para planos con viewBox de distinta resolución.
    const mapScale = Math.max(1, Number(window.MANAGEMENT_PAVILION_WIDTH || 696) / 696);
    const compactLabel = ["A", "B", "C"].includes(
      String(window.MANAGEMENT_PAVILION_KEY || "").toUpperCase(),
    );
    const padding = (compactLabel ? 3 : 6) * mapScale;
    const x = Number(s.x) + Number(s.ancho) - padding;
    const y = Number(s.y) + padding;
    const size = (compactLabel ? 6.5 : 10) * mapScale;

    return `<text class="stand-number-label" x="${x}" y="${y}" font-size="${size}" text-anchor="end" dominant-baseline="hanging" pointer-events="none">${esc(s.numero)}</text>`;
  };
  try {
    const response = await fetch(
        "../../includes/api/stands.php?pabellon=" + window.MANAGEMENT_PAVILION,
      ),
      stands = await response.json();
    svg.innerHTML = stands
      .filter((s) => s.x !== null)
      .map(
        (s) =>
          `<g class="management-space${matches(s) ? "" : " is-filtered-out"}" data-id="${s.id}">${s.estado === "Ocupado" && s.logo ? `<rect class="stand-logo-bg" x="${s.x}" y="${s.y}" width="${s.ancho}" height="${s.alto}" pointer-events="none"/><image class="stand-logo-image" href="../../${esc(s.logo)}" x="${s.x}" y="${s.y}" width="${s.ancho}" height="${s.alto}" preserveAspectRatio="xMidYMid meet" pointer-events="none"/>` : ""}<rect x="${s.x}" y="${s.y}" width="${s.ancho}" height="${s.alto}" class="${s.estado === "Ocupado" ? `occupied${s.logo ? " has-logo" : ""}` : cls(s)}" pointer-events="none"><title>${esc(s.numero)} · ${esc(categoryLabel(s.categoria))}</title></rect>${standNumberLabel(s)}<rect class="stand-hitbox" x="${s.x}" y="${s.y}" width="${s.ancho}" height="${s.alto}"/></g>`,
      )
      .join("");
    svg
      .querySelectorAll("[data-id]")
      .forEach(
        (g) =>
          (g.onclick = () =>
            (location.href = "editar_stand.php?id=" + g.dataset.id)),
      );
  } catch {
    svg.innerHTML =
      '<text x="20" y="40">No fue posible cargar los espacios.</text>';
  }
});
