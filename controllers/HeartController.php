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
* API No. 18
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
* API No. 19
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
    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
