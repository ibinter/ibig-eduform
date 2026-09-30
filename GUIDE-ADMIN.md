# Guide d'utilisation — Administration IBIG EDUFORM

---

## 💳 Activer le paiement en ligne (Moneroo)

Le site dispose de sa **propre page de paiement** (`paiement-inscription.php`) qui
utilise **Moneroo** (Orange/MTN/Moov Money, Wave, cartes). Pour l'activer :

1. **Créez un compte** sur https://moneroo.io et activez votre compte marchand (XOF).
2. Dans le tableau de bord Moneroo → **Developers / API Keys** : copiez votre
   **Secret Key**.
3. **Webhooks** : créez un webhook pointant vers
   `https://ibig-eduform.com/webhook-moneroo.php` et copiez le **Webhook secret**.
4. Collez les deux dans **`core/secrets.php`** :
   ```php
   define('MONEROO_SECRET_KEY',     getenv('MONEROO_SECRET_KEY')     ?: 'VOTRE_SECRET_KEY');
   define('MONEROO_WEBHOOK_SECRET', getenv('MONEROO_WEBHOOK_SECRET') ?: 'VOTRE_WEBHOOK_SECRET');
   ```
5. Exécutez la migration **`migrations/2026_paiements_moneroo.sql`** dans phpMyAdmin
   (ajoute les colonnes de suivi à `paiements_inscription`).
6. Re-téléversez les fichiers + videz le cache.

**Fonctionnement :** le client saisit ses infos sur votre page → il est redirigé
vers le paiement sécurisé Moneroo → il revient sur `paiement-retour.php` → le
**webhook** confirme automatiquement et marque le paiement « payé » en base.

> Tant que les clés sont vides, la page bascule sur l'ancien lien (`paiement_lien`)
> si présent, sinon affiche un message. Aucune erreur.
> 💡 Astuce frais : Moneroo prélève une commission. Pour recevoir le **net** voulu,
> facturez `montant ÷ (1 − taux)` (ex. à 2 % : 50 000 ÷ 0,98 ≈ 51 020).

---

Ce guide explique comment gérer le contenu du site depuis l'espace d'administration.
Aucune compétence technique n'est nécessaire pour l'usage courant.

---

## ⚡ Activer la réception WhatsApp INSTANTANÉE des préinscriptions (CallMeBot)

À chaque préinscription, la demande est : (1) enregistrée en base, (2) envoyée
**automatiquement** sur le WhatsApp de l'institut, (3) le prospect peut aussi
l'envoyer en 1 clic. Les points (1) et (3) marchent déjà. Pour activer le (2)
(envoi automatique), faites cette inscription **gratuite et unique** :

1. Sur le téléphone qui porte le **numéro de réception** (+225 07 78 88 25 92),
   enregistrez ce contact WhatsApp : **+34 644 94 58 67** (CallMeBot).
2. Envoyez-lui ce message WhatsApp **exact** :
   `I allow callmebot to send me messages`
3. CallMeBot répond avec une **clé API** (ex. `123456`).
4. Ouvrez le fichier **`core/secrets.php`** et collez la clé :
   `define('WHATSAPP_CALLMEBOT_APIKEY', getenv('WHATSAPP_CALLMEBOT_APIKEY') ?: '123456');`
5. Re-téléversez `core/secrets.php` + videz le cache.

> Tant que la clé n'est pas renseignée, tout fonctionne **sauf** l'envoi
> automatique (la demande reste en base + le bouton wa.me reste actif).
> Pour changer le numéro de réception : `WHATSAPP_ADMIN_PHONE` dans `core/config.php`
> (format international **sans +**, ex. `2250778882592`).

---

## 1. Se connecter à l'administration

1. Ouvrez : **https://ibig-eduform.com/admin/auth/login.php**
2. Saisissez votre **email professionnel** et votre **mot de passe**.
3. Cliquez sur **Se connecter**.

> 🔒 Après 5 tentatives échouées, la connexion est bloquée quelques minutes (protection anti-piratage). Patientez, puis réessayez.

---

## 2. ⚙️ Étape technique à faire UNE SEULE FOIS (par le webmaster)

Avant de pouvoir gérer le carrousel et les pages, il faut créer 2 tables dans la base de données.

1. Connectez-vous à **phpMyAdmin** (depuis le panneau de votre hébergeur).
2. À gauche, cliquez sur la base **`ibigs2689720_7ja7u`**.
3. En haut, cliquez sur l'onglet **SQL**.
4. Ouvrez le fichier **`migrations/2026_content_admin.sql`** (fourni dans le site), copiez **tout** son contenu.
5. Collez-le dans la zone de saisie SQL, puis cliquez sur **Exécuter**.

