<?php

namespace App\Middleware;

use App\Services\TokenService;
use App\Services\CacheService;
use App\Models\UserModel;
use App\Core\RequestObject;
use App\Exceptions\AuthenticationException;
use Psr\Log\LoggerInterface;

/**
 * AuthMiddleware - Authentication and authorization middleware
 * 
 * This middleware handles user authentication and authorization.
 * It validates JWT tokens, retrieves user permissions, and builds
 * the RequestObject for controllers.
 */
class AuthMiddleware
{
    private TokenService $tokenService;
    private CacheService $cacheService;
    private UserModel $userModel;
    private ?LoggerInterface $logger = null;
    
    /**
     * Create a new AuthMiddleware instance
     * 
     * @param TokenService $tokenService Service for JWT token handling
     * @param CacheService $cacheService Service for caching
     * @param UserModel|null $userModel User model for fetching user data (optional)
     */
    public function __construct(
        TokenService $tokenService, 
        CacheService $cacheService,
        ?UserModel $userModel = null
    ) {
        $this->tokenService = $tokenService;
        $this->cacheService = $cacheService;
        $this->userModel = $userModel ?? new UserModel();
    }
    
    /**
     * Set a logger for the middleware
     * 
     * @param LoggerInterface $logger PSR-3 compatible logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if user is logged in
     */
    public function isUserLoggedIn(): bool
    {
        // Check for token in cookies
        $tokenName = $this->tokenService->getTokenName();
        if (!isset($_COOKIE[$tokenName])) {
            // Also check for token in Authorization header (for API requests)
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return false;
            }
            $token = $matches[1];
        } else {
            $token = $_COOKIE[$tokenName];
        }
        
