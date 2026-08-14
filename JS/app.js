let informacionStands = {};

fetch("includes/api/stands.php")
  .then((response) => response.json())
  .then((data) => {
    data.forEach((stand) => {
      informacionStands[stand.clave] = {
        numero: stand.numero,
        categoria: stand.categoria,
        estado: stand.estado,
        empresa: stand.empresa,
        logo: stand.logo,
      };
    });
  })
  .catch((error) => {
    console.error("Error al cargar stands:", error);
  });

const zonas = document.querySelectorAll(".zona");

zonas.forEach((zona) => {
  zona.addEventListener("click", () => {
    if (zona.dataset.link) {
      window.location.href = zona.dataset.link;
    }
  });
});

const stands = document.querySelectorAll(".stand");
const modal = document.getElementById("modalStand");
const cerrar = document.querySelector(".cerrar");
const titulo = document.getElementById("tituloStand");
const categoria = document.getElementById("categoriaStand");
const estado = document.getElementById("estadoStand");
const empresa = document.getElementById("empresaStand");
const logoEmpresa = document.getElementById("logoEmpresa");
const btnReservar = document.getElementById("btnReservar");

stands.forEach((stand) => {
  stand.addEventListener("click", () => {
    const datos = informacionStands[stand.id] || {
      numero: stand.id,
      categoria: "Sin categoria",
      estado: "Sin datos",
      empresa: "",
      logo: "",
    };

    titulo.textContent = datos.numero;
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
      btnReservar.style.display = datos.estado === "Ocupado" ? "none" : "block";
    }

    if (modal) {
      modal.style.display = "flex";
    }
  });
});

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
