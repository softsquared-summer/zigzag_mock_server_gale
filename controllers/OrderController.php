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
* API No. 22
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
                    if(!isExistItem($item_id)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이디입니다.";
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
* API No. 22-1
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
                    if(isDeletedItemOnOrders($user_id,$item_id)){
                        $res->result = recreateOrder($user_id, $item_id,$size,$color,$num);
                        $res->isSuccess = TRUE;
                        $res->code = 100;
                        $res->message = "결제창으로 이동합니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        break;
                    }
                }
            }

            $res->result = postOrder($user_id,$item_id,$size,$color,$num);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "결제창으로 이동합니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 23
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

            if(empty($_GET["page"])){
                $page = 1;
            }
            else{
                if(isBasketOverPage($user_id,$_GET["page"])){
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

            $res->result = getBaskets($user_id,$page);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "장바구니 리스트 조회가 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 24
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
* API No. 26
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
* API No. 27
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
* API No. 28
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
            if(empty($req->item_id1)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "구매할 아이템을 선택해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $order1 = $req->item_id1;
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
            if(empty($req->item_id2)){$order2 = -2;}
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

            if(empty($req->item_id3)){$order3 = -3;}
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

            if(empty($req->item_id4)){$order4 = -4;}
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

            if(empty($req->item_id5)){$order5 = -5;}
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
    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
