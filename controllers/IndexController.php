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
            $keyword = $_GET["keyword"];
            $category = $_GET["category"];
            $category_detail = $_GET["category_detail"];
            $ship = $_GET["ship"];
            $filter = $_GET["filter"];

            $keyword_input = "default"; //검색어 있으면 제목에서 찾기, 없으면 전체 조회
            if(empty($keyword)){
                $keyword_input = "where Item.id != -1";
            }
            else{
                if(nl2br(gettype($keyword)) == "string"){
                    $keyword_input = "where Item.name = "."%".$keyword."%";
                }
                else{
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.(keyword)";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            $category_input = "default"; //카테고리 없으면 전체 조회, 큰 것만 있으면 큰 카테고리, 큰 거 작은거 둘 다 있으면 작은 카테고리
            if(empty($category) and empty($category_detail)){
                $category_input = " and item_category.category_code != -1";
            }
            else if(!empty($category) and empty($category_detail)) {
                $category_code = categoryTextToCode($category);
                $category_input = " and item_category.category_code = ".$category_code;
            }
            else if(!empty($category) and !empty($category_detail)){
                    $category_detail_code = categoryDetailTextToCode($category_detail);
                    echo categoryTextToCode($category);
                    echo categoryDetailTextToCode($category_detail);
                    if(!isSameCategory(categoryTextToCode($category), categoryDetailTextToCode($category_detail))){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "세부 카테고리와 큰 카테고리가 일치하지 않습니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                    $category_input = " and item_category.category_detail_code = ".$category_detail_code;
            }
            else{
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.(category)";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }


            $ship_input = "default"; // ship = free가 아니면 접근 금지.
            if(empty($ship)){
                $ship_input = " and Item.Shipment != -1";
            }
            else{
                if($ship == 'free'){
                    $ship_input = " and Item.Shipment = 0";
                }
                else{
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 쿼리스트링 입력입니다.(ship)";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            $filter_input = "default"; //filter가 low면 저가순, high면 고가순, 없으면 랜덤, 그 외에는 접근 금지
            if(empty($filter)){
                $filter_input = "order by rand();";
            }
            else if($filter = "low"){
                $filter_input = "order by price asc;";
            }
            else if($filter = "high"){
                $filter_input = "order by price desc;";
            }
            else{
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 쿼리스트링 입력입니다.(filter)";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $res->result = getItems($user_id, $keyword_input, $category_input, $ship_input, $filter_input);
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

            $res->result = getItemColors($user_id, $item_id);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "테스트 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;


//test sample
//        /*
//         * API No. 0
//         * API Name : 테스트 API
//         * 마지막 수정 날짜 : 19.04.29
//         */
//        case "test":
//            http_response_code(200);
//            $text = [4];
//            $res->result = test($text);
//            $res->isSuccess = TRUE;
//            $res->code = 100;
//            $res->message = "테스트 성공";
//            echo json_encode($res, JSON_NUMERIC_CHECK);
//            break;
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
