<script>
document.addEventListener("DOMContentLoaded", function () {

  const burger = document.querySelector(".burger-eduform");
  const menu   = document.querySelector(".menu-mobile-eduform");

  console.log("BURGER =", burger);
  console.log("MENU =", menu);

  if (!burger || !menu) {
    console.error("MENU MOBILE : éléments non trouvés");
    return;
  }

  burger.addEventListener("click", function () {
    const isOpen = menu.classList.toggle("open");

    burger.setAttribute("aria-expanded", isOpen);
    menu.setAttribute("aria-hidden", !isOpen);

    document.body.style.overflow = isOpen ? "hidden" : "";
  });

  // Ferme au clic sur un lien
  menu.querySelectorAll("a").forEach(link => {
    link.addEventListener("click", () => {
      menu.classList.remove("open");
      burger.setAttribute("aria-expanded","false");
      menu.setAttribute("aria-hidden","true");
      document.body.style.overflow = "";
    });
  });

});
</script>
