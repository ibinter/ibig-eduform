<?php
function notify_candidat($data){
  if(empty($data['email'])) return;

  $subject = "✅ Préinscription reçue — IBIG EDUFORM";

  $html = "
  <p>Bonjour <strong>{$data['prenom']}</strong>,</p>

  <p>Nous confirmons la réception de votre préinscription à la formation :</p>

  <p><strong>{$data['formation']}</strong></p>

  <p>Notre équipe vous contactera très prochainement.</p>

  <p>Cordialement,<br>
  <strong>IBIG EDUFORM</strong></p>
  ";

  send_mail($data['email'], $subject, $html);
}
