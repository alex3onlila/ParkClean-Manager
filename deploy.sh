#!/bin/bash

# Configuration
REPO_URL="https://github.com/alex3onlila/ParkClean-Manager.git"

echo "🚀 Préparation de l'envoi vers GitHub..."

# Initialiser git si ce n'est pas fait
if [ ! -d ".git" ]; then
    git init
    git remote add origin $REPO_URL
fi

# Ajouter les fichiers
git add .

# Demander le message de commit
echo "📝 Entrez votre message de commit :"
read commit_message
git commit -m "$commit_message"

# Pousser vers la branche principale
git branch -M main
git push -u origin main

echo "✅ Projet mis à jour sur GitHub !"