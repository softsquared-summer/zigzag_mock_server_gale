<?php

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


//-----------------------조건식--------------------------//

//기존에 있는 이메일인지 판단
function isExistEmail($email){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM User where email = ?) as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//기존에 있는 번호인지 판단
function isExistPhone($phone){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM User where phone = ?) as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$phone]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//삭제된 유저인지 판단
function isDeletedUser($email){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM User where email = ? and isDeleted = 'Y') as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

function isValidUser($email, $password){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM User WHERE email= ? AND password = ?) AS exist;";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email, $password]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);

}

//삭제 유저 재가입 함수
function recreateUser($email){

    $pdo = pdoSqlConnect();

    $query = "UPDATE User SET isDeleted = 'N' where email = ?;";
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email]);
    $st->setFetchMode(PDO::FETCH_ASSOC);

    $st=null;
    $pdo = null;

    return;
}


// CREATE
//    function addMaintenance($message){
//        $pdo = pdoSqlConnect();
//        $query = "INSERT INTO MAINTENANCE (MESSAGE) VALUES (?);";
//
//        $st = $pdo->prepare($query);
//        $st->execute([$message]);
//
//        $st = null;
//        $pdo = null;
//
//    }


// UPDATE
//    function updateMaintenanceStatus($message, $status, $no){
//        $pdo = pdoSqlConnect();
//        $query = "UPDATE MAINTENANCE
//                        SET MESSAGE = ?,
//                            STATUS  = ?
//                        WHERE NO = ?";
//
//        $st = $pdo->prepare($query);
//        $st->execute([$message, $status, $no]);
//        $st = null;
//        $pdo = null;
//    }

// RETURN BOOLEAN
//    function isRedundantEmail($email){
//        $pdo = pdoSqlConnect();
//        $query = "SELECT EXISTS(SELECT * FROM USER_TB WHERE EMAIL= ?) AS exist;";
//
//
//        $st = $pdo->prepare($query);
//        //    $st->execute([$param,$param]);
//        $st->execute([$email]);
//        $st->setFetchMode(PDO::FETCH_ASSOC);
//        $res = $st->fetchAll();
//
//        $st=null;$pdo = null;
//
//        return intval($res[0]["exist"]);
//
//    }

//READ
function test()
{
    $pdo = pdoSqlConnect();
    $query = "SELECT * FROM Test;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

//READ
function testDetail($testNo)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT * FROM Test WHERE no = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$testNo]);
    //    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}


function testPost($name)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO Test (name) VALUES (?);";

    $st = $pdo->prepare($query);
    $st->execute([$name]);

    $st = null;
    $pdo = null;

}
