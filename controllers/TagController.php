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
* API No. 14
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
* API No. 15
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
* API No. 16
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
* API No. 17
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
    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
