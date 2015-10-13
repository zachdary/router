<?php 
require ('crouter.php');

class Handler{
    public function hello($name){
        echo "Hello $name !!!";
    }
    public static function hello_again($name){
        echo "Hello $name again !!!";
    }
}
// product
//$router = include("router.inc.php");
//$router->execute();

/**
 * using CRouter to compile callback handlers in plan array.
 * no need to create the tree node by split the pathinfo.
 * will compile the router to source code into "router.inc.php".
 *
 * in product model, just need include the compiled source.
 * <pre>
 *     $router = include("router.inc.php");
 *     $router->execute();
 * </pre>
 */
// dev
(new CRouter('router.inc.php', true))
->error(401, function($message){
    header('Location: /login', true, 401);
    die($message);
})
->error(405, function($message){
    header('Location: /hello/world', true, 405);
})
->error(406, function($message){
    die($message);
})
->hook('auth', function($params){
    if ('lloyd' == $params['name'])
        return $params;
    $params['router']->error(401, 'Forbiden');
})
->hook('after', function($result, $router){
    if ($result) {
        header('Content-type: application/'. (isset($_GET['jsoncallback'])?'javascript':'json'));
        if (isset($_GET['jsoncallback']))
            print $_GET['jsoncallback']. '('. json_encode($result). ')';
        else print json_encode($result);
    }
})
->hook('before', function($params){
    //$params['name'] = 'lloydzhou';
    return $params;
})
->get('/', function(){
    echo "Hello world !!!";
})
->post('/hello', array('Handler', 'hello'), 'auth')
->get('/hello/:name', array(new Handler(), 'hello'))
->get('/hello/:name/again', array('Handler', 'hello_again'), 'auth')
->get('/hello/:name.:ext', function($name, $ext){
    if ('js' == $ext || 'json' == $ext) return array('name'=>$name);
    return array('code'=>1, 'msg'=>'error message...');
}, 'auth')
->execute();

/**
 * curl -vvv 127.0.0.1:8888/hello/
 * will trigger 405 error handler, should redirect to URL: "/hello/world"
 *
 * curl -vvv 127.0.0.1:8888/hello/lloyd 
 * will get 200 status code, and get body "Hello lloyd !!!"
 *
 * curl -vvv 127.0.0.1:8888/hello/lloyd/again 
 * will get 200 status code, and get body "Hello lloyd again !!!"
 *
 * curl -vvv 127.0.0.1:8888/hello/world/again 
 * will trigger 406 error handler, should redirect to URL: "/login"
 *
 * curl -vvv 127.0.0.1:8888/hello/lloyd.json 
 * will get 200 status code, and get body: {"name": "lloyd"}
 *
 * curl -vvv 127.0.0.1:8888/hello/lloyd.js?jsoncallback=test
 * will get 200 status code, and get body: test({"name": "lloyd"})
 *
 * curl -vvv 127.0.0.1:8888/hello/lloyd.jsx?jsoncallback=test
 * will get 200 status code, and get body: test({"code":1,"msg":"error message..."})
 */
