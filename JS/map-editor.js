document.addEventListener("DOMContentLoaded", () => {
  const cfg = window.MAP_EDITOR;
  const svg = document.querySelector("#editorOverlay");
  const form = document.querySelector("#boxForm");
  const advancedButton = document.querySelector("#advancedEdit");
  const saveButton = document.querySelector("#saveSelection");
  const deleteButton = document.querySelector("#deleteBox");
  const globalFontInput = document.querySelector("#globalNumberFontSize");
  const applyGlobalFontButton = document.querySelector("#applyGlobalNumberFont");
  const finishLink = document.querySelector("#finishEditor");
  const selectionStatus = document.querySelector("#selectionStatus");
  const coordinateNames = ["x", "y", "ancho", "alto"];

  let data = cfg.stands.map((stand) => ({
    ...stand,
    x: Number(stand.x),
    y: Number(stand.y),
    ancho: Number(stand.ancho),
    alto: Number(stand.alto),
    numero_font_size: stand.numero_font_size === null || stand.numero_font_size === undefined
      ? ""
      : Number(stand.numero_font_size),
  }));
  let selectedStand = null;
  let selectedKeys = new Set();
  let interaction = null;
  let draft = null;
  let mode = "create";
  let dirty = false;
  let saving = false;
  let editingSnapshot = null;
  let tempCounter = 0;
  const dirtyKeys = new Set();

  const keyOf = (stand) =>
    stand.temporary ? `__new_${stand.tempId}` : String(stand.id);
  const rounded = (value) => Number(value || 0).toFixed(1);
  const escapeText = (value) =>
    String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;");
  const categoryValue = (value) =>
    ({
      estandar: "Estandar",
      economico: "Economico",
      pymes: "PyMES",
      premium: "Premium",
    })[
      String(value ?? "")
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
    ] || "Premium";

  const point = (event) => {
    const bounds = svg.getBoundingClientRect();
    return {
      x: ((event.clientX - bounds.left) * cfg.width) / bounds.width,
      y: ((event.clientY - bounds.top) * cfg.height) / bounds.height,
    };
  };

  const selectedExistingIds = () =>
    data
      .filter((stand) => !stand.temporary && selectedKeys.has(keyOf(stand)))
      .map((stand) => Number(stand.id));

  function setDirty(value) {
    dirty = value;
    const blocked = saving;
    finishLink.classList.toggle("is-disabled", blocked);
    finishLink.setAttribute("aria-disabled", String(blocked));
  }

  function markDirty(stand = selectedStand) {
    if (!stand) return;

    dirtyKeys.add(keyOf(stand));
    setDirty(true);
  }

  function setSaving(value) {
    saving = value;
    saveButton.disabled = value;
    deleteButton.disabled = value;
    advancedButton.disabled = value;
    setDirty(dirty);
  }

  function clearFields() {
    for (const name of ["stand_id", "numero", "numero_font_size", ...coordinateNames]) {
      form[name].value = "";
    }
    form.categoria.value = "Premium";
  }

  function syncFields() {
    if (!selectedStand) {
      clearFields();
      return;
    }

    form.stand_id.value = selectedStand.id || "";
    form.numero.value = selectedStand.numero || "";
    form.categoria.value = categoryValue(selectedStand.categoria);
    form.numero_font_size.value = selectedStand.numero_font_size || "";
    coordinateNames.forEach((name) => {
      form[name].value = rounded(selectedStand[name]);
    });
  }

  function updateActions() {
    const existingCount = selectedExistingIds().length;
    const hasSelection = Boolean(selectedStand);

    saveButton.hidden = true;
    saveButton.textContent = selectedStand?.temporary
      ? "Agregar recuadro"
      : "Guardar cambios";

    deleteButton.hidden = mode !== "advanced" || existingCount === 0;
    deleteButton.textContent =
      existingCount > 1
        ? `Eliminar selección (${existingCount})`
        : "Eliminar recuadro";

    if (selectedStand?.temporary) {
      selectionStatus.textContent =
        "Recuadro nuevo listo para completar y agregar.";
    } else if (existingCount > 1) {
      selectionStatus.textContent = `${existingCount} recuadros seleccionados. Las propiedades corresponden al último seleccionado.`;
    } else if (existingCount === 1) {
      selectionStatus.textContent =
        "Editando un recuadro existente. Puedes moverlo, ajustar su tamaño o guardar sus propiedades.";
    } else {
      selectionStatus.textContent =
        mode === "advanced"
          ? "Selecciona un recuadro. Usa Ctrl, Cmd o Shift + clic para seleccionar varios."
          : "Arrastra sobre el plano para dibujar un recuadro nuevo.";
    }
  }

  function render() {
    svg.innerHTML =
      data
        .map((stand) => {
          const key = keyOf(stand);
          const selected = selectedKeys.has(key);
          const primary = selectedStand === stand;
          const resizeHandle =
            primary && mode === "advanced"
              ? `<rect data-id="${key}" data-handle="resize" x="${stand.x + stand.ancho - 6}" y="${stand.y + stand.alto - 6}" width="12" height="12" class="editor-resize-handle"><title>Ajustar tamaño</title></rect>`
              : "";

          const numberSize = Number(stand.numero_font_size) || Math.max(6, Math.min(18, stand.alto * 0.28));
          return `
            <g>
              <rect
                data-id="${key}"
                x="${stand.x}"
                y="${stand.y}"
                width="${stand.ancho}"
                height="${stand.alto}"
                class="editor-box${selected ? " selected" : ""}${primary ? " primary" : ""}"
              >
                <title>${escapeText(stand.numero)}</title>
              </rect>
              <text
                class="editor-number-label"
                x="${stand.x + stand.ancho / 2}"
                y="${stand.y + stand.alto / 2}"
                font-size="${numberSize}"
                text-anchor="middle"
                dominant-baseline="middle"
                pointer-events="none"
              >${escapeText(stand.numero)}</text>
              ${resizeHandle}
            </g>
          `;
        })
        .join("") +
      (draft
        ? `<rect x="${draft.x}" y="${draft.y}" width="${draft.ancho}" height="${draft.alto}" class="editor-box draft"/>`
        : "");
  }

  function refresh() {
    syncFields();
    updateActions();
    render();
  }

  function clearSelection() {
    if (selectedStand && dirty) {
      markDirty(selectedStand);
    }
    selectedStand = null;
    selectedKeys.clear();
    editingSnapshot = null;
    draft = null;
    refresh();
  }

  function selectStand(stand, additive = false) {
    if (!stand) {
      clearSelection();
      return;
    }

    const key = keyOf(stand);
    if (additive) {
      if (selectedKeys.has(key)) {
        selectedKeys.delete(key);
        selectedStand =
          data.findLast((item) => selectedKeys.has(keyOf(item))) || null;
      } else {
        selectedKeys.add(key);
        selectedStand = stand;
      }
    } else if (!selectedKeys.has(key)) {
      selectedKeys = new Set([key]);
      selectedStand = stand;
    } else {
      selectedStand = stand;
    }
    editingSnapshot = selectedStand ? structuredClone(selectedStand) : null;
    refresh();
  }

  function setMode(nextMode) {
    mode = nextMode;
    svg.dataset.mode = mode;
    const advanced = mode === "advanced";
    advancedButton.classList.toggle("active", advanced);
    advancedButton.setAttribute("aria-pressed", String(advanced));
    advancedButton.textContent = advanced
      ? "Edición avanzada activada"
      : "Edición avanzada";
    updateActions();
    render();
  }

  svg.addEventListener("pointerdown", (event) => {
    const standKey = event.target.dataset.id;
    const startPoint = point(event);

    if (standKey && mode === "advanced") {
      const stand = data.find((item) => keyOf(item) === standKey);
      if (!stand) return;

      const additive = event.ctrlKey || event.metaKey || event.shiftKey;
      if (selectedStand && selectedStand !== stand) {
        markDirty(selectedStand);
      }
      selectStand(stand, additive);
      if (additive || !selectedKeys.has(standKey)) return;

      interaction = {
        type: event.target.dataset.handle === "resize" ? "resize" : "move",
        pointerId: event.pointerId,
        startPoint,
        original: {
          x: stand.x,
          y: stand.y,
          ancho: stand.ancho,
          alto: stand.alto,
        },
        stand,
      };
    } else if (mode === "create") {
      selectedStand = null;
      selectedKeys.clear();
      interaction = {
        type: "create",
        pointerId: event.pointerId,
        startPoint,
      };
      draft = {
        x: startPoint.x,
        y: startPoint.y,
        ancho: 0,
        alto: 0,
      };
      updateActions();
    } else {
      return;
    }

    event.preventDefault();
    svg.setPointerCapture(event.pointerId);
  });

  svg.addEventListener("pointermove", (event) => {
    if (!interaction || event.pointerId !== interaction.pointerId) return;

    const currentPoint = point(event);
    const dx = currentPoint.x - interaction.startPoint.x;
    const dy = currentPoint.y - interaction.startPoint.y;

    if (interaction.type === "create") {
      setDirty(true);
      draft = {
        x: Math.max(0, Math.min(interaction.startPoint.x, currentPoint.x)),
        y: Math.max(0, Math.min(interaction.startPoint.y, currentPoint.y)),
        ancho: Math.abs(dx),
        alto: Math.abs(dy),
      };
    } else if (interaction.type === "move") {
      const { stand, original } = interaction;
      markDirty(stand);
      stand.x = Math.max(0, Math.min(cfg.width - stand.ancho, original.x + dx));
      stand.y = Math.max(0, Math.min(cfg.height - stand.alto, original.y + dy));
      syncFields();
    } else if (interaction.type === "resize") {
      const { stand, original } = interaction;
      markDirty(stand);
      stand.ancho = Math.max(
        5,
        Math.min(cfg.width - stand.x, original.ancho + dx),
      );
      stand.alto = Math.max(
        5,
        Math.min(cfg.height - stand.y, original.alto + dy),
      );
      syncFields();
    }
    render();
  });

  function finishInteraction(event) {
    if (!interaction || event.pointerId !== interaction.pointerId) return;

    if (
      interaction.type === "create" &&
      draft &&
      draft.ancho > 5 &&
      draft.alto > 5
    ) {
      const newStand = {
        id: "",
        tempId: ++tempCounter,
        temporary: true,
        numero: "",
        categoria: "Premium",
        x: draft.x,
        y: draft.y,
        ancho: draft.ancho,
        alto: draft.alto,
        numero_font_size: "",
      };
      data.push(newStand);
      selectedStand = newStand;
      selectedKeys = new Set([keyOf(newStand)]);
      markDirty(newStand);
      editingSnapshot = null;
      draft = null;
      refresh();
      form.numero.focus();
    } else {
      draft = null;
      if (interaction.type === "create") setDirty(dirtyKeys.size > 0);
      refresh();
    }

    interaction = null;
  }

  svg.addEventListener("pointerup", finishInteraction);
  svg.addEventListener("pointercancel", finishInteraction);

  coordinateNames.forEach((name) => {
    form[name].addEventListener("change", () => {
      if (!selectedStand) return;

      selectedStand[name] = Number(form[name].value) || 0;
      markDirty(selectedStand);
      selectedStand.ancho = Math.max(
        5,
        Math.min(cfg.width - selectedStand.x, selectedStand.ancho),
      );
      selectedStand.alto = Math.max(
        5,
        Math.min(cfg.height - selectedStand.y, selectedStand.alto),
      );
      selectedStand.x = Math.max(
        0,
        Math.min(cfg.width - selectedStand.ancho, selectedStand.x),
      );
      selectedStand.y = Math.max(
        0,
        Math.min(cfg.height - selectedStand.alto, selectedStand.y),
      );
      refresh();
    });
  });

  for (const field of [form.numero, form.categoria, form.numero_font_size]) {
    field.addEventListener("input", () => {
      if (!selectedStand) return;

      selectedStand.numero = form.numero.value.trim();
      selectedStand.categoria = form.categoria.value;
      selectedStand.numero_font_size = form.numero_font_size.value
        ? Math.min(120, Math.max(4, Number(form.numero_font_size.value)))
        : "";
      markDirty(selectedStand);
    });
  }

  function syncSelectedFromForm() {
    if (!selectedStand) return;

    selectedStand.numero = form.numero.value.trim();
    selectedStand.categoria = form.categoria.value;
    selectedStand.numero_font_size = form.numero_font_size.value
      ? Math.min(120, Math.max(4, Number(form.numero_font_size.value)))
      : "";
    coordinateNames.forEach((name) => {
      form[name].value = rounded(form[name].value);
      selectedStand[name] = Number(form[name].value);
    });
  }

  async function saveStand(stand) {
    if (!stand.numero || String(stand.numero).trim() === "") {
      selectStand(stand);
      form.numero.focus();
      throw new Error("Escribe el número de todos los recuadros nuevos o modificados.");
    }

    const oldKey = keyOf(stand);
    const formData = new FormData();
    formData.set("csrf", form.csrf.value);
    formData.set("pabellon_id", form.pabellon_id.value);
    formData.set("stand_id", stand.id || "");
    formData.set("action", "save");
    formData.set("numero", stand.numero);
    formData.set("categoria", stand.categoria || "Premium");
    formData.set("numero_font_size", stand.numero_font_size || "");
    coordinateNames.forEach((name) => {
      formData.set(name, rounded(stand[name]));
    });

    const response = await fetch(location.href, {
      method: "POST",
      body: formData,
    });
    const result = await response.json();

    if (!result.ok) {
      throw new Error(result.error || "No fue posible guardar los cambios.");
    }

    const saved = {
      ...result.stand,
      x: Number(result.stand.x),
      y: Number(result.stand.y),
      ancho: Number(result.stand.ancho),
      alto: Number(result.stand.alto),
      numero_font_size: result.stand.numero_font_size === null || result.stand.numero_font_size === undefined
        ? ""
        : Number(result.stand.numero_font_size),
    };
    Object.assign(stand, saved, { temporary: false });
    delete stand.tempId;
    dirtyKeys.delete(oldKey);
    dirtyKeys.delete(keyOf(stand));
  }

  async function savePendingChanges() {
    if (selectedStand) {
      syncSelectedFromForm();
      markDirty(selectedStand);
    }

    const pending = data.filter((stand) => dirtyKeys.has(keyOf(stand)));
    if (!pending.length) return;

    setSaving(true);
    try {
      for (const stand of pending) {
        await saveStand(stand);
      }
      setDirty(false);
      dirtyKeys.clear();
      refresh();
    } finally {
      setSaving(false);
    }
  }

  applyGlobalFontButton?.addEventListener("click", async () => {
    if (saving) return;

    const raw = globalFontInput.value.trim();
    if (raw === "") {
      alert("Escribe un tamaño para aplicarlo a todos.");
      globalFontInput.focus();
      return;
    }

    const nextSize = Math.min(120, Math.max(4, Number(raw)));
    if (!Number.isFinite(nextSize)) {
      alert("Escribe un tamaño válido.");
      globalFontInput.focus();
      return;
    }

    data.forEach((stand) => {
      stand.numero_font_size = nextSize;
      dirtyKeys.add(keyOf(stand));
    });
    if (selectedStand) form.numero_font_size.value = String(nextSize);
    setDirty(true);
    refresh();

    try {
      await savePendingChanges();
      selectionStatus.textContent = `Tamaño ${nextSize} aplicado a todos los números.`;
    } catch (error) {
      alert(error.message || "No fue posible guardar el tamaño para todos.");
    }
  });

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (!selectedStand || saving) return;

    selectedStand.numero = form.numero.value.trim();
    selectedStand.categoria = form.categoria.value;
    selectedStand.numero_font_size = form.numero_font_size.value
      ? Math.min(120, Math.max(4, Number(form.numero_font_size.value)))
      : "";
    coordinateNames.forEach((name) => {
      form[name].value = rounded(form[name].value);
      selectedStand[name] = Number(form[name].value);
    });

    const wasTemporary = Boolean(selectedStand.temporary);
    setSaving(true);
    saveButton.textContent = "Guardando…";
    try {
      const response = await fetch(location.href, {
        method: "POST",
        body: new FormData(form),
      });
      const result = await response.json();

      if (!result.ok) {
        alert(result.error);
        return;
      }

      const saved = {
        ...result.stand,
        x: Number(result.stand.x),
        y: Number(result.stand.y),
        ancho: Number(result.stand.ancho),
        alto: Number(result.stand.alto),
        numero_font_size: result.stand.numero_font_size === null || result.stand.numero_font_size === undefined
          ? ""
          : Number(result.stand.numero_font_size),
      };
      if (wasTemporary) selectedKeys.delete(keyOf(selectedStand));
      Object.assign(selectedStand, saved, { temporary: false });
      delete selectedStand.tempId;
      selectedKeys.add(String(saved.id));
      dirtyKeys.delete(keyOf(selectedStand));
      editingSnapshot = structuredClone(selectedStand);
      setDirty(false);

      if (wasTemporary) {
        clearSelection();
        selectionStatus.textContent =
          "Recuadro agregado permanentemente. Puedes dibujar el siguiente.";
      } else {
        refresh();
        selectionStatus.textContent =
          "Cambios guardados permanentemente y visibles para los demás usuarios.";
      }
    } catch (error) {
      alert("No fue posible guardar. Revisa la conexión e inténtalo de nuevo.");
    } finally {
      setSaving(false);
      updateActions();
    }
  });

  saveButton.addEventListener("click", () => form.requestSubmit());

  deleteButton.addEventListener("click", async () => {
    const ids = selectedExistingIds();
    if (!ids.length) return;

    const message =
      ids.length === 1
        ? "¿Eliminar el recuadro seleccionado?"
        : `¿Eliminar los ${ids.length} recuadros seleccionados?`;
    if (!confirm(message)) return;

    const formData = new FormData(form);
    formData.set("action", "delete");
    formData.set("stand_ids", JSON.stringify(ids));
    setSaving(true);
    try {
      const response = await fetch(location.href, {
        method: "POST",
        body: formData,
      });
      const result = await response.json();

      if (!result.ok) {
        alert(result.error);
        return;
      }

      const deleted = new Set(ids.map(String));
      data = data.filter((stand) => !deleted.has(String(stand.id)));
      setDirty(false);
      clearSelection();
      selectionStatus.textContent =
        ids.length === 1
          ? "Recuadro eliminado permanentemente."
          : `${ids.length} recuadros eliminados permanentemente.`;
    } catch (error) {
      alert(
        "No fue posible eliminar. Revisa la conexión e inténtalo de nuevo.",
      );
    } finally {
      setSaving(false);
    }
  });

  advancedButton.addEventListener("click", () => {
    if (selectedStand && dirty) {
      markDirty(selectedStand);
    }
    const activate = mode !== "advanced";
    clearSelection();
    setMode(activate ? "advanced" : "create");
  });

  document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;
    if (selectedStand?.temporary) {
      data = data.filter((stand) => !stand.temporary);
    } else if (dirty && selectedStand && editingSnapshot) {
      Object.assign(selectedStand, editingSnapshot);
    }
    setDirty(false);
    clearSelection();
  });

  finishLink.addEventListener("click", async (event) => {
    if (saving) {
      event.preventDefault();
      alert("Espera a que termine el guardado antes de salir.");
      return;
    }

    if (!dirty) return;

    event.preventDefault();
    finishLink.textContent = "Guardando...";
    try {
      await savePendingChanges();
      location.href = finishLink.href;
    } catch (error) {
      alert(error.message || "No fue posible guardar los cambios.");
      finishLink.textContent = "Terminar";
    }
  });

  window.addEventListener("beforeunload", (event) => {
    if (!dirty && !saving) return;
    event.preventDefault();
    event.returnValue = "";
  });

  setMode("create");
  refresh();
});
