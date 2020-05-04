<?php
require './pdos/DatabasePdo.php';
require './pdos/IndexPdo.php';
require './vendor/autoload.php';

use \Monolog\Logger as Logger;
use Monolog\Handler\StreamHandler;

date_default_timezone_set('Asia/Seoul');
ini_set('default_charset', 'utf8mb4');

//에러출력하게 하는 코드
//error_reporting(E_ALL); ini_set("display_errors", 1);

//Main Server API
$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    /* ******************   MainController   ****************** */
    //1. 회원가입 API
    $r->addRoute('POST', '/user', ['MainController', 'createUser']);

    //2. 로그인 API
    $r->addRoute('POST', '/login', ['MainController', 'createJwt']);

    //3. 회원탈퇴 API
    $r->addRoute('DELETE', '/user', ['MainController', 'deleteUser']);

    //4. Order 리셋 API
    $r->addRoute('DELETE', '/reset', ['MainController', 'reset']);

    /* ******************   IndexController   ****************** */

    //1. 아이템 리스트 조회 API
    $r->addRoute('GET', '/items', ['IndexController', 'getItems']);

    //2. 아이템 상세 조회 API
    $r->addRoute('GET', '/items/{itemID}', ['IndexController', 'getItemDetail']);

    //3. 아이템 색깔 조회 API
    $r->addRoute('GET', '/items/{itemID}/colors', ['IndexController', 'getItemColors']);

    //4. 아이템 사이즈 조회 API
    $r->addRoute('GET', '/items/{itemID}/sizes', ['IndexController', 'getItemSizes']);

    //5. 아이템 리뷰 총평 조회 API
    $r->addRoute('GET', '/items/{itemID}/summary', ['IndexController', 'getItemSummary']);

    //6. 아이템 리뷰 리스트 조회 API
    $r->addRoute('GET', '/items/{itemID}/comments', ['IndexController', 'getItemComments']);

    //7. 아이템 리뷰 작성 API
    $r->addRoute('POST', '/comment', ['IndexController', 'postComment']);

    //8. 아이템 리뷰 삭제 API
    $r->addRoute('DELETE', '/comment', ['IndexController', 'deleteComment']);

    //9. 쇼핑몰 리스트 조회 API
    $r->addRoute('GET', '/malls', ['IndexController', 'getMalls']);

    //10. 쇼핑몰 상세 조회 API
    $r->addRoute('GET', '/malls/{mallID}', ['IndexController', 'getMallDetail']);

    //11. 쇼핑몰 태그 설정 API
    $r->addRoute('POST', '/tag', ['IndexController', 'postTag']);

    //12. 최근 사용한 태그 리스트 조회 API
    $r->addRoute('GET', '/tags-recent', ['IndexController', 'getTagRecent']);

    //13. 태그 리스트 조회 API
    $r->addRoute('GET', '/tags', ['IndexController', 'getTag']);

    //14. 태그 삭제 API
    $r->addRoute('DELETE', '/tags', ['IndexController', 'deleteTag']);

    //15. 찜하기 API
    $r->addRoute('POST', '/heart', ['IndexController', 'postHeart']);

    //16. 찜한 아이템 개수 조회하기 API
    $r->addRoute('GET', '/hearts', ['IndexController', 'getHearts']);

    //17. 즐겨찾기하기 API
    $r->addRoute('POST', '/favorite', ['IndexController', 'postFavorite']);

    //18. 즐겨찾기한 쇼핑몰 개수 조회하기 API
    $r->addRoute('GET', '/favorites', ['IndexController', 'getHearts']);

    //19. 장바구니 추가 API
    $r->addRoute('POST', '/basket', ['IndexController', 'postBasket']);

    //19-1. 직접구매 API
    $r->addRoute('POST', '/order', ['IndexController', 'postOrder']);

    //20. 장바구니 아이템 리스트 조회 API
    $r->addRoute('GET', '/baskets', ['IndexController', 'getBaskets']);

    //21. 장바구니 아이템 삭제 API
    $r->addRoute('DELETE', '/basket', ['IndexController', 'deleteBaskets']);

    //22. 결제 총액 계산 API
    $r->addRoute('GET', '/totalpay', ['IndexController', 'totalPay']);

    //23. 배송지 설정 API
    $r->addRoute('POST', '/address', ['IndexController', 'postAddress']);

    //24. 주문 동의 확인 및 결제 API
    $r->addRoute('POST', '/payment', ['IndexController', 'payment']);

    //test sample
//    $r->addRoute('GET', '/', ['IndexController', 'index']);
//    $r->addRoute('GET', '/test', ['IndexController', 'test']);
//    $r->addRoute('GET', '/test/{testNo}', ['IndexController', 'testDetail']);
//    $r->addRoute('POST', '/test', ['IndexController', 'testPost']);
//    $r->addRoute('GET', '/jwt', ['MainController', 'validateJwt']);

    


//    $r->addRoute('GET', '/users', 'get_all_users_handler');
//    // {id} must be a number (\d+)
//    $r->addRoute('GET', '/user/{id:\d+}', 'get_user_handler');
//    // The /{title} suffix is optional
//    $r->addRoute('GET', '/articles/{id:\d+}[/{title}]', 'get_article_handler');
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

// 로거 채널 생성
$accessLogs = new Logger('ACCESS_LOGS');
$errorLogs = new Logger('ERROR_LOGS');
// log/your.log 파일에 로그 생성. 로그 레벨은 Info
$accessLogs->pushHandler(new StreamHandler('logs/access.log', Logger::INFO));
$errorLogs->pushHandler(new StreamHandler('logs/errors.log', Logger::ERROR));
// add records to the log
//$log->addInfo('Info log');
// Debug 는 Info 레벨보다 낮으므로 아래 로그는 출력되지 않음
//$log->addDebug('Debug log');
//$log->addError('Error log');

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        echo "404 Not Found";
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        echo "405 Method Not Allowed";
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        switch ($routeInfo[1][0]) {
            case 'IndexController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/IndexController.php';
                break;
            case 'MainController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/MainController.php';
                break;
            /*case 'EventController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/EventController.php';
                break;
            case 'ProductController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/ProductController.php';
                break;
            case 'SearchController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/SearchController.php';
                break;
            case 'ReviewController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/ReviewController.php';
                break;
            case 'ElementController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/ElementController.php';
                break;
            case 'AskFAQController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/AskFAQController.php';
                break;*/
        }

        break;
}
