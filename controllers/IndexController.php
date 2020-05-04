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

            $filter_input = "order by rand();"; //filter가 low면 저가순, high면 고가순, 없으면 랜덤, 그 외에는 접근 금지
            if(empty($_GET["filter"])){
                $filter_input == "order by rand();";
            }
            else if($_GET["filter"] == "low"){
                $filter_input = "order by price asc;";
            }
            else if($_GET["filter"] == "high"){
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


            if(empty($_GET["user_id"])){
                //리뷰 리스트 전체 조회
                $res->result = getItemComments($item_id);
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "아이템 리뷰 조회가 완료되었습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            else{
                if($_GET["user_id"] == $user_id){
                    //자신이 쓴 리뷰 리스트 조회
                    $res->result = getItemCommentsMyself($user_id,$item_id);
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

            $item_id = $req->item_id;
            $item_color = $req->item_color;
            $item_size = $req->item_size;
            $score = $req->score;
            $size = $req->size;
            $color = $req->color;
            $finish = $req->finish;
            $comment = $req->comment;

            //item 타입 및 존재 여부 체크
            if(nl2br(gettype($item_id)) != "integer"){
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

            //score 타입 체크
            if(nl2br(gettype($score)) != "integer"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //size, color, finish 값 체크
            if(nl2br(gettype($size)) != "integer"
            or nl2br(gettype($color)) != "integer"
            or nl2br(gettype($finish)) != "integer"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //image_url값이 없으면 "없음"으로 표시
            if(empty($req->image_url)){
                $image_url = " ";
            }
            else{
                $image_url = $req->image_url;
            }

            //comment, image_url 타입 체크
            if(nl2br(gettype($comment)) != "string"
            or nl2br(gettype($image_url)) != "string"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
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

            $comment_id = $_GET["id"];

            //입력받은 값 타입 체크
            if(nl2br(gettype($comment_id)) != "string"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }else{
                //존재하는 리뷰 아이디인지 그리고 그 리뷰를 해당 유저가 작성했는지 확인
                if(!isExistComment($user_id,$comment_id)){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "존재하지 않는 아이템입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            $res->result = deleteComment($user_id, $comment_id);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "리뷰 삭제가 완료되었습니다.";
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

            $item_id = $req->item_id;
            $size = $req->size;
            $color = $req->color;
            $num = $req->num;

            //item 타입 및 존재 여부 체크
            if(nl2br(gettype($item_id)) != "integer"){
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

            //size, color, num 타입 체크
            if(nl2br(gettype($size)) != "string"
            or nl2br(gettype($color)) != "string"
            or nl2br(gettype($num)) != "integer"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //상품에 맞는 색깔인지 검색
            if(!isExistColor($item_id, $color)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템에 존재하지 않는 색입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //상품에 맞는 사이즈인지 검색
            if(!isExistSize($item_id, $size)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템에 존재하지 않는 사이즈입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
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

            $item_id = $req->item_id;
            $size = $req->size;
            $color = $req->color;
            $num = $req->num;

            //item 타입 및 존재 여부 체크
            if(nl2br(gettype($item_id)) != "integer"){
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

            //size, color, num 타입 체크
            if(nl2br(gettype($size)) != "string"
                or nl2br(gettype($color)) != "string"
                or nl2br(gettype($num)) != "integer"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //상품에 맞는 색깔인지 검색
            if(!isExistColor($item_id, $color)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템에 존재하지 않는 색입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //상품에 맞는 사이즈인지 검색
            if(!isExistSize($item_id, $size)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템에 존재하지 않는 사이즈입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
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

            $item_id = $_GET["item_id"];

            //입력받은 값 타입 체크
            if(nl2br(gettype($item_id)) != "string"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }else{
                //존재하는 아이템 아이디인지 확인
                if(!isExistItemOnOrders($user_id,$item_id)){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "존재하지 않는 아이템입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
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

            //입력받은 값 체크
            if(empty($_GET["basket"])){
                $res->result = totalPay($user_id);
                $res->is_success = TRUE;
                $res->code = 100;
                $res->message = "총액 계산이 완료되었습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }else{
                $basket = $_GET["basket"];
            }

            if($basket == "Y"){
                $res->result = totalPayBasket($user_id);
                $res->is_success = TRUE;
                $res->code = 100;
                $res->message = "총액 계산이 완료되었습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            else if($basket == 'N'){
                $res->result = totalPay($user_id);
                $res->is_success = TRUE;
                $res->code = 100;
                $res->message = "총액 계산이 완료되었습니다.";
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

            $name = $req->name;
            $phone = $req->phone;
            $zipcode = $req->zipcode;
            $address = $req->address;
            $address_detail = $req->address_detail;
            if(empty($req->memo)){
                $memo = ' ';
            }
            else{
                $memo = $req->memo;
            }

            //타입 체크
            if(nl2br(gettype($name)) != "string"
            or nl2br(gettype($phone)) != "string"
            or nl2br(gettype($zipcode)) != "integer"
            or nl2br(gettype($address)) != "string"
            or nl2br(gettype($address_detail)) != "string"
            or nl2br(gettype($memo)) != "string"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
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
            else{$item1 = $req->order_id1;}

            //나머지 아이템은 빈칸일 시 공백으로 넘기기
            if(empty($req->order_id2)){$item2 = 0;}
            else{$item2 = $req->order_id2;}

            if(empty($req->order_id3)){$item3 = 0;}
            else{$item3 = $req->order_id3;}

            if(empty($req->order_id4)){$item4 = 0;}
            else{$item4 = $req->order_id4;}

            if(empty($req->order_id5)){$item5 = 0;}
            else{$item5 = $req->order_id5;}

            //item 타입 및 존재 여부 체크
            if(nl2br(gettype($item1)) != "integer"
            or nl2br(gettype($item2)) != "integer"
            or nl2br(gettype($item3)) != "integer"
            or nl2br(gettype($item4)) != "integer"
            or nl2br(gettype($item5)) != "integer"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }else{
                //존재하는 아이템 아이디인지 확인
                if(!isExistOrder($user_id,$item1)
                or !isExistOrder($user_id,$item2)
                or !isExistOrder($user_id,$item3)
                or !isExistOrder($user_id,$item4)
                or !isExistOrder($user_id,$item5)){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "존재하지 않는 아이템입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            $res->result = payment($user_id,$item1,$item2,$item3,$item4,$item5);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "장바구니 추가가 완료되었습니다.";
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
