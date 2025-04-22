<?php
/**
 * Système de routes simplifié pour tui_calendar
 */
class Routes {
    // Stockage des routes
    private static $routes = [];
    
    // Route par défaut
    private static $defaultRoute = ['controller' => 'Calendar', 'action' => 'index'];
    
    // Suffixe des contrôleurs
    private static $controllerSuffix = 'Controller';
    
    /**
     * Définir une route
     * 
     * @param string $path Chemin URL (ex: 'calendar/view')
     * @param array $route Route [controller, action]
     * @return void
     */
    public static function add($path, $route) {
        self::$routes[$path] = $route;
    }
    
    /**
     * Définir la route par défaut
     * 
     * @param string $controller Nom du contrôleur
     * @param string $action Nom de l'action
     * @return void
     */
    public static function setDefault($controller, $action) {
        self::$defaultRoute = [
            'controller' => $controller,
            'action' => $action
        ];
    }
    
    /**
     * Récupérer la route actuelle à partir de l'URL
     * 
     * @return array Route [controller, action, params]
     */
    public static function getRoute() {
        // Vérifier d'abord s'il y a des paramètres GET explicites
        if (isset($_GET['controller']) && isset($_GET['action'])) {
            $controller = ucfirst($_GET['controller']);
            $action = $_GET['action'];
            
            // Extraire les autres paramètres
            $params = $_GET;
            unset($params['controller']);
            unset($params['action']);
        } else {
            // Sinon, analyser l'URL réécrite
            $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
            
            // Enlever le préfixe "tui_calendar" de l'URL
            $path = str_replace('tui_calendar/', '', $path);
            $path = str_replace('tui_calendar', '', $path);
            
            // Diviser l'URL en segments
            $segments = explode('/', trim($path, '/'));
            
            // Déterminer le contrôleur et l'action
            $controller = !empty($segments[0]) ? ucfirst($segments[0]) : self::$defaultRoute['controller'];
            $action = isset($segments[1]) ? $segments[1] : self::$defaultRoute['action'];
            
            // Extraire les paramètres supplémentaires
            $params = [];
            for ($i = 2; $i < count($segments); $i += 2) {
                if (isset($segments[$i+1])) {
                    $params[$segments[$i]] = $segments[$i+1];
                }
            }
        }
        
        return [
            'controller' => $controller,
            'action' => $action,
            'params' => $params
        ];
    }
    
    /**
     * Dispatcher - Exécuter la route actuelle
     * 
     * @return void
     */
    public static function dispatch() {
        $route = self::getRoute();
        
        // Construire le nom complet de la classe contrôleur
        $controllerClass = $route['controller'] . self::$controllerSuffix;
        $controllerFile = __DIR__ . '/../controllers/' . $controllerClass . '.php';
        
        // Vérifier si le fichier du contrôleur existe
        if (!file_exists($controllerFile)) {
            self::notFound("Contrôleur non trouvé: $controllerClass");
            return;
        }
        
        // Inclure le fichier du contrôleur
        require_once $controllerFile;
        
        // Vérifier si la classe existe
        if (!class_exists($controllerClass)) {
            self::notFound("Classe du contrôleur non trouvée: $controllerClass");
            return;
        }
        
        // Créer une instance du contrôleur
        $controller = new $controllerClass();
        
        // Vérifier si la méthode existe
        if (!method_exists($controller, $route['action'])) {
            self::notFound("Action non trouvée: {$route['action']} dans $controllerClass");
            return;
        }
        
        // Appeler la méthode du contrôleur
        call_user_func_array([$controller, $route['action']], array_values($route['params']));
    }
    
    /**
     * Générer une URL à partir d'une définition de route
     * 
     * @param string $controller Nom du contrôleur
     * @param string $action Nom de l'action
     * @param array $params Paramètres supplémentaires (optionnel)
     * @return string URL générée
     */
    public static function url($controller, $action = 'index', $params = []) {
        // URL propre pour les navigateurs modernes
        $url = '/tui_calendar/' . strtolower($controller) . '/' . $action;
        
        // Ajouter les paramètres dans l'URL si nécessaire
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $url .= '/' . urlencode($key) . '/' . urlencode($value);
            }
        }
        
        return $url;
    }
    
    /**
     * Effectuer une redirection
     * 
     * @param string $controller Nom du contrôleur
     * @param string $action Nom de l'action
     * @param array $params Paramètres supplémentaires (optionnel)
     * @return void
     */
    public static function redirect($controller, $action = 'index', $params = []) {
        $url = self::url($controller, $action, $params);
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Gérer une page non trouvée
     * 
     * @param string $message Message d'erreur
     * @return void
     */
    public static function notFound($message = 'Page non trouvée') {
        header("HTTP/1.0 404 Not Found");
        echo "<h1>404 - Page non trouvée</h1>";
        echo "<p>$message</p>";
        exit;
    }
}
?>