✅ C'est fait. Cela crée :
- la table **`hero_slides`** (déjà remplie avec les 4 slides actuels) ;
- la table **`site_pages`** (12 pages prêtes à être éditées).

> Tant que cette étape n'est pas faite, le site fonctionne normalement avec son contenu d'origine — seuls les écrans « Carrousel » et « Pages » afficheront un message d'avertissement.

---

## 3. 🖼️ Gérer le carrousel de la page d'accueil

**Menu : Admin → Carrousel accueil**

### Modifier un slide existant
1. Cliquez sur **Éditer** en face du slide.
2. Modifiez les champs :
   - **Position** : ordre d'affichage (1 = premier).
   - **Image de fond** : choisissez une image existante OU téléversez-en une nouvelle (JPEG/PNG/WebP, max 5 Mo).
   - **Sur-titre / Titre / Texte d'accroche** : les textes principaux.
   - **Titre** : vous pouvez mettre en couleur un mot avec `<span>mot</span>`.
   - **Boutons 1 et 2** : libellé + lien (ex. `formations.php`).
   - **Panneau** : titre, texte et liste à puces (une puce par ligne).
   - **Slide actif** : décochez pour le masquer sans le supprimer.
3. Cliquez sur **Enregistrer**. Le changement est visible immédiatement sur l'accueil.

### Ajouter un slide
- Bouton **+ Nouveau slide**, remplissez le formulaire, **Enregistrer**.

### Masquer / Supprimer
- **Masquer** : cliquez sur le badge de statut (Actif ↔ Masqué).
- **Supprimer** : bouton **Suppr.** (une confirmation est demandée).

> 💡 Si vous supprimez **tous** les slides, la page d'accueil réaffiche automatiquement les slides d'origine.

---

## 4. 📄 Gérer les pages du site

**Menu : Admin → Pages du site**

Pages concernées : À propos, Pédagogie, Entreprises, Catalogue, Samedi Pro, FAQ, CGV,
Conditions, Confidentialité, Cookies, Mentions légales, Non-remboursement.

### Principe important : « Version d'origine » vs « Publiée »
- **Version d'origine** (par défaut) : le site affiche la page telle qu'elle existe aujourd'hui.
- **Publiée** : le site affiche **votre** contenu saisi dans l'admin.

### Modifier une page
1. Cliquez sur **Éditer**.
2. Renseignez :
   - **Titre** de la page,
   - **Méta-description** (résumé pour Google, ~160 caractères),
   - **Contenu** : en HTML (paragraphes `<p>…</p>`, titres `<h2>…</h2>`, listes `<ul><li>…</li></ul>`, liens `<a href="…">…</a>`).
3. Cochez **Publier cette version** pour l'activer sur le site.
4. Cliquez sur **Enregistrer**.

### Revenir à la version d'origine
- Sur la liste, cliquez sur le badge de statut pour **dé-publier** : le site réaffiche instantanément la page d'origine. Votre contenu n'est pas perdu, juste masqué.

### Prévisualiser
- Bouton **Voir** : ouvre la page publique dans un nouvel onglet.

---

## 5. Les autres contenus (déjà administrables)

Ces sections se gèrent depuis leurs menus respectifs :

| Contenu | Où le gérer |
|---|---|
| Formations + pages de formation | Admin → Formations |
| Calendrier / sessions | Admin → Calendrier |
| Préinscriptions reçues | Admin → Préinscriptions |
| Avis clients | Admin → Avis |
| Blog / articles | Admin → Blog |
| Offres d'emploi / opportunités | Admin → Opportunités / Emplois |
| Formateurs & candidatures | Admin → Formateurs |
| Utilisateurs de l'admin | Admin → Utilisateurs |

---

## 6. Bonnes pratiques

- **Déconnectez-vous** après usage sur un ordinateur partagé.
- Si un message **« 419 / Lien expiré »** apparaît : la page est restée ouverte trop longtemps. Rechargez et recommencez l'action.
- Conservez des **mots de passe forts** pour les comptes admin.
- Pour les images du carrousel, privilégiez un format **paysage** (~1536 × 1024 px) et un poids raisonnable.

---

## 7. Notes pour le webmaster

- **Sécurité** : identifiants de base dans `core/secrets.php` (à ne jamais publier). Protection CSRF active sur tous les formulaires/actions admin. Mode production (erreurs masquées).
- **Performance** : compression GZIP + cache navigateur (`.htaccess`), images optimisées, WebP avec repli.
- **À faire** : (1) changer le mot de passe MySQL exposé puis le reporter dans `core/secrets.php` ; (2) exécuter la migration SQL (§2).
- Le fichier `migrations/2026_content_admin.sql` peut être ré-exécuté sans risque (il ne recrée rien d'existant).
