# Plan : Mise à jour de deploy.sh pour commit automatique

## Objectif
Modifier deploy.sh pour effectuer un commit complet automatique avec un message de commit généré dynamiquement (date + heure).

## Changes à apporter dans deploy.sh

1. **Supprimer la demande interactive** de message de commit
2. **Générer automatiquement** un message de commit au format : `Update: [YYYY-MM-DD HH:MM]`
3. **Ajouter git status** pour voir les fichiers modifiés avant le commit
4. **Garder git add .** pour ajouter tous les changements
5. **Faire le commit** avec le message automatique
6. **Faire le push** vers la branche principale

## Fichier à modifier
- `deploy.sh`

## Nouveau contenu prévu
```bash
#!/bin/bash

REPO_URL="https://github.com/alex3onlila/ParkClean-Manager.git"

echo "🚀 Préparation de l'envoi vers GitHub..."

# Initialiser git si ce n'est pas fait
if [ ! -d ".git" ]; then
    git init
    git remote add origin $REPO_URL
fi

# Afficher les fichiers modifiés
echo "📁 Fichiers modifiés :"
git status

# Ajouter tous les fichiers
git add .

# Générer message de commit automatique avec date et heure
commit_message="Update: $(date '+%Y-%m-%d %H:%M')"
echo "📝 Message de commit : $commit_message"

# Commit
git commit -m "$commit_message"

# Pousser vers la branche principale
git branch -M main
git push -u origin main

echo "✅ Projet mis à jour sur GitHub !"
```

## Actions
- [x] Lire le fichier deploy.sh actuel
- [x] Modifier deploy.sh avec le nouveau contenu
- [ ] Tester le script

