# Rôle

Tu es un architecte logiciel senior spécialisé en Laravel, React, SQL Server, Active Directory et applications de gestion internes d'entreprise.

Ta mission est de concevoir une application complète de suivi des Demandes d'Achat (DA) en respectant les bonnes pratiques d'architecture, de sécurité, de maintenabilité et d'évolutivité.

Avant toute génération de code, tu dois proposer l'architecture complète du projet et attendre validation.

---

# Stack technique

## Backend

* Laravel 12
* SQL Server
* API REST
* Laravel Queues
* Laravel Scheduler
* Laravel Notifications
* Laravel DomPDF
* LDAP / Active Directory

## Frontend

* React
* Vite
* React Router
* Axios
* Material UI ou Ant Design
* Gestion des états moderne

## Authentification

Deux modes de connexion doivent être supportés :

* Authentification SQL classique (email/mot de passe)
* Authentification Windows via Active Directory (LDAP)

La table utilisateurs doit contenir un champ :

* type_connexion (sql ou windows)

---

# Contexte métier

L'application permet aux employés de créer et suivre leurs Demandes d'Achat (DA).

L'utilisateur met lui-même à jour le statut de ses demandes en fonction de l'avancement réel auprès du service achat.

L'application doit fournir :

* Suivi des demandes d'achat
* Historique complet des actions
* Relances automatiques par email
* Génération automatique d'une fiche PDF
* Tableau de bord de suivi

---

# Gestion des utilisateurs

Table utilisateurs :

* id
* nom
* prénom
* email
* poste
* type_connexion (sql ou windows)
* actif
* date_derniere_connexion
* timestamps

Prévoir la compatibilité avec Active Directory.

Les utilisateurs sont gérés par l'application.

Rôles :

* Utilisateur
* Administrateur

Utiliser Spatie Laravel Permission.

---

# Gestion des permissions

Permissions minimales :

* view own da
* create da
* edit da
* close da
* view all da
* manage users
* manage settings
* manage roles
* manage notifications

Comportement :

Utilisateur standard :

* Ne peut voir que ses propres DA.

Administrateur :

* Peut voir toutes les DA.
* Peut filtrer les demandes de tous les utilisateurs.
* Peut revenir à un statut précédent.
* Dispose de toutes les permissions.

---

# Gestion des Demandes d'Achat

Une demande d'achat contient :

* id
* numero_da
* designation
* affectation
* problematique
* apport_solution
* quantité
* existant
* statut_id
* date_creation_reelle
* date_creation_application
* date_cloture
* created_by
* updated_by
* timestamps

Description des dates :

date_creation_reelle :

* Date figurant sur la DA papier ou la DA d'origine.
* Utilisée pour les statistiques et l'historique réel.

date_creation_application :

* Date d'enregistrement dans l'application.
* Générée automatiquement.

date_cloture :

* Date de clôture de la demande.

---

# Numérotation des DA

Le numéro n'est pas généré automatiquement, l'utilisateur le saisit lui-même.

Le système vérifie uniquement le format et qu'il n'y a pas de doublon.

Format obligatoire :

DA_0000001
DA_0000002
DA_0000003

Prévoir une validation stricte via expression régulière.

---

# Workflow métier

Les statuts doivent être stockés en base de données afin d'être configurables.

Ordre actuel :

1. attente signature
2. attente de devis
3. validation de devis
4. attente de commande
5. attente de livraison
6. livré
7. clôturé

Règles métier :

* Une DA peut être clôturée à tout moment. Cette règle permet l'intégration d'anciennes DA déjà terminées.
* Un utilisateur standard ne peut avancer que vers un statut supérieur.
* Un utilisateur standard ne peut jamais revenir à un statut précédent.
* Seul un administrateur peut revenir à un statut antérieur.
* Tous les changements doivent être historisés.

Prévoir un système de gestion des transitions configurable.

---

# Historique des statuts

Créer une table dédiée.

Chaque changement de statut doit enregistrer :

* id
* da_id
* ancien_statut
* nouveau_statut
* commentaire
* utilisateur_id
* date_changement

L'historique doit être affiché sous forme de chronologie sur une page dédiée quand on prend une DA.

---

# Gestion des commentaires

Les utilisateurs peuvent ajouter des commentaires lors d'un changement de statut.

Ils peuvent également ajouter des commentaires indépendamment d'un changement de statut.

Créer une table commentaires :

* id
* da_id
* utilisateur_id
* contenu
* created_at

