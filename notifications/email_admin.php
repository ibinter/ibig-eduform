<?php
function notify_admin_preinscription($data){
  $subject = "📥 Nouvelle préinscription — IBIG EDUFORM";

  $html = "
  <h2>Nouvelle préinscription reçue</h2>
  <p><strong>Nom :</strong> {$data['prenom']} {$data['nom']}</p>
  <p><strong>Téléphone :</strong> {$data['telephone']}</p>
  <p><strong>Email :</strong> {$data['email']}</p>
  <p><strong>Formation :</strong> {$data['formation']}</p>
  <p><strong>Date :</strong> ".date('d/m/Y H:i')."</p>
  ";

  send_mail("formation@ibig-eduform.com", $subject, $html);
}
