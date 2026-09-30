<?php
function whatsapp_link_candidat($data){

  $msg = "Bonjour {$data['prenom']},%0A
Nous confirmons la réception de votre préinscription à la formation :%0A
*{$data['formation']}*%0A%0A
Un membre de l’équipe IBIG EDUFORM vous contactera très bientôt.%0A%0A
Merci.";

  $phone = preg_replace('/\D/', '', $data['telephone']);

  return "https://wa.me/{$phone}?text={$msg}";
}

function whatsapp_link_admin($d){

  // ⚠️ NUMÉRO WHATSAPP ADMIN (FORMAT INTERNATIONAL)
  $adminPhone = "2250778882592"; // <-- À CONFIRMER / MODIFIER

  $msg = "📥 *Nouvelle préinscription IBIG EDUFORM*%0A%0A"
       . "*Formation :* {$d['formation']}%0A"
       . "*Nom :* {$d['prenom']} {$d['nom']}%0A"
       . "*Téléphone :* {$d['telephone']}%0A"
       . "*Email :* {$d['email']}%0A%0A"
       . "Connectez-vous à l’admin pour le suivi.";

  return "https://wa.me/{$adminPhone}?text={$msg}";
}
