<?php

function emailFormateur(string $type, array $data): array {

  switch ($type) {

    case 'retenue':
      return [
        'subject' => "Candidature retenue – IBIG EDUFORM",
        'message' => "
Bonjour {$data['nom']},

Nous avons le plaisir de vous informer que votre candidature
comme consultant formateur a été RETENUE.

Notre équipe vous contactera très prochainement.

Cordialement,
IBIG EDUFORM
"
      ];

    case 'rejete':
      return [
        'subject' => "Suite à votre candidature – IBIG EDUFORM",
        'message' => "
Bonjour {$data['nom']},

Nous vous remercions pour votre candidature.
Elle n’a pas été retenue à ce stade.

Nous conservons toutefois votre profil.

Cordialement,
IBIG EDUFORM
"
      ];

    default:
      return [
        'subject' => "Mise à jour de votre candidature – IBIG EDUFORM",
        'message' => "
Bonjour {$data['nom']},

Le statut de votre candidature a été mis à jour : {$data['statut']}.

Cordialement,
IBIG EDUFORM
"
      ];
  }
}
