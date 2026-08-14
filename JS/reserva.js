document.addEventListener("DOMContentLoaded", () => {
  const radios = document.querySelectorAll('[name="stands_adicionales"]');
  const block = document.querySelector("#additionalStandBlock");
  const rows = document.querySelector("#additionalStandRows");
  const template = document.querySelector("#additionalStandTemplate");
  const addButton = document.querySelector("#addStandRow");

  const filterStandOptions = (row) => {
    const pavilion = row.querySelector('[name="pabellon_adicional[]"]');
    const stand = row.querySelector('[name="numero_adicional[]"]');
    const pavilionId = pavilion.value;

    Array.from(stand.options).forEach((option, index) => {
      const isHidden = index > 0 && option.dataset.pavilion !== pavilionId;
      option.hidden = isHidden;
      option.disabled = isHidden;
    });

    if (stand.selectedOptions[0]?.disabled) {
      stand.value = "";
    }
  };

  const refreshRows = () => {
    rows.querySelectorAll(".additional-stand-row").forEach((row, index) => {
      const removeButton = row.querySelector(".remove-stand-row");
      removeButton.hidden = index === 0;
      row.querySelectorAll("select").forEach((select) => {
        select.required = true;
      });
      filterStandOptions(row);
    });
  };

  const addStandRow = () => {
    const row = template.content.firstElementChild.cloneNode(true);
    const pavilion = row.querySelector('[name="pabellon_adicional[]"]');

    pavilion.addEventListener("change", () => {
      row.querySelector('[name="numero_adicional[]"]').value = "";
      filterStandOptions(row);
    });

    row.querySelector(".remove-stand-row").addEventListener("click", () => {
      row.remove();
      refreshRows();
    });

    rows.append(row);
    refreshRows();
  };

  const clearStandRows = () => {
    rows.innerHTML = "";
  };

  const setAdditionalFlow = (enabled) => {
    block.hidden = !enabled;
    addButton.hidden = !enabled;

    if (enabled && rows.children.length === 0) {
      addStandRow();
      return;
    }

    if (!enabled) {
      clearStandRows();
    }
  };

  radios.forEach((radio) => {
    radio.addEventListener("change", () => {
      setAdditionalFlow(radio.value === "si" && radio.checked);
    });
  });

  addButton?.addEventListener("click", addStandRow);
  setAdditionalFlow(false);
});
