document.addEventListener("DOMContentLoaded", () => {
  const canvas = document.querySelector("#areaCanvas"),
    layer = document.querySelector("#areaBoxes"),
    select = document.querySelector("#areaSelect"),
    form = document.querySelector("#areaForm");
  const selectedId = document.querySelector("#selectedAreaId"),
    fields = {
      x: document.querySelector("#areaX"),
      y: document.querySelector("#areaY"),
      ancho: document.querySelector("#areaWidth"),
      alto: document.querySelector("#areaHeight"),
    };
  let zones = window.AREA_ZONES.map((z) => ({
      ...z,
      x: +z.area_x || 0,
      y: +z.area_y || 0,
      ancho: +z.area_ancho || 120,
      alto: +z.area_alto || 70,
    })),
    drag = null;
  const selected = () => zones.find((z) => String(z.id) === select.value);
  const styleBox = (box, z) => {
    box.style.left = z.x / 12 + "%";
    box.style.top = z.y / 8 + "%";
    box.style.width = z.ancho / 12 + "%";
    box.style.height = z.alto / 8 + "%";
  };
  function syncFields() {
    const z = selected();
    if (!z) return;
    selectedId.value = z.id;
    Object.entries(fields).forEach(
      ([key, input]) => (input.value = z[key].toFixed(1)),
    );
  }
  function render() {
    layer.innerHTML = zones
      .map(
        (z) =>
          `<button type="button" aria-label="Zona ${z.nombre}" class="area-box ${String(z.id) === select.value ? "selected" : ""}" data-id="${z.id}"><i title="Cambiar tamaño"></i></button>`,
      )
      .join("");
    layer.querySelectorAll(".area-box").forEach((box) =>
      styleBox(
        box,
        zones.find((z) => String(z.id) === box.dataset.id),
      ),
    );
    syncFields();
  }
  layer.addEventListener("pointerdown", (e) => {
    const box = e.target.closest(".area-box");
    if (!box) return;
    e.preventDefault();
    select.value = box.dataset.id;
    const z = selected(),
      r = canvas.getBoundingClientRect();
    drag = {
      box,
      z,
      pointer: e.pointerId,
      startX: e.clientX,
      startY: e.clientY,
      x: z.x,
      y: z.y,
      w: z.ancho,
      h: z.alto,
      resize: e.target.tagName === "I",
      r,
    };
    layer.setPointerCapture(e.pointerId);
    layer
      .querySelectorAll(".area-box")
      .forEach((x) => x.classList.toggle("selected", x === box));
    syncFields();
  });
  layer.addEventListener("pointermove", (e) => {
    if (!drag || e.pointerId !== drag.pointer) return;
    const dx = ((e.clientX - drag.startX) * 1200) / drag.r.width,
      dy = ((e.clientY - drag.startY) * 800) / drag.r.height,
      z = drag.z;
    if (drag.resize) {
      z.ancho = Math.min(1200 - z.x, Math.max(20, drag.w + dx));
      z.alto = Math.min(800 - z.y, Math.max(20, drag.h + dy));
    } else {
      z.x = Math.max(0, Math.min(1200 - z.ancho, drag.x + dx));
      z.y = Math.max(0, Math.min(800 - z.alto, drag.y + dy));
    }
    styleBox(drag.box, z);
    syncFields();
  });
  const end = (e) => {
    if (!drag || e.pointerId !== drag.pointer) return;
    drag = null;
  };
  layer.addEventListener("pointerup", end);
  layer.addEventListener("pointercancel", end);
  select.addEventListener("change", render);
  Object.entries(fields).forEach(([key, input]) =>
    input.addEventListener("input", () => {
      const z = selected();
      if (!z) return;
      z[key] = Math.max(
        key === "ancho" || key === "alto" ? 10 : 0,
        +input.value || 0,
      );
      const box = layer.querySelector(`[data-id="${z.id}"]`);
      if (box) styleBox(box, z);
    }),
  );
  form.addEventListener("submit", () => {
    form.zones.value = JSON.stringify(
      zones.map(({ id, x, y, ancho, alto }) => ({ id, x, y, ancho, alto })),
    );
  });
  render();
});
