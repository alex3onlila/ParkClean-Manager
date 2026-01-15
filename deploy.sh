#!/bin/bash

# Configuration
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
