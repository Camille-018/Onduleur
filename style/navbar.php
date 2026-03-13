<?php
require_once __DIR__ . '/../auth/authCheck.php';


// Récupérer les UPS pour le menu
$upsListForMenu = $pdo->query("SELECT id, device_model FROM ups ORDER BY device_model")->fetchAll();
?>

<div class="top-bar">
  <div class="nav-left">
    <a href="https://www.cereep.ens.psl.eu/" target="_blank" title="Site du CEREEP">
    <img src="/style/images/cereep.jpg" class="logo" alt="CEREEP">
</a>

    <div class="dropdown">
      <span class="dropbtn">Dashboard ▾</span>
      <div class="dropdown-content">
        <a href="/index.php">Accueil</a>
        <?php foreach ($upsListForMenu as $ups): ?>
          <a href="/ups.php?id=<?= $ups['id'] ?>"><?= htmlspecialchars($ups['device_model']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="dropdown">
      <span class="dropbtn">Alertes ▾</span>
      <div class="dropdown-content">
        <a href="/alerte/alerte.php">Alertes</a>
        <a href="/alerte/changerSeuils.php">Changer Seuils</a>
      </div>
    </div>

    <a href="/historique/historique.php">Historique</a>
  </div>

  <div class="nav-right">
    <span class="user"><?= htmlspecialchars($_SESSION['user']) ?></span>
    <a class="logout" href="/auth/logout.php">Déconnexion</a>
  </div>
</div>

<script>
// Auto logout script – ça reste dans navbar.php ou index.php, pas dans authCheck
const logoutTime = 10 * 60 * 1000;
let logoutTimer;

function autoLogout() {
    window.location.href = "/auth/logout.php";
}

function resetTimer() {
    clearTimeout(logoutTimer);
    logoutTimer = setTimeout(autoLogout, logoutTime);
}

['click','mousemove','keydown','scroll','touchstart'].forEach(event => {
    document.addEventListener(event, resetTimer, false);
});

resetTimer();
</script>