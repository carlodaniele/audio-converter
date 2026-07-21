# Guide rapide - Version gratuite

Ce guide est autonome et ne necessite pas de site externe.

## Prerequis

- WordPress 7.0+
- PHP 8.0+
- Au moins un connecteur IA WordPress configure et actif
- Utilisateur autorise a modifier les articles

Important: sans connecteur IA actif, le plugin peut etre active mais ne peut pas generer de brouillons depuis l'audio.

## 1) Installation

1. Copiez le dossier du plugin dans wp-content/plugins.
2. Activez Nomad Pipeline Audio to Draft.
3. Ouvrez Reglages > Nomad Pipeline Audio to Draft.
4. Enregistrez les valeurs par defaut.

## 2) Configuration recommandee

- Langue par defaut: langue principale du site
- Ton par defaut: professional
- Longueur cible par defaut: medium
- Indications de noms propres: marques, personnes, lieux, produits
- Mode d'insertion: append (recommande)

## 3) Utilisation dans l'editeur de blocs

1. Ouvrez ou creez un article.
2. Ouvrez la barre laterale Nomad Pipeline Audio to Draft.
3. Cliquez sur Select audio from Media Library.
4. Selectionnez un fichier audio.
5. Verifiez les options editoriales.
6. Cliquez sur Generate draft from audio.

## 4) Resultat attendu

- Le plugin insere des blocs Gutenberg dans l'article en cours.
- Si active, le titre genere est applique au titre de l'article.
- En cas d'erreur, un message apparait dans la barre laterale.

## 5) Depannage rapide

- Barre laterale absente: verifiez plugin actif et editeur de blocs.
- Erreur fournisseur IA: verifiez la configuration des connecteurs IA.
- Aucun bloc insere: lisez le message d'erreur et essayez un audio plus clair.

## 6) Bonnes pratiques

- Utilisez un audio clair avec peu de bruit de fond.
- Ajoutez des indications de noms propres.
- Pour de meilleurs resultats, preferez des enregistrements courts et nets.
