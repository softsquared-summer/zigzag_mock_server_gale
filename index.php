<?php
//Pdo & vendor requirement
require './pdos/DatabasePdo.php';
require './pdos/TagPdo.php';
require './pdos/CommentPdo.php';
require './pdos/ConditionPdo.php';
require './pdos/FavoritePdo.php';
require './pdos/HeartPdo.php';
require './pdos/ItemPdo.php';
require './pdos/MainPdo.php';
require './pdos/MallPdo.php';
require './pdos/OrderPdo.php';
require './pdos/OrderFinishPdo.php';
require './pdos/SubFuctionPdo.php';
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

    /* ******************   ItemController   ****************** */

    //1. 아이템 리스트 조회 API
    $r->addRoute('GET', '/items', ['ItemController', 'getItems']);

    //2. 아이템 상세 조회 API
    $r->addRoute('GET', '/items/{itemID}', ['ItemController', 'getItemDetail']);

    //3. 아이템 색깔 조회 API
    $r->addRoute('GET', '/items/{itemID}/colors', ['ItemController', 'getItemColors']);

    //4. 아이템 사이즈 조회 API
    $r->addRoute('GET', '/items/{itemID}/sizes', ['ItemController', 'getItemSizes']);

    /* ******************   CommentController   ****************** */

    //5. 아이템 리뷰 총평 조회 API
    $r->addRoute('GET', '/items/{itemID}/summary', ['CommentController', 'getItemSummary']);

    //6. 아이템 리뷰 리스트 조회 API
    $r->addRoute('GET', '/items/{itemID}/comments', ['CommentController', 'getItemComments']);

    //7. 아이템 리뷰 작성 API
    $r->addRoute('POST', '/comment', ['CommentController', 'postComment']);

    //8. 아이템 리뷰 삭제 API
    $r->addRoute('DELETE', '/comment', ['CommentController', 'deleteComment']);

    /* ******************   MallController   ****************** */

    //9. 쇼핑몰 리스트 조회 API
    $r->addRoute('GET', '/malls', ['MallController', 'getMalls']);

    //10. 쇼핑몰 상세 조회 API
    $r->addRoute('GET', '/malls/{mallID}', ['MallController', 'getMallDetail']);

    /* ******************   TagController   ****************** */

    //11. 쇼핑몰 태그 설정 API
    $r->addRoute('POST', '/tag', ['TagController', 'postTag']);

    //12. 최근 사용한 태그 리스트 조회 API
    $r->addRoute('GET', '/tags-recent', ['TagController', 'getTagRecent']);

    //13. 태그 리스트 조회 API
    $r->addRoute('GET', '/tags', ['TagController', 'getTag']);

    //14. 태그 삭제 API
    $r->addRoute('DELETE', '/tags', ['TagController', 'deleteTag']);

    /* ******************   HeartController   ****************** */

    //15. 찜하기 API
    $r->addRoute('POST', '/heart', ['HeartController', 'postHeart']);

    //16. 찜한 아이템 개수 조회하기 API
    $r->addRoute('GET', '/hearts', ['HeartController', 'getHearts']);

    /* ******************   FavoriteController   ****************** */

    //17. 즐겨찾기하기 API
    $r->addRoute('POST', '/favorite', ['FavoriteController', 'postFavorite']);

    //18. 즐겨찾기한 쇼핑몰 개수 조회하기 API
    $r->addRoute('GET', '/favorites', ['FavoriteController', 'getFavorites']);

    /* ******************   OrderController   ****************** */

    //19. 장바구니 추가 API
    $r->addRoute('POST', '/basket', ['OrderController', 'postBasket']);

    //19-1. 직접구매 API
    $r->addRoute('POST', '/order', ['OrderController', 'postOrder']);

    //20. 장바구니 아이템 리스트 조회 API
    $r->addRoute('GET', '/baskets', ['OrderController', 'getBaskets']);

    //21. 장바구니 아이템 삭제 API
    $r->addRoute('DELETE', '/basket', ['OrderController', 'deleteBaskets']);

    //22. 결제 총액 계산 API
    $r->addRoute('GET', '/totalpay', ['OrderController', 'totalPay']);

    //23. 배송지 설정 API
    $r->addRoute('POST', '/address', ['OrderController', 'postAddress']);

    //24. 주문 동의 확인 및 결제 API
    $r->addRoute('POST', '/payment', ['OrderController', 'payment']);

    /* ******************   OrderFinishController   ****************** */

    //25. 주문 완료 리스트 조회 API
    $r->addRoute('GET', '/orders', ['OrderFinishController', 'getOrders']);

    //26. 주문 완료 상세 조회 API
    $r->addRoute('GET', '/orders/{orderID}', ['OrderFinishController', 'getOrderDetail']);

//    //27. 주문 취소 API
//    $r->addRoute('DELETE', '/order', ['IndexController', 'deleteOrder']);

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
            case 'MainController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/MainController.php';
                break;
            case 'CommentController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/CommentController.php';
                break;
            case 'ItemController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/ItemController.php';
                break;
            case 'MallController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/MallController.php';
                break;
            case 'TagController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/TagController.php';
                break;
            case 'HeartController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/HeartController.php';
                break;
            case 'FavoriteController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/FavoriteController.php';
                break;
            case 'OrderController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/OrderController.php';
                break;
            case 'OrderFinishController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/OrderFinishController.php';
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
