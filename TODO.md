# Plan de nettoyage clients.css

## Problèmes identifiés :
1. **Doublons massifs** : Les mêmes règles CSS sont répétées plusieurs fois
2. **Valeurs incohérentes** : Mêmes classes avec des valeurs différentes 
3. **Commentaires redondants** : Multiples blocs de commentaires identiques
4. **Approches mixtes** : Variables CSS vs valeurs codées en dur
5. **Styles de toast multiples** : 3+ implémentations différentes
6. **Avatars/images** : 5+ définitions pour les mêmes éléments

## Sections à consolider :
1. **Avatar/Image** : client-avatar, client-thumb, client-initials, avatar-box
2. **Tableau** : .table, .table td, styles de ligne
3. **Toast/Notifications** : clients-toast, pc-toast
4. **Boutons** : btn-light-custom, .btn-action-group
5. **Responsive** : @media queries multiples

## Améliorations à apporter :
- Structure logique et organisée
- Naming consistent (kebab-case)
- Variables CSS pour la cohérence
- Optimisation des performances
- Code maintenable
- Suppression des redondances

## Étapes :
1. ✅ Analyse du fichier existant
2. 🔄 Planification du nettoyage
3. ⏳ Implémentation de la version nettoyée
4. ⏳ Tests et validation
