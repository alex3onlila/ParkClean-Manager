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

# Vérifier s'il y a des changements
if [ -z "$(git status --porcelain)" ]; then
    echo "⚠️  Aucun changement à commiter. Working tree clean."
    echo "✅ Projet déjà à jour sur GitHub !"
    exit 0
fi

# Ajouter tous les fichiers
git add .

# Générer message de commit automatique avec date et heure
commit_message="Update: $(date '+%Y-%m-%d %H:%M')"
echo "📝 Message de commit : $commit_message"

# Commit
git commit -m "$commit_message"

# Synchroniser avec le remote (pull --rebase pour éviter les conflits)
echo "🔄 Synchronisation avec le remote..."
git pull --rebase origin main

# Pousser vers la branche principale
git branch -M main
git push -u origin main

echo "✅ Projet mis à jour sur GitHub !"