        try {
            return $this->tokenService->validateAndRefreshToken($token);
        } catch (AuthenticationException $e) {
            // Log the exception
            if ($this->logger) {
                $this->logger->info("Authentication failed: " . $e->getMessage());
            } else {
                error_log("Authentication failed: " . $e->getMessage());
            }
            
            // Try to use refresh token if available
            $refreshTokenName = $this->tokenService->getTokenName() . '_refresh';
            if (isset($_COOKIE[$refreshTokenName])) {
                try {
                    $refreshed = $this->tokenService->refreshFromToken($_COOKIE[$refreshTokenName]);
                    return $refreshed;
                } catch (AuthenticationException $e) {
                    // Refresh token is also invalid
                    if ($this->logger) {
                        $this->logger->info("Refresh token also invalid: " . $e->getMessage());
                    } else {
                        error_log("Refresh token also invalid: " . $e->getMessage());
                    }
                }
            }
            
            return false;
        }
    }
    
    /**
     * Retrieve user information from token and permissions from cache
     * 
     * @param int $userId User ID
     * @param int $userRole User role ID
     * @return array User information with permissions
     */
    public function retrieveUserInfo(int $userId, int $userRole): array
    {
        // Generate a cache key for this user's permissions
        $cacheKey = "user_permissions_{$userId}_{$userRole}";
        
        // Try to get permissions from cache first
        $cachedPermissions = $this->cacheService->get($cacheKey);
        
        if ($cachedPermissions !== null) {
            return $cachedPermissions;
        }
        
        // Cache miss, get from database
        // Get role permissions from cache
        $permissions = $this->cacheService->getRolePermission($userRole);
        $permissionInt = $this->calculatePermissionInteger($permissions);
        
        // Get additional user data
        $userData = $this->userModel->getUserById($userId);
        
        if (!$userData) {
            $userInfo = [
                'userId' => $userId,
                'userName' => 'Unknown',
                'userFirstName' => 'Unknown',
                'permissionInteger' => 0,
                'userRole' => $userRole,
                'profilePictureUrl' => '/assets/img/default-avatar.png',
                'userSearchType' => null,
            ];
        } else {
            $userInfo = [
                'userId' => $userId,
                'userName' => $userData->userName ?? $userData->last_name ?? 'Unknown',
                'userFirstName' => $userData->userFirstName ?? $userData->first_name ?? 'Unknown',
                'permissionInteger' => $permissionInt,
                'userRole' => $userRole,
                'profilePictureUrl' => $userData->profilePictureUrl ?? '/assets/img/default-avatar.png',
                'userSearchType' => $userData->userSearchType ?? null,
                'userEmail' => $userData->userEmail ?? $userData->email ?? null,
            ];
        }
        
        // Cache the user info for future requests (10 minute TTL)
        $this->cacheService->set($cacheKey, $userInfo, 600);
        
        return $userInfo;
    }
    
    /**
     * Calculate permission integer from individual permissions
     * 
     * @param array $permissions Array of permission flags
     * @return int Bit-encoded permission integer
     */
    private function calculatePermissionInteger(array $permissions): int
    {
        $permissionInt = 0;
        $power = 0;
        
        // Skip first two attributes (id and name)
        foreach (array_slice($permissions, 2) as $permission) {
            if ($permission == 1) {
                $permissionInt += 2 ** $power;
            }
            $power++;
        }
        
        return $permissionInt;
    }
    
    /**
     * Process the request through authentication
     * 
     * @return RequestObject|null The authenticated request object or null
     */
    public function handle(): ?RequestObject
    {
        $tokenName = $this->tokenService->getTokenName();
        $token = null;
        
        // Check for token in cookies
        if (isset($_COOKIE[$tokenName])) {
            $token = $_COOKIE[$tokenName];
        } else {
            // Also check for token in Authorization header (for API requests)
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        
        if (!$token) {
            return null;
        }
        
        try {
            if (!$this->tokenService->validateAndRefreshToken($token)) {
                // Try to use refresh token if available
                $refreshTokenName = $tokenName . '_refresh';
                if (isset($_COOKIE[$refreshTokenName])) {
                    try {
                        if (!$this->tokenService->refreshFromToken($_COOKIE[$refreshTokenName])) {
                            return null;
                        }
                        // Get the new token after refresh
                        $token = $_COOKIE[$tokenName];
                    } catch (AuthenticationException $e) {
                        // Refresh token is also invalid
                        return null;
                    }
                } else {
                    return null;
                }
            }
            
            $decodedToken = $this->tokenService->decodeJWT($token);
            $userId = $decodedToken->user_id;
            $userRole = $decodedToken->acctype;
            
            $userInfo = $this->retrieveUserInfo($userId, $userRole);
            
            return new RequestObject($userInfo);
        } catch (\Exception $e) {
            // Log the exception
            if ($this->logger) {
                $this->logger->error("Authentication middleware error: " . $e->getMessage(), [
                    'exception' => $e
                ]);
            } else {
                error_log("Authentication middleware error: " . $e->getMessage());
            }
            return null;
        }
    }
    
         /**
     * Enforce authentication for protected routes
     * 
     * @param RequestObject $request Current request
     * @param callable $next Next middleware or controller
     * @param string $redirectUrl URL to redirect to if not authenticated
     * @return mixed The response from the next middleware or controller
     */
    public function enforceAuth(RequestObject $request, callable $next, string $redirectUrl = '/login'): mixed
    {
        if (!$request->isAuthenticated()) {
            // Store current URL for redirection after login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            
            // Redirect to login page
            header("Location: $redirectUrl");
            exit;
        }
        
        return $next($request);
    }
    
    /**
     * Enforce specific permissions for protected routes
     * 
     * @param RequestObject $request Current request
     * @param callable $next Next middleware or controller
     * @param int $permission Required permission bit
     * @param string $redirectUrl URL to redirect to if not authorized
     * @return mixed The response from the next middleware or controller
     */
    public function enforcePermission(RequestObject $request, callable $next, int $permission, string $redirectUrl = '/403'): mixed
    {
        if (!$request->isAuthenticated()) {
            // Store current URL for redirection after login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            
            // Redirect to login page
            header("Location: /login");
            exit;
        }
        
        if (!$request->hasPermission($permission)) {
            // User is authenticated but doesn't have the required permission
            header("Location: $redirectUrl");
            exit;
        }
        
        return $next($request);
    }
}