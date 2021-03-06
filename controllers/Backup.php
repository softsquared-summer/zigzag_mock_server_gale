<?php
require 'function.php';

const JWT_SECRET_KEY = "TEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEY";

$res = (Object)Array();
header('Content-Type: json');
$req = json_decode(file_get_contents("php://input"));
try {
    addAccessLogs($accessLogs, $req);
    switch ($handler) {
        case "index":
            echo "API Server";
            break;
        case "ACCESS_LOGS":
            //            header('content-type text/html charset=utf-8');
            header('Content-Type: text/html; charset=UTF-8');
            getLogs("./logs/access.log");
            break;
        case "ERROR_LOGS":
            //            header('content-type text/html charset=utf-8');
            header('Content-Type: text/html; charset=UTF-8');
            getLogs("./logs/errors.log");
            break;

        /*
         * API No. 1
         * API Name : 아이템 리스트 조회 API
         * 마지막 수정 날짜 : 20.05.02
         */
        case "getItems":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //쿼리스트링으로 가져온 값 읽기

            $keyword_input = "Item.id != -1"; //검색어 있으면 제목에서 찾기, 없으면 전체 조회
            if(empty($_GET["keyword"])){
                $keyword_input = "Item.id != -1";
            }
            else{
                $keyword = $_GET["keyword"];
                if(nl2br(gettype($keyword)) == "string"){
                    $keyword_input = "Item.name like "."'%".$keyword."%'";
                }
                else{
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.(keyword)";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            $category_input = " item_category.category_code != -1"; //카테고리 없으면 전체 조회, 큰 것만 있으면 큰 카테고리, 큰 거 작은거 둘 다 있으면 작은 카테고리
            if(empty($_GET["category"]) and empty($_GET["category_detail"])){
                $category_input = " item_category.category_code != -1";
            }
            else if(!empty($_GET["category"]) and empty($_GET["category_detail"])) {
                if(isExistCategory($_GET["category"])){
                    $category = $_GET["category"];
                }else{
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.(category)";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                $category_code = categoryTextToCode($category);
                $category_input = " item_category.category_code = ".$category_code;
            }
            else if(!empty($_GET["category"]) and !empty($_GET["category_detail"])){
                if(isExistCategory($_GET["category"])){
                    $category = $_GET["category"];
                }else{
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.(category)";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                if(isExistCategoryDetail($_GET["category_detail"])){
                    $category_detail = $_GET["category_detail"];
                }else{
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.(category)";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                $category_detail_code = categoryDetailTextToCode($category_detail);
                    if(!isSameCategory(categoryTextToCode($category), categoryDetailTextToCode($category_detail))){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "세부 카테고리와 큰 카테고리가 일치하지 않습니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                    $category_input = " item_category.category_detail_code = ".$category_detail_code;
            }
            else{
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.(category)";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }


            $ship_input = " Mall.Shipment != -1"; // ship = free가 아니면 접근 금지.
            if(empty($_GET["ship"])){
                $ship_input = " Mall.Shipment != -1";
            }
            else{
                $ship = $_GET["ship"];
                if($ship == 'free'){
                    $ship_input = " Mall.Shipment = 0";
                }
                else{
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 쿼리스트링 입력입니다.(ship)";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            $filter_input = "order by Item.id desc"; //filter가 low면 저가순, high면 고가순, 없으면 랜덤, 그 외에는 접근 금지
            if(empty($_GET["filter"])){
                $filter_input == "order by Item.id desc";
            }
            else if($_GET["filter"] == "low"){
                $filter_input = "order by price asc";
            }
            else if($_GET["filter"] == "high"){
                $filter_input = "order by price desc";
            }
            else{
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 쿼리스트링 입력입니다.(filter)";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            if(empty($_GET["page"])){
                $page = 1;
            }
            else{
                if(isOverPage("Item",$_GET["page"])){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "초과된 페이지입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    $page = $_GET["page"];
                }
            }


            $res->result = getItems($user_id, $keyword_input, $category_input, $ship_input, $filter_input,$page);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "아이템 리스트 조회가 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
         * API No. 2
         * API Name : 아이템 상세 조회 API
         * 마지막 수정 날짜 : 19.05.02
         */
        case "getItemDetail":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $item_id = $vars["itemID"];

            //입력받은 값 타입 체크
            if(nl2br(gettype($item_id)) != "string"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }else{
                //존재하는 아이템 아이디인지 확인
                if(!isExistItem($item_id)){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "존재하지 않는 아이디입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            $res->result = getItemDetail($user_id, $item_id);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "테스트 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 3
* API Name : 아이템 색깔 조회 API
* 마지막 수정 날짜 : 19.05.02
*/
        case "getItemColors":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $item_id = $vars["itemID"];

            //입력받은 값 타입 체크
            if(nl2br(gettype($item_id)) != "string"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }else{
                //존재하는 아이템 아이디인지 확인
                if(!isExistItem($item_id)){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "존재하지 않는 아이디입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            $res->result = getItemColors($item_id);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "색깔 조회가 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 4
* API Name : 아이템 사이즈 조회 API
* 마지막 수정 날짜 : 19.05.02
*/
        case "getItemSizes":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $item_id = $vars["itemID"];

            //입력받은 값 타입 체크
            if(nl2br(gettype($item_id)) != "string"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }else{
                //존재하는 아이템 아이디인지 확인
                if(!isExistItem($item_id)){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "존재하지 않는 아이디입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            $res->result = getItemSizes($item_id);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "사이즈 조회가 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 5
* API Name : 아이템 리뷰 총평 조회 API
* 마지막 수정 날짜 : 19.05.02
*/
        case "getItemSummary":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $item_id = $vars["itemID"];

            //입력받은 값 타입 체크
            if(nl2br(gettype($item_id)) != "string"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }else{
                //존재하는 아이템 아이디인지 확인
                if(!isExistItem($item_id)){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "존재하지 않는 아이디입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            $res->result = getItemSummary($item_id);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "아이템 리뷰 총평 조회가 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

            /*
* API No. 6
* API Name : 아이템 리뷰 리스트 조회 API
* 마지막 수정 날짜 : 19.05.02
*/
        case "getItemComments":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $item_id = $vars["itemID"];

            //입력받은 값 타입 체크
            if(nl2br(gettype($item_id)) != "string"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }else{
                //존재하는 아이템 아이디인지 확인
                if(!isExistItem($item_id)){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "존재하지 않는 아이디입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            if(empty($_GET["page"])){
                $page = 1;
            }
            else{
                if(isOverPage("Comment",$_GET["page"])){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "초과된 페이지입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    $page = $_GET["page"];
                }
            }


            if(empty($_GET["user_id"])){
                //리뷰 리스트 전체 조회
                $res->result = getItemComments($item_id,$page);
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "아이템 리뷰 조회가 완료되었습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            else{
                if($_GET["user_id"] == $user_id){
                    //자신이 쓴 리뷰 리스트 조회
                    $res->result = getItemCommentsMyself($user_id,$item_id,$page);
                    $res->isSuccess = TRUE;
                    $res->code = 100;
                    $res->message = "회원님이 쓰신 리뷰 조회가 완료되었습니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
                else{
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 유저 아이디입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

        /*
* API No. 7
* API Name : 아이템 리뷰 작성 API
* 마지막 수정 날짜 : 19.05.03
*/
        case "postComment":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //변수별 post variation
            if(empty($req->item_id)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템 아이디를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $item_id = $req->item_id;
                //변수 타입 체크
                if(nl2br(gettype($item_id)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
                    if(!isExistItem($item_id)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이템입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            if(empty($req->item_color)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템 색깔을 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $item_color = $req->item_color;
                //변수 타입 체크
                if(nl2br(gettype($item_color)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
//                    //변수 존재성 체크
//                    if(!isExistColor($item_id,$item_color)){
//                        $res->is_success = FALSE;
//                        $res->code = 201;
//                        $res->message = "존재하지 않는 색깔입니다.";
//                        echo json_encode($res, JSON_NUMERIC_CHECK);
//                        return;
//                    }
                }
            }

            if(empty($req->item_size)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템 사이즈를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $item_size = $req->item_size;
                //변수 타입 체크
                if(nl2br(gettype($item_size)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
//                    if(!isExistSize($item_id,$item_size)){
//                        $res->is_success = FALSE;
//                        $res->code = 201;
//                        $res->message = "존재하지 않는 사이즈입니다.";
//                        echo json_encode($res, JSON_NUMERIC_CHECK);
//                        return;
//                    }
                }
            }

            if(empty($req->score)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "별점을 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $score = $req->score;
                //변수 타입 체크
                if(nl2br(gettype($score)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            if(empty($req->size)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "사이즈가 어땠는지 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $size = $req->size;
                //변수 타입 체크
                if(nl2br(gettype($size)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            if(empty($req->color)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "색깔이 어땠는지 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $color = $req->color;
                //변수 타입 체크
                if(nl2br(gettype($color)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            if(empty($req->finish)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "마감 상태가 어땠는지 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $finish = $req->finish;
                //변수 타입 체크
                if(nl2br(gettype($finish)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            if(empty($req->comment)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "리뷰 내용을 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $comment = $req->comment;
                //변수 타입 체크
                if(nl2br(gettype($comment)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            //image_url값이 없으면 "없음"으로 표시
            if(empty($req->image_url)){
                $image_url = " ";
            }
            else{
                $image_url = $req->image_url;
                if(nl2br(gettype($image_url)) != "string"
                or !isImagePNG($image_url)){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.png 확장자로 이미지를 넣어주세요.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            //유저가 사지 않은 제품은 못 사게 하기
            if(!isPurchaseUser($user_id, $item_id, $item_color, $item_size)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "구매하지 않은 아이템입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //이미 리뷰를 작성했을 경우 막기, 단 삭제된 경우 새로 진행
            if(isAlreayComment($user_id, $item_id, $item_color, $item_size)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 리뷰를 작성하신 아이템입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $res->result = postComment($user_id,$item_id,$score,$item_color,$item_size,$size,$color,$finish,$comment,$image_url );
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "리뷰가 작성되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 8
* API Name : 리뷰 삭제 API
* 마지막 수정 날짜 : 20.05.03
*/
        case "deleteComment":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            if(empty($_GET["id"])){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "리뷰 아이디를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $comment_id = $_GET["id"];
                //변수 타입 체크
                if(nl2br(gettype($comment_id)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
                    if(!isExistComment($user_id,$comment_id)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 리뷰입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            $res->result = deleteComment($user_id, $comment_id);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "리뷰 삭제가 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 9
* API Name : 쇼핑몰 리스트 조회 API
* 마지막 수정 날짜 : 20.05.04
*/
        case "getMalls":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            if(empty($_GET["page"])){
                $page = 1;
            }
            else{
                if(isOverPage("Mall",$_GET["page"])){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "초과된 페이지입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    $page = $_GET["page"];
                }
            }

            //쿼리스트링 태그값 가져오기
            if(empty($_GET["tag_id"])){
                $res->result = getMalls($user_id,$page);
                $res->is_success = TRUE;
                $res->code = 100;
                $res->message = "쇼핑몰 리스트 조회가 완료되었습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            else{
                $tag_id = $_GET["tag_id"];
                //쇼핑몰 아이디 타입 체크
                if(nl2br(gettype($tag_id)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //쇼핑몰 아이디 존재성 체크
                    if(!isExistTag($user_id,$tag_id)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이디입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            $res->result = getMallsWithTag($user_id,$tag_id,$page);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "쇼핑몰 리스트 조회가 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
     * API No. 10
     * API Name : 쇼핑몰 상세 조회 API
     * 마지막 수정 날짜 : 19.05.02
     */
        case "getMallDetail":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $mall_id = $vars["mallID"];

            //입력받은 값 타입 체크
            if(nl2br(gettype($mall_id)) != "string"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }else{
                //존재하는 아이템 아이디인지 확인
                if(!isExistMall($mall_id)){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "존재하지 않는 아이디입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            $res->result = getMallDetail($user_id, $mall_id);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "테스트 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 11
* API Name : 쇼핑몰 태그 설정 API
* 마지막 수정 날짜 : 19.05.04
*/
        case "postTag":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //쇼핑몰 아이디 비었을 때 : post variation 매뉴얼
            if(empty($req->mall_id)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "쇼핑몰 아이디를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $mall_id = $req->mall_id;
                //쇼핑몰 아이디 타입 체크
                if(nl2br(gettype($mall_id)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //쇼핑몰 아이디 존재성 체크
                    if(!isExistMall($mall_id)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이디입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            //태그 이름 비었을 때
            if(empty($req->tag_name)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "태그 이름을 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $tag_name = $req->tag_name;
                //쇼핑몰 아이디 타입 체크
                if(nl2br(gettype($tag_name)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            $res->result = postTag($user_id,$mall_id,$tag_name);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "태그가 추가되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 12
* API Name : 최근 사용한 태그 리스트 조회 API
* 마지막 수정 날짜 : 20.05.04
*/
        case "getTagRecent":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $res->result = getTagRecent($user_id);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "최근 사용한 태그 내역입니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 13
* API Name : 태그 리스트 조회 API
* 마지막 수정 날짜 : 20.05.04
*/
        case "getTag":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            if(empty($_GET["page"])){
                $page = 1;
            }
            else{
                if(isOverPage("Tag",$_GET["page"])){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "초과된 페이지입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    $page = $_GET["page"];
                }
            }

            $res->result = getTag($user_id,$page);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "최근 사용한 태그 내역입니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 14
* API Name : 태그 삭제 API
* 마지막 수정 날짜 : 20.05.04
*/
        case "deleteTag":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            if(empty($_GET["tag_id"])){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "태그 아이디를 입력해주세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $tag_id = $_GET["tag_id"];
            }

            //입력받은 값 타입 체크
            if(nl2br(gettype($tag_id)) != "string"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }else{
                //존재하는 태그인지 확인
                if(!isExistTag($user_id,$tag_id)){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "존재하지 않는 태그입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            //이미 삭제된 태그인지
            if(isDeletedTag($user_id,$tag_id)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 태그입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $res->result = deleteTag($user_id, $tag_id);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "선택하신 태그가 삭제되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 15
* API Name : 찜하기 API
* 마지막 수정 날짜 : 19.05.04
*/
        case "postHeart":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //쇼핑몰 아이디 비었을 때 : post variation 매뉴얼
            if(empty($req->item_id)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템 아이디를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $item_id = $req->item_id;
                //쇼핑몰 아이디 타입 체크
                if(nl2br(gettype($item_id)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //쇼핑몰 아이디 존재성 체크
                    if(!isExistItem($item_id)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이템입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            //찜하기 되어있는 경우, 안되어있는 경우, 했다가 취소했다가 다시 한 경우 3개로 나누기

            //처음으로 찜하기
            if(!isExistHeart($user_id, $item_id)){
                $res->result = postHeart($user_id, $item_id);
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "찜한 아이템에 추가했어요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            else{
                //찜 삭제된 것 다시 하기
                if(isDeletedHeart($user_id, $item_id)){
                    $res->result = recreateHeart($user_id, $item_id);
                    $res->isSuccess = TRUE;
                    $res->code = 100;
                    $res->message = "찜한 아이템에 추가했어요";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
                else{
                    $res->result = deleteHeart($user_id, $item_id);
                    $res->isSuccess = TRUE;
                    $res->code = 100;
                    $res->message = "찜한 아이템에서 삭제했어요";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
            }

        /*
* API No. 16
* API Name : 찜한 아이템 리스트 조회 API
* 마지막 수정 날짜 : 20.05.04
*/
        case "getHearts":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            if(empty($_GET["page"])){
                $page = 1;
            }
            else{
                if(isOverPage("Heart",$_GET["page"])){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "초과된 페이지입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    $page = $_GET["page"];
                }
            }

            $res->result = getHearts($user_id,$page);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "찜한 아이템 조회가 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 17
* API Name : 즐겨찾기하기 API
* 마지막 수정 날짜 : 19.05.04
*/
        case "postFavorite":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //쇼핑몰 아이디 비었을 때 : post variation 매뉴얼
            if(empty($req->mall_id)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "쇼핑몰 아이디를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $mall_id = $req->mall_id;
                //쇼핑몰 아이디 타입 체크
                if(nl2br(gettype($mall_id)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //쇼핑몰 아이디 존재성 체크
                    if(!isExistMall($mall_id)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이템입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            //찜하기 되어있는 경우, 안되어있는 경우, 했다가 취소했다가 다시 한 경우 3개로 나누기

            //처음으로 찜하기
            if(!isExistFavorite($user_id, $mall_id)){
                $res->result = postFavorite($user_id, $mall_id);
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "즐겨찾기에 등록되었습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            else{
                //찜 삭제된 것 다시 하기
                if(isDeletedFavorite($user_id, $mall_id)){
                    $res->result = recreateFavorite($user_id, $mall_id);
                    $res->isSuccess = TRUE;
                    $res->code = 100;
                    $res->message = "즐겨찾기에 등록되었습니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
                else{
                    $res->result = deleteFavorite($user_id, $mall_id);
                    $res->isSuccess = TRUE;
                    $res->code = 100;
                    $res->message = "즐겨찾기에서 삭제되었습니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
            }

        /*
* API No. 18
* API Name : 즐겨찾기한 아이템 리스트 조회 API
* 마지막 수정 날짜 : 20.05.04
*/
        case "getFavorites":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            if(empty($_GET["page"])){
                $page = 1;
            }
            else{
                if(isOverPage("Favorite",$_GET["page"])){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "초과된 페이지입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    $page = $_GET["page"];
                }
            }

            $res->result = getFavorites($user_id,$page);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "즐겨찾기 쇼핑몰 조회가 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 19
* API Name : 장바구니 추가 API
* 마지막 수정 날짜 : 19.05.03
*/
        case "postBasket":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //변수별 post variation
            if(empty($req->item_id)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템 아이디를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $item_id = $req->item_id;
                //변수 타입 체크
                if(nl2br(gettype($item_id)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
                    if(isExistItemOnOrders($user_id,$item_id)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "이미 존재하는 아이디입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            if(empty($req->size)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템 사이즈를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $size = $req->size;
                //변수 타입 체크
                if(nl2br(gettype($size)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
//                    if(!isExistSize($item_id,$size)){
//                        $res->is_success = FALSE;
//                        $res->code = 201;
//                        $res->message = "존재하지 않는 사이즈입니다.";
//                        echo json_encode($res, JSON_NUMERIC_CHECK);
//                        return;
//                    }
                }
            }

            if(empty($req->color)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템 색깔를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $color = $req->color;
                //변수 타입 체크
                if(nl2br(gettype($color)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
//                    if(!isExistColor($item_id,$color)){
//                        $res->is_success = FALSE;
//                        $res->code = 201;
//                        $res->message = "존재하지 않는 색깔입니다.";
//                        echo json_encode($res, JSON_NUMERIC_CHECK);
//                        return;
//                    }
                }
            }

            if(empty($req->num)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템 개수를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $num = $req->num;
                //변수 타입 체크
                if(nl2br(gettype($num)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            //구매하지 않고 삭제된 아이템 다시 장바구니에 넣을 경우
            if(isDeletedItemOnOrders($user_id,$item_id)){
                $res->result = recreateBasket($user_id, $item_id,$size,$color,$num);
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "장바구니 추가가 완료되었습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            $res->result = postBasket($user_id, $item_id,$size,$color,$num);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "장바구니 추가가 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 19-1
* API Name : 직접구매 API
* 마지막 수정 날짜 : 19.05.03
*/
        case "postOrder":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //변수별 post variation
            if(empty($req->item_id)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템 아이디를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $item_id = $req->item_id;
                //변수 타입 체크
                if(nl2br(gettype($item_id)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
                    if(isExistItem($item_id)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "이미 존재하는 아이디입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            if(empty($req->size)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템 사이즈를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $size = $req->size;
                //변수 타입 체크
                if(nl2br(gettype($size)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
//                    if(!isExistSize($item_id,$size)){
//                        $res->is_success = FALSE;
//                        $res->code = 201;
//                        $res->message = "존재하지 않는 사이즈입니다.";
//                        echo json_encode($res, JSON_NUMERIC_CHECK);
//                        return;
//                    }
                }
            }

            if(empty($req->color)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템 색깔를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $color = $req->color;
                //변수 타입 체크
                if(nl2br(gettype($color)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
//                    if(!isExistColor($item_id,$color)){
//                        $res->is_success = FALSE;
//                        $res->code = 201;
//                        $res->message = "존재하지 않는 색깔입니다.";
//                        echo json_encode($res, JSON_NUMERIC_CHECK);
//                        return;
//                    }
                }
            }

            if(empty($req->num)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템 개수를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $num = $req->num;
                //변수 타입 체크
                if(nl2br(gettype($num)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            //구매하지 않고 삭제된 아이템 다시 직접구매할 경우
            if(isDeletedItemOnOrders($user_id,$item_id)){
                $res->result = recreateOrder($user_id, $item_id,$size,$color,$num);
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "결제창으로 이동합니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            $res->result = postOrder($user_id,$item_id,$size,$color,$num);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "결제창으로 이동합니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 20
* API Name : 장바구니 아이템 리스트 조회 API
* 마지막 수정 날짜 : 20.05.02
*/
        case "getBaskets":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $res->result = getBaskets($user_id);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "장바구니 리스트 조회가 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 21
* API Name : 장바구니 삭제 API
* 마지막 수정 날짜 : 20.05.03
*/
        case "deleteBaskets":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            if(empty($_GET["item_id"])){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템 아이디를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $item_id = $_GET["item_id"];
                //변수 타입 체크
                if(nl2br(gettype($item_id)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
                    if(!isExistItemOnOrders($user_id,$item_id)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이템입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            $res->result = deleteBaskets($user_id, $item_id);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "선택하신 상품이 삭제되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 22
* API Name : 결제 총액 계산 API
* 마지막 수정 날짜 : 20.05.03
*/
        case "totalPay":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //아이템 선택 하나도 안 되어있을 시 결제 막기
            if(empty($_GET["item_id1"])){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "구매할 아이템을 선택해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $item1 = $_GET["item_id1"];
                //item 타입 및 존재 여부 체크
                if(nl2br(gettype($item1)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }else{
                    //존재하는 아이템 아이디인지 확인
                    if(!isExistItem($item1)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이템1입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            //나머지 아이템은 빈칸일 시 공백으로 넘기기
            if(empty($_GET["item_id2"])){$item2 = -2;}
            else{
                $item2 = $_GET["item_id2"];
                //item 타입 및 존재 여부 체크
                if(nl2br(gettype($item2)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }else{
                    //존재하는 아이템 아이디인지 확인
                    if(!isExistItem($item2)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이템2입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            if(empty($_GET["item_id3"])){$item3 = -3;}
            else{
                $item3 = $_GET["item_id3"];
                //item 타입 및 존재 여부 체크
                if(nl2br(gettype($item3)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }else{
                    //존재하는 아이템 아이디인지 확인
                    if(!isExistItem($item3)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이템3입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            if(empty($_GET["item_id4"])){$item4 = -4;}
            else{
                $item4 = $_GET["item_id4"];
                //item 타입 및 존재 여부 체크
                if(nl2br(gettype($item4)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }else{
                    //존재하는 아이템 아이디인지 확인
                    if(!isExistItem($item4)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이템4입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            if(empty($_GET["item_id5"])){$item5 = -5;}
            else{
                $item5 = $_GET["item_id5"];
                //item 타입 및 존재 여부 체크
                if(nl2br(gettype($item5)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }else{
                    //존재하는 아이템 아이디인지 확인
                    if(!isExistItem($item5)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이템5입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            if(!isAllDifferent($item1,$item2,$item3,$item4,$item5)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "중복되는 아이템이 존재합니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $res->result = totalPay((int)$item1,(int)$item2,(int)$item3,(int)$item4,(int)$item5);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "총액 계산이 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 23
* API Name : 배송지 설정 API
* 마지막 수정 날짜 : 19.05.04
*/
        case "postAddress":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //변수별 post variation
            if(empty($req->name)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이름를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $name = $req->name;
                //변수 타입 체크
                if(nl2br(gettype($name)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            if(empty($req->phone)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "번호를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $phone = $req->phone;
                //변수 타입 체크
                if(nl2br(gettype($phone)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            if(empty($req->zipcode)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "우편번호를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $zipcode = $req->zipcode;
                //변수 타입 체크
                if(nl2br(gettype($zipcode)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            if(empty($req->address)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "주소를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $address = $req->address;
                //변수 타입 체크
                if(nl2br(gettype($address)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            if(empty($req->address_detail)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "주소를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $address_detail = $req->address_detail;
                //변수 타입 체크
                if(nl2br(gettype($address_detail)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            if(empty($req->memo)){
                $memo = ' ';
            }
            else{
                $memo = $req->memo;
                if(nl2br(gettype($memo)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            //기존에 설정된 배송지는 추가 막기
            if(isExistAddress($zipcode,$address,$address_detail)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 등록된 배송지입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $res->result = postAddress($user_id, $name,$phone,$zipcode,$address,$address_detail,$memo);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "배송지 설정이 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 24
* API Name : 주문 동의 확인 및 결제 API
* 마지막 수정 날짜 : 19.05.03
*/
        case "payment":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            // 만 14세 이상 결제 동의 체크 여부 확인
            if(empty($req->is_over_14) or $req->is_over_14 != 'Y'){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "만 14세 이상 결제 동의에 체크해주시기 바랍니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            // 서비스 이용약관 및 개인정보 수집 이용 동의 체크 여부 확인
            if(empty($req->is_service_agree) or $req->is_service_agree != 'Y'){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "서비스 이용약관 및 개인정보 수집 이용 동의에 체크해주시기 바랍니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            // 서비스 이용약관 및 개인정보 수집 이용 동의 체크 여부 확인
            if(empty($req->is_order_agree) or $req->is_order_agree != 'Y'){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "주문내용 확인 및 결제 동의에 체크해주시기 바랍니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //아이템 선택 하나도 안 되어있을 시 결제 막기
            if(empty($req->order_id1)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "구매할 아이템을 선택해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $order1 = $req->order_id1;
                //item 타입 및 존재 여부 체크
                if(nl2br(gettype($order1)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }else{
                    //존재하는 아이템 아이디인지 확인
                    if(!isExistOrder($user_id,$order1)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이템1입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            //나머지 아이템은 빈칸일 시 공백으로 넘기기
            if(empty($req->order_id2)){$order2 = -2;}
            else{
                $order2 = $req->order_id2;
                //item 타입 및 존재 여부 체크
                if(nl2br(gettype($order2)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }else{
                    //존재하는 아이템 아이디인지 확인
                    if(!isExistOrder($user_id,$order2)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이템2입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            if(empty($req->order_id3)){$order3 = -3;}
            else{
                $order3 = $req->order_id3;
                //item 타입 및 존재 여부 체크
                if(nl2br(gettype($order3)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }else{
                    //존재하는 아이템 아이디인지 확인
                    if(!isExistOrder($user_id,$order3)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이템3입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            if(empty($req->order_id4)){$order4 = -4;}
            else{
                $order4 = $req->order_id4;
                //item 타입 및 존재 여부 체크
                if(nl2br(gettype($order4)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }else{
                    //존재하는 아이템 아이디인지 확인
                    if(!isExistOrder($user_id,$order4)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이템4입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            if(empty($req->order_id5)){$order5 = -5;}
            else{
                $order5 = $req->order_id5;
                //item 타입 및 존재 여부 체크
                if(nl2br(gettype($order5)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }else{
                    //존재하는 아이템 아이디인지 확인
                    if(!isExistOrder($user_id,$order5)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이템5입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            //입력받은 아이템끼리 중복되면 안됨
            if(!isAllDifferent($order1,$order2,$order3,$order4,$order5)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "중복되는 아이템이 존재합니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            if(empty($_GET["type"])){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "입금방식을 입력해주세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $type = $_GET["type"];
                //item 타입 및 존재 여부 체크
                if($type == "bank"){
                    $res->result = payment($user_id,$order1,$order2,$order3,$order4,$order5);
                    $res->isSuccess = TRUE;
                    $res->code = 100;
                    $res->message = "결제가 완료되었습니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }else if($type == "none_bank"){
                    $res->result = payment ($user_id,$order1,$order2,$order3,$order4,$order5);
                    $res->isSuccess = TRUE;
                    $res->code = 100;
                    $res->message = "결제가 완료되었습니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
                else{
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 입력입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

        /*
* API No. 25
* API Name : 주문 완료 리스트 조회 API
* 마지막 수정 날짜 : 20.05.02
*/
        case "getOrders":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //쿼리스트링 태그값 가져오기
            if(empty($_GET["order_id"])){
                $res->result = getOrders($user_id);
                $res->is_success = TRUE;
                $res->code = 100;
                $res->message = "주문 완료 조회가 완료되었습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            else{
                $item_id = $_GET["order_id"];
                //쇼핑몰 아이디 타입 체크
                if(nl2br(gettype($item_id)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //쇼핑몰 아이디 존재성 체크
                    if(!isPurchaseItem($user_id,$item_id)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "구매하지 않은 아이템입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            $res->result = getOrdersWithItem($user_id,$item_id);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "주문 완료 조회가 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 26
* API Name : 주문 완료 상세 조회 API
* 마지막 수정 날짜 : 20.05.05
*/
        case "getOrderDetail":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //쿼리스트링 태그값 가져오기
            if(empty($vars["order_id"])){
                $res->result = getOrders($user_id);
                $res->is_success = TRUE;
                $res->code = 100;
                $res->message = "주문 완료 조회가 완료되었습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            else{
                $completion_id = $vars["completionID"];
                //쇼핑몰 아이디 타입 체크
                if(nl2br(gettype($completion_id)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //쇼핑몰 아이디 존재성 체크
                    if(!isExistCompletion($user_id,$completion_id)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "주문 완료 내역이 존재하지 않습니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            $res->result = getOrderDetail($user_id,$completion_id);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "주문 완료 조회가 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 27
* API Name : 주문 취소 API
* 마지막 수정 날짜 : 20.05.03
*/
        case "deleteOrder":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            if(empty($_GET["order_id"])){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "주문 아이디를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $order_id = $_GET["order_id"];
                //변수 타입 체크
                if(nl2br(gettype($order_id)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
                    if(!isExistOrder($user_id,$order_id)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 주문입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            $res->result = deleteOrder($user_id, $order_id);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "주문이 정상적으로 취소되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;
//test sample
//        /*
//         * API No. 0
//         * API Name : 테스트 API
//         * 마지막 수정 날짜 : 19.04.29
//         */
        case "test":
            http_response_code(200);
            $res->result = test();
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "테스트 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;
//        /*
//         * API No. 0
//         * API Name : 테스트 Path Variable API
//         * 마지막 수정 날짜 : 19.04.29
//         */
//        case "testDetail":
//            http_response_code(200);
//            $res->result = testDetail($vars["testNo"]);
//            $res->isSuccess = TRUE;
//            $res->code = 100;
//            $res->message = "테스트 성공";
//            echo json_encode($res, JSON_NUMERIC_CHECK);
//            break;
//        /*
//         * API No. 0
//         * API Name : 테스트 Body & Insert API
//         * 마지막 수정 날짜 : 19.04.29
//         */
//        case "testPost":
//            http_response_code(200);
//            $res->result = testPost($req->name);
//            $res->isSuccess = TRUE;
//            $res->code = 100;
//            $res->message = "테스트 성공";
//            echo json_encode($res, JSON_NUMERIC_CHECK);
//            break;
    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