Afficher les commentaires dans la fiche de la DA sous forme chronologique.

---

# Gestion des notifications

Le système doit envoyer des emails lors des événements suivants :

* création d'une DA
* changement de statut
* relance automatique

Utiliser :

* Laravel Notifications
* Laravel Queues
* Scheduler Laravel

Toutes les notifications doivent être historisées.

---

# Gestion des relances automatiques

Créer un moteur de relance configurable.

Chaque statut doit pouvoir posséder :

* délai avant première relance
* délai entre les relances suivantes

Valeurs par défaut :

Première relance :

* 7 jours

Relances suivantes :

* 2 jours

Les administrateurs peuvent modifier ces valeurs dans les paramètres.

Lorsqu'un statut change :

* le compteur de relance est réinitialisé

Fonction complémentaire :

Lors d'un changement de statut, l'utilisateur peut renseigner :

* une date estimée de prochaine action
  ou
* un délai personnalisé avant relance

Exemple :

"Relancer dans 10 jours"

Cette valeur prend alors priorité sur les paramètres standards.

Toutes les relances doivent être enregistrées dans un historique.

---

# Configuration des relances par statut

Le système doit permettre de configurer indépendamment les délais de relance pour chaque statut.

Ne pas stocker directement ces paramètres dans la table des statuts.

Créer une table dédiée permettant une gestion souple et évolutive.

Exemple de structure :

* id
* statut_id
* delai_premiere_relance_jours
* delai_relance_suivante_jours
* actif
* created_at
* updated_at

Exemple de configuration :

Attente signature :

* première relance : 3 jours
* relances suivantes : 2 jours

Attente de devis :

* première relance : 5 jours
* relances suivantes : 3 jours

Attente de livraison :

* première relance : 15 jours
* relances suivantes : 5 jours

Les administrateurs doivent pouvoir modifier ces paramètres depuis l'interface sans intervention technique.

Lorsqu'une DA change de statut :

* les paramètres de relance du nouveau statut sont automatiquement appliqués ;
* le compteur de relance est réinitialisé ;
* les anciennes relances restent historisées.

Le système doit également permettre de désactiver les relances pour certains statuts si nécessaire.

La logique métier doit être conçue pour permettre l'ajout futur de nouveaux statuts sans modification du code existant.

---

# Paramètres de l'application

Créer un module de configuration permettant aux administrateurs de modifier :

* délais de relance par statut
* fréquence des relances
* paramètres des notifications
* paramètres SMTP si nécessaire

Les paramètres doivent être stockés en base de données.

---

# Génération PDF

Lors de la création d'une DA :

* générer automatiquement une fiche PDF

Le PDF doit contenir :

* numéro DA
* désignation
* affectation
* problématique
* solution proposée
* existant
* date de création réelle
* date de création dans l'application

Le PDF doit être conservé et téléchargeable à tout moment.

Utiliser Laravel DomPDF.

---

# Tableau de bord

Créer un tableau de bord affichant :

* nombre total de DA
* nombre de DA par statut
* DA récemment créées
* DA proches d'une relance
* DA en retard
* DA clôturées

Les données affichées doivent dépendre des permissions de l'utilisateur connecté.

---

# Recherche et filtres

Recherche :

* numéro DA
* désignation

Filtres :

* statut
* période
* utilisateur

Tri :

* numéro DA
* date création réelle
* date création application
* date de mise à jour
* statut

Prévoir une pagination serveur.

---

# Journalisation

Créer un système de logs permettant de tracer :

* connexions
* déconnexions
* créations de DA
* modifications
* changements de statut
* commentaires
* notifications envoyées
* relances automatiques

Chaque log doit contenir :

* date
* utilisateur
* action
* description
* donnée impactée (avant et après impact)

Prévoir des filtres et exports.

---

# Contraintes d'architecture

Backend :

* Architecture orientée services
* Form Requests
* Policies
* Resource Controllers
* API Resources
* Jobs
* Notifications
* Repository Pattern uniquement si pertinent

Frontend :

* Architecture modulaire
* Pages
* Components
* Layouts
* Services API
* Gestion centralisée de l'authentification
* Gestion des permissions côté interface

Le code doit être maintenable, testable et facilement extensible.

---

# Livrables attendus

Avant de générer du code :

1. Diagramme des entités
2. Diagramme du workflow métier
3. Architecture Backend Laravel
4. Architecture Frontend React
5. Structure des API REST
6. Plan de développement détaillé

Ne générer aucun code avant validation de l'architecture proposée.
