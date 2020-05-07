<?php
//main controller

//회원가입 - 유저 추가 함수
function createUser($email, $password, $phone){

    $pdo = pdoSqlConnect();

    $query = "INSERT INTO User (email, password, phone) VALUES (?,?,?);";
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email, $password, $phone]);
    $st->setFetchMode(PDO::FETCH_ASSOC);

    $st=null;
    $pdo = null;

    return;
}

//회원 탈퇴
function deleteUser($email){

    $pdo = pdoSqlConnect();

    $query = "UPDATE User SET is_deleted = 'Y' where email = ?;";
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email]);
    $st->setFetchMode(PDO::FETCH_ASSOC);

    $st=null;
    $pdo = null;

    return;
    //장바구니 리셋
    function order_reset(){

        $pdo = pdoSqlConnect();

        $query = "UPDATE Orders SET status=999 where status=-999;";
        $st = $pdo->prepare($query);
        //    $st->execute([$param,$param]);
        $st->execute([]);
        $st->setFetchMode(PDO::FETCH_ASSOC);

        $st=null;
        $pdo = null;

        return;
    }
}