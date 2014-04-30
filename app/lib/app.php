<?php

class App {

  static public $site;
  static public $path;
  static public $routes = array();
  static public $router;
  static public $route;
  static public $language;

  static public function configure() {

    if(is_null(static::$site)) {
      static::$site = kirby::panelsetup();
    }

    // load all available routes
    static::$routes = array_merge(static::$routes, require(root('panel.app.routes') . DS . 'api.php'));
    static::$routes = array_merge(static::$routes, require(root('panel.app.routes') . DS . 'views.php'));
    static::$routes = array_merge(static::$routes, require(root('panel.app.routes') . DS . 'assets.php'));

    // start the router
    static::$router = new Router();
    static::$router->register(static::$routes);

    // content language switcher variable
    if($language = server::get('http_language')) {
      static::$language = $language;
    }

    // load the interface language file
    if(static::$site->user()) {
      $languageCode = static::$site->user()->language();
    } else {
      $languageCode = c::get('panel.language', 'en');
    }

    // validate the language code
    if(!in_array($languageCode, static::languages()->keys())) {
      $languageCode = 'en';
    }

    $language = require(root('panel.languages') . DS . $languageCode . '.php');

    // set all language variables
    l::$data = $language['data'];

    // register router filters
    static::$router->filter('auth', function() {
      if(!static::$site->user()) {
        go('panel/login');
      }
    });

    // check for a completed installation
    static::$router->filter('isInstalled', function() {
      if(static::$site->users()->count() == 0) {
        go('panel/install');
      }
    });

    // only use the fragments of the path without params
    static::$path = implode('/', (array)url::fragments(detect::path()));

  }

  static public function launch() {

    static::$route = static::$router->run(static::$path);

    // react on invalid routes
    if(!static::$route) {
      throw new Exception('Invalid route');
    }

    // let's find the controller and controller action
    $controllerParts  = str::split(static::$route->action(), '::');
    $controllerUri    = $controllerParts[0];
    $controllerAction = $controllerParts[1];
    $controllerFile   = root('panel.app.controllers') . DS . strtolower(str_replace('Controller', '', $controllerUri)) . '.php';
    $controllerName   = basename($controllerUri);

    // react on missing controllers
    if(!file_exists($controllerFile)) {
      throw new Exception('Invalid controller');
    }

    // load the controller
    require_once($controllerFile);

    // check for the called action
    if(!method_exists($controllerName, $controllerAction)) {
      throw new Exception('Invalid action');
    }

    // run the controller
    $controller = new $controllerName;

    // call the action and pass all arguments from the router
    $response = call(array($controller, $controllerAction), static::$route->arguments());

    // check for a valid response object
    if(is_a($response, 'Response')) {
      echo $response;
    } else {
      echo new Response($response);
    }

  }

  static public function languages() {

    $languages = new Collection;

    foreach(dir::read(root('panel.languages')) as $file) {
      $language = new Obj(require(root('panel.languages') . DS . $file));
      $language->code = str_replace('.php', '', $file);
      $languages->set($language->code, $language);
    }

    return $languages;

  }

}