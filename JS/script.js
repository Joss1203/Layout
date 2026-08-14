let informacionStands = {};
fetch("../IMG/pabellon-c.svg")
  .then((res) => res.text())
  .then((svg) => {
    const modal = document.getElementById("modalStand");
    const cerrar = document.querySelector(".cerrar");
    const titulo = document.getElementById("tituloStand");
    const categoria = document.getElementById("categoriaStand");
    const estado = document.getElementById("estadoStand");
    const empresa = document.getElementById("empresaStand");
    const logoEmpresa = document.getElementById("logoEmpresa");
    const btnReservar = document.getElementById("btnReservar");

    document.getElementById("mapa").innerHTML = svg;

    for (let i = 1; i <= 114; i++) {
      if (i == 58) i = 101;

      let stands_C =
        document.getElementById(`c-${i}`) || document.getElementById(`C-${i}`);
      stands_C.id = `c${i}`;
      const regexNuevo = /c[0-9]+/;

      if (!stands_C) continue;

      stands_C.addEventListener("click", () => {
        let datos = {};

        // amarillos
        if (i <= 3 || (i >= 101 && i <= 104) || i == 33 || i == 32) {
          datos = {
            numero: stands_C.id,
            categoria: "premium",
            estado: "disponible",
            empresa: "",
            logo: "",
          };
        } else if (
          i == 4 ||
          i == 5 ||
          (i >= 105 && i <= 114) ||
          (i >= 29 && i <= 31)
        ) {
          // azules
          datos = {
            numero: stands_C.id,
            categoria: "standar",
            estado: "disponible",
            empresa: "",
            logo: "",
          };
        } else if (i >= 6 && i <= 28) {
          // verde
          datos = {
            numero: stands_C.id,
            categoria: "económico",
            estado: "disponible",
            empresa: "",
            logo: "",
          };
        } else {
          // morado
          datos = {
            numero: stands_C.id,
            categoria: "PyMES",
            estado: "disponible",
            empresa: "",
            logo: "",
          };
        }

        titulo.textContent = datos.numero.toString().toUpperCase();
        categoria.textContent = "Categoria: " + datos.categoria;
        estado.textContent = "Estado: " + datos.estado;
        empresa.textContent = datos.empresa ? "Empresa: " + datos.empresa : "";

        if (logoEmpresa) {
          if (datos.logo) {
            logoEmpresa.src = datos.logo;
            logoEmpresa.style.display = "block";
          } else {
            logoEmpresa.removeAttribute("src");
            logoEmpresa.style.display = "none";
          }
        }

        if (btnReservar) {
          btnReservar.style.display =
            datos.estado === "Ocupado" ? "none" : "block";
        }

        if (modal) {
          modal.style.display = "flex";
        }
      });
    }
    if (cerrar && modal) {
      cerrar.addEventListener("click", () => {
        modal.style.display = "none";
      });
    }

    if (modal) {
      modal.addEventListener("click", (e) => {
        if (e.target === modal) {
          modal.style.display = "none";
        }
      });
    }

    if (btnReservar) {
      btnReservar.addEventListener("click", () => {
        alert("Aqui se abrira el formulario de reserva.");
      });
    }
  });
