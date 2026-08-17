document.addEventListener("DOMContentLoaded", async () => {
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
  const categoryLabel = (value) =>
    ({
      estandar: "Estándar",
      economico: "Económico",
      "sin categoria": "Sin categoría",
    })[clean(value)] || String(value ?? "");
  const overlay = document.querySelector("#standOverlay"),
    list = document.querySelector("#standList"),
    dialog = document.querySelector("#standDialog"),
    detail = document.querySelector("#standDetail"),
    toast = document.querySelector("#noticeToast"),
    map = document.querySelector(".public-map"),
    mapViewport = document.querySelector(".map-viewport");
  let stands = [];
  let zoom = 1;
  let panStart = null;
  let didPan = false;
  let suppressNextClick = false;

  function setZoom(nextZoom) {
    if (!map) return;

    zoom = Math.min(2, Math.max(0.6, nextZoom));
    map.style.width = `${zoom * 100}%`;
  }

  try {
    const r = await fetch(
      "includes/api/stands.php?pabellon=" + window.PABELLON_ID,
    );
    if (!r.ok) throw Error();
    stands = await r.json();
  } catch {
    list.innerHTML = "<p>No fue posible cargar la disponibilidad.</p>";
    return;
  }
  function open(s) {
    if (!s) return;

    const hasContact = s.contacto || s.email || s.telefono;
    const contact = hasContact && s.estado === "Ocupado"
      ? `<div class="public-contact"><h3>Información de contacto</h3>${s.contacto ? `<p><b>Contacto:</b> ${esc(s.contacto)}</p>` : ""}${s.email ? `<p><b>Correo:</b> <a href="mailto:${esc(s.email)}">${esc(s.email)}</a></p>` : ""}${s.telefono ? `<p><b>Teléfono:</b> <a href="tel:${esc(s.telefono)}">${esc(s.telefono)}</a></p>` : ""}</div>`
      : "";
    const reserveUrl = `reservar.php?stand=${encodeURIComponent(s.numero)}&id=${encodeURIComponent(s.id)}`;
    detail.innerHTML = `<span class="status ${s.estado === "Ocupado" ? "busy" : "free"}">${esc(s.estado)}</span><h2>${esc(s.numero)}</h2><p><b>Tipo de espacio:</b> ${esc(categoryLabel(s.categoria))}</p>${s.empresa ? `<p><b>Empresa:</b> ${esc(s.empresa)}</p>` : ""}${s.logo ? `<img class="company-logo" src="${esc(s.logo)}" alt="Logo de empresa">` : ""}${contact}${s.estado !== "Ocupado" ? `<a class="reserve-button" href="${reserveUrl}" target="_blank" rel="noopener">Apartar espacio</a>` : '<p class="occupied-message">Este espacio ya está ocupado.</p>'}${window.EDIT_URL ? `<a class="modal-edit" href="${window.EDIT_URL}${s.id}">Editar este espacio</a>` : ""}`;
    dialog.showModal();
  }
  function render() {
    const cat = document.querySelector("#filtroCategoria").value,
      state = document.querySelector("#filtroEstado").value,
      matches = (s) =>
        (!cat || clean(s.categoria) === clean(cat)) &&
        (!state || s.estado === state),
      filtered = stands.filter(matches);
    list.innerHTML = filtered.length
      ? filtered
          .map(
            (s) =>
              `<button class="stand-row" data-id="${s.id}"><span><b>${esc(s.numero)}</b><small>${esc(categoryLabel(s.categoria))}</small></span><em class="${s.estado === "Ocupado" ? "busy" : "free"}">${esc(s.estado)}</em></button>`,
          )
          .join("")
      : "<p>No hay espacios con esos filtros.</p>";
    const categoryClass = (s) =>
      ({
        premium: "premium",
        estandar: "standard",
        economico: "economic",
        pymes: "pymes",
        "sin categoria": "uncategorized",
      })[
        clean(s.categoria)
      ] || "uncategorized";
    const standNumberLabel = (s) => {
      const mapScale = Math.max(1, Number(window.PAVILION_WIDTH || 696) / 696);
      const pavilionKey = String(window.PAVILION_KEY || "").toUpperCase();
      const compactLabel = ["A", "B", "C"].includes(pavilionKey);
      const chaletLabel = pavilionKey === "CH";
      const padding = (compactLabel ? 3 : 6) * mapScale;
      const x = chaletLabel
        ? Number(s.x) + Number(s.ancho) / 2
        : Number(s.x) + Number(s.ancho) - padding;
      const y = chaletLabel
        ? Number(s.y) + Number(s.alto) / 2
        : Number(s.y) + padding;
      const size = chaletLabel ? 10 : (compactLabel ? 6.5 : 10) * mapScale;
      const textAnchor = chaletLabel ? "middle" : "end";
      const baseline = chaletLabel ? "middle" : "hanging";

      return `<text class="stand-number-label" x="${x}" y="${y}" font-size="${size}" text-anchor="${textAnchor}" dominant-baseline="${baseline}" pointer-events="none">${esc(s.numero)}</text>`;
    };
    overlay.innerHTML = stands
      .filter((s) => s.x !== null)
      .map(
        (s) =>
          `<g data-id="${s.id}" class="map-space${matches(s) ? "" : " is-filtered-out"}">${s.estado === "Ocupado" && s.logo ? `<rect class="stand-logo-bg" x="${s.x}" y="${s.y}" width="${s.ancho}" height="${s.alto}" pointer-events="none"/><image class="stand-logo-image" href="${esc(s.logo)}" x="${s.x}" y="${s.y}" width="${s.ancho}" height="${s.alto}" preserveAspectRatio="xMidYMid meet" pointer-events="none"/>` : ""}<rect x="${s.x}" y="${s.y}" width="${s.ancho}" height="${s.alto}" class="map-stand ${s.estado === "Ocupado" ? `occupied${s.logo ? " has-logo" : ""}` : categoryClass(s)}" pointer-events="none"><title>${esc(s.numero)} · ${esc(categoryLabel(s.categoria))}</title></rect>${standNumberLabel(s)}<rect class="map-stand-hitbox" x="${s.x}" y="${s.y}" width="${s.ancho}" height="${s.alto}" fill="transparent" pointer-events="all"/></g>`,
      )
      .join("");
    overlay.querySelectorAll(".map-space").forEach((space) => {
      space.addEventListener("click", (event) => {
        event.stopPropagation();
        if (suppressNextClick) {
          suppressNextClick = false;
          return;
        }

        if (didPan) {
          didPan = false;
          return;
        }

        openFromElement(space);
      });
    });
  }
  function openFromElement(el) {
    if (!el) return;
    open(stands.find((s) => String(s.id) === el.dataset.id));
  }

  list.addEventListener("click", (event) => {
    openFromElement(event.target.closest("[data-id]"));
  });
  overlay.addEventListener("click", (event) => {
    if (suppressNextClick) {
      suppressNextClick = false;
      return;
    }

    if (didPan) {
      didPan = false;
      return;
    }

    openFromElement(event.target.closest("[data-id]"));
  });
  document.querySelector("#filtroPabellon").onchange = (e) =>
    (location.href = "pabellon.php?id=" + encodeURIComponent(e.target.value));
  document
    .querySelectorAll(".filters select:not(#filtroPabellon)")
    .forEach((x) => (x.onchange = render));
  document.querySelectorAll("[data-zoom]").forEach((button) => {
    button.addEventListener("click", () => {
      const action = button.dataset.zoom;
      if (action === "in") setZoom(zoom + 0.1);
      if (action === "out") setZoom(zoom - 0.1);
    });
  });
  mapViewport?.addEventListener("contextmenu", (event) => {
    event.preventDefault();
  });
  mapViewport?.addEventListener("pointerdown", (event) => {
    if (event.button !== 0) return;

    panStart = {
      pointerId: event.pointerId,
      x: event.clientX,
      y: event.clientY,
      left: mapViewport.scrollLeft,
      top: mapViewport.scrollTop,
    };
    mapViewport.classList.add("is-panning");
    mapViewport.setPointerCapture(event.pointerId);
    event.preventDefault();
  });
  mapViewport?.addEventListener("pointermove", (event) => {
    if (!panStart || event.pointerId !== panStart.pointerId) return;

    const dx = event.clientX - panStart.x;
    const dy = event.clientY - panStart.y;
    if (Math.abs(dx) > 3 || Math.abs(dy) > 3) {
      didPan = true;
    }

    mapViewport.scrollLeft = panStart.left - dx;
    mapViewport.scrollTop = panStart.top - dy;
  });
  mapViewport?.addEventListener("pointerup", (event) => {
    if (!didPan) {
      const target = document
        .elementsFromPoint(event.clientX, event.clientY)
        .find((el) => el.closest?.(".map-space"))
        ?.closest(".map-space");

      if (target) {
        suppressNextClick = true;
        openFromElement(target);
      }
    }

    panStart = null;
    mapViewport.classList.remove("is-panning");
  });
  mapViewport?.addEventListener("pointercancel", () => {
    panStart = null;
    mapViewport.classList.remove("is-panning");
  });
  document.querySelector(".dialog-close").onclick = () => dialog.close();
  setZoom(1);
  render();
});
