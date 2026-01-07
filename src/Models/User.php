<?php
/**
 * ParkClean Manager - User Model
 * Modèle pour la gestion des utilisateurs (admin/employés)
 * 
 * @version 1.0.0
 * @author ParkClean Team
 */

declare(strict_types=1);

namespace ParkClean\Models;

use ParkClean\Core\BaseModel;
use ParkClean\Core\Database;
use PDO;
use InvalidArgumentException;
use RuntimeException;

class User extends BaseModel
{
    protected string $table = 'users';
    protected array $fillable = [
        'username', 'password', 'email', 'role'
    ];
    protected array $hidden = ['password'];
    protected array $casts = [
        'id' => 'integer'
    ];

    /**
     * Rôles disponibles
     */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';
    public const ROLE_VIEWER = 'viewer';

    /**
     * Hachage d'un mot de passe
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Vérifie un mot de passe
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Authentifie un utilisateur
     */
    public static function authenticate(string $username, string $password): ?array
    {
        $user = self::findBy('username', $username);
        
        if (!$user) {
            return null;
        }

        if (!self::verifyPassword($password, $user['password'])) {
            return null;
        }

        // Ne pas retourner le mot de passe
        unset($user['password']);
        
        return $user;
    }

    /**
     * Récupère un utilisateur par email
     */
    public static function findByEmail(string $email): ?array
    {
        return self::findBy('email', $email);
    }

    /**
     * Récupère un utilisateur par username
     */
    public static function findByUsername(string $username): ?array
    {
        return self::findBy('username', $username);
    }

    /**
     * Crée un nouvel utilisateur
     */
    public static function create(array $data): array
    {
        // Validation
        if (empty($data['username'])) {
            throw new InvalidArgumentException('Le nom d\'utilisateur est requis');
        }
        if (empty($data['password'])) {
            throw new InvalidArgumentException('Le mot de passe est requis');
        }

        // Vérifier l'unicité du username
        if (self::existsBy('username', $data['username'])) {
            throw new InvalidArgumentException('Ce nom d\'utilisateur existe déjà');
        }

        // Vérifier l'unicité de l'email si fourni
        if (isset($data['email']) && !empty($data['email'])) {
            if (self::existsBy('email', $data['email'])) {
                throw new InvalidArgumentException('Cet email est déjà utilisé');
            }
        }

        // Valider le rôle
        $validRoles = [self::ROLE_ADMIN, self::ROLE_USER, self::ROLE_VIEWER];
        if (!isset($data['role']) || !in_array($data['role'], $validRoles)) {
            $data['role'] = self::ROLE_USER;
        }

        // Hacher le mot de passe
        $data['password'] = self::hashPassword($data['password']);

        return parent::create($data);
    }

    /**
     * Met à jour un utilisateur
     */
    public static function update(int $id, array $data): bool
    {
        // Vérifier l'unicité du username si modifié
        if (isset($data['username'])) {
            if (self::existsBy('username', $data['username'], $id)) {
                throw new InvalidArgumentException('Ce nom d\'utilisateur existe déjà');
            }
        }

        // Vérifier l'unicité de l'email si modifié
        if (isset($data['email']) && !empty($data['email'])) {
            if (self::existsBy('email', $data['email'], $id)) {
                throw new InvalidArgumentException('Cet email est déjà utilisé');
            }
        }

        // Hacher le nouveau mot de passe si fourni
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = self::hashPassword($data['password']);
        } else {
            // Ne pas modifier le mot de passe s'il n'est pas fourni
            unset($data['password']);
        }

        // Valider le rôle si fourni
        if (isset($data['role'])) {
            $validRoles = [self::ROLE_ADMIN, self::ROLE_USER, self::ROLE_VIEWER];
            if (!in_array($data['role'], $validRoles)) {
                throw new InvalidArgumentMessage('Rôle invalide');
            }
        }

        return parent::update($id, $data);
    }

    /**
     * Met à jour le mot de passe
     */
    public static function updatePassword(int $id, string $newPassword): bool
    {
        return parent::update($id, [
            'password' => self::hashPassword($newPassword)
        ]);
    }

    /**
     * Supprime un utilisateur
     */
    public static function delete(int $id): bool
    {
        // Empêcher la suppression du dernier admin
        $user = self::find($id);
        if ($user && $user['role'] === self::ROLE_ADMIN) {
            $adminCount = self::count("role = 'admin'");
            if ($adminCount <= 1) {
                throw new RuntimeException('Impossible de supprimer le dernier administrateur');
            }
        }

        return parent::delete($id);
    }

    /**
     * Récupère tous les utilisateurs d'un certain rôle
     */
    public static function findByRole(string $role): array
    {
        return self::findAllBy('role', $role);
    }

    /**
     * Vérifie si un utilisateur a un rôle spécifique
     */
    public static function hasRole(int $userId, string $role): bool
    {
        $user = self::find($userId);
        return $user && $user['role'] === $role;
    }

    /**
     * Vérifie si l'utilisateur est admin
     */
    public static function isAdmin(int $userId): bool
    {
        return self::hasRole($userId, self::ROLE_ADMIN);
    }

    /**
     * Statistiques des utilisateurs
     */
    public static function getStats(): array
    {
        $pdo = Database::getConnection();
        
        $total = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $admins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        $users = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
        $viewers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'viewer'")->fetchColumn();

        return [
            'total' => $total,
            'admins' => $admins,
            'users' => $users,
            'viewers' => $viewers
        ];
    }
}

