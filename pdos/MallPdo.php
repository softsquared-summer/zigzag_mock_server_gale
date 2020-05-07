<?php

//쇼핑몰 리스트 조회 API
function getMalls($user_id,$page)
{
    $pdo = pdoSqlConnect();
    $query = "
select

Mall.id as mall_id,
mall_image.image_url as image_url,
if(
    Favorite.user_id = ?,
    'Y',
    'N'
    )
as is_favorite,
Mall.name as mall_name,
(
    SELECT COUNT(*) + 1 
    FROM 
    (
        select
        count(if(Favorite.mall_id = Mall.id,1,null)) as num,
        ifnull(Mall.id,0) as mall_id
        from Mall
        left join Favorite
        on Mall.id = Favorite.mall_id
        group by Mall.id
    )
    mall_rank WHERE num > b.num
) AS mall_rank,
' ' as tags

from (
    select
    count(if(Favorite.mall_id = Mall.id,1,null)) as num,
    ifnull(Mall.id,0) as mall_id
    from Mall
    
    left join Favorite
    on Mall.id = Favorite.mall_id
    group by Mall.id
    ) b

left join Mall
on Mall.id = b.mall_id

inner join mall_image
on Mall.id = mall_image.id

left join Favorite
on Favorite.mall_id = Mall.id

left join (
    select
count(
	if(
		Favorite.mall_id = Mall.id,
        1,
        null
    )
) as num,
ifnull(
	Favorite.mall_id,
    0
) as mall_id
from Mall

left join Favorite
on Mall.id = Favorite.mall_id

group by Mall.id
    ) mall_rank
on mall_rank.mall_id = Mall.id

order by mall_rank asc";

    $st = $pdo->prepare($query." limit ".(($page-1)*6).", 6;");
    //    $st->execute([$param,$param]);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res_body = $st->fetchAll();

    $st=null;

    $query_tag = "select
(
    SELECT COUNT(*) + 1
    FROM
    (
        select
        count(if(Favorite.mall_id = Mall.id,1,null)) as num,
        ifnull(Mall.id,0) as mall_id
        from Mall
        left join Favorite
        on Mall.id = Favorite.mall_id
        group by Mall.id
    )
    mall_rank WHERE num > b.num
) AS mall_rank,
ifnull(Tag.id ,'없음') as tags,
ifnull(Tag.name ,'없음') as name

from (
    select
    count(if(Favorite.mall_id = Mall.id,1,null)) as num,
    ifnull(Mall.id,0) as mall_id
    from Mall

    left join Favorite
    on Mall.id = Favorite.mall_id
    group by Mall.id
    ) b

left join Mall
on Mall.id = b.mall_id

inner join mall_image
on Mall.id = mall_image.id

left join Favorite
on Favorite.mall_id = Mall.id

left join (
    select
count(
	if(
		Favorite.mall_id = Mall.id,
        1,
        null
    )
) as num,
ifnull(
	Favorite.mall_id,
    0
) as mall_id
from Mall

left join Favorite
on Mall.id = Favorite.mall_id

group by Mall.id
    ) mall_rank
on mall_rank.mall_id = Mall.id

left join Tag
    on Tag.mall_id = Mall.id

order by mall_rank asc";


    $st2 = $pdo->prepare($query_tag." limit ".(($page-1)*6).", 6;");
    //    $st->execute([$param,$param]);
    $st2->execute([]);
    $st2->setFetchMode(PDO::FETCH_ASSOC);
    $res_tag = $st2->fetchAll();

    for($i = 0;$i<count($res_body);$i++){
        $res_body[$i]["tags"] = $res_tag[$i];
    }

    $res = $res_body;

    $pdo = null;

    return $res;
}

//쇼핑몰 리스트 조회 API
function getMallsWithTag($user_id,$tag_id,$page)
{
    $pdo = pdoSqlConnect();
    $query = "
select

Mall.id as mall_id,
mall_image.image_url as image_url,
if(
    Favorite.user_id = ?,
    'Y',
    'N'
    )
as is_favorite,
Mall.name as mall_name,
(
    SELECT COUNT(*) + 1 
    FROM 
    (
        select
        count(if(Favorite.mall_id = Mall.id,1,null)) as num,
        ifnull(Mall.id,0) as mall_id
        from Mall
        left join Favorite
        on Mall.id = Favorite.mall_id
        group by Mall.id
    )
    mall_rank WHERE num > b.num
) AS mall_rank,
' ' as tags

from (
    select
    count(if(Favorite.mall_id = Mall.id,1,null)) as num,
    ifnull(Mall.id,0) as mall_id
    from Mall
    
    left join Favorite
    on Mall.id = Favorite.mall_id
    group by Mall.id
    ) b

left join Mall
on Mall.id = b.mall_id

inner join mall_image
on Mall.id = mall_image.id

left join Favorite
on Favorite.mall_id = Mall.id

left join (
    select
count(
	if(
		Favorite.mall_id = Mall.id,
        1,
        null
    )
) as num,
ifnull(
	Favorite.mall_id,
    0
) as mall_id
from Mall

left join Favorite
on Mall.id = Favorite.mall_id

group by Mall.id
    ) mall_rank
on mall_rank.mall_id = Mall.id

left join Tag
on Tag.mall_id = Mall.id

where Tag.id = ?

order by mall_rank asc";

    $st = $pdo->prepare($query." limit ".(($page-1)*6).", 6;");
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$tag_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res_body = $st->fetchAll();

    $st=null;

    $query_tag = "select
(
    SELECT COUNT(*) + 1
    FROM
    (
        select
        count(if(Favorite.mall_id = Mall.id,1,null)) as num,
        ifnull(Mall.id,0) as mall_id
        from Mall
        left join Favorite
        on Mall.id = Favorite.mall_id
        group by Mall.id
    )
    mall_rank WHERE num > b.num
) AS mall_rank,
ifnull(Tag.id ,'없음') as tags,
ifnull(Tag.name ,'없음') as name

from (
    select
    count(if(Favorite.mall_id = Mall.id,1,null)) as num,
    ifnull(Mall.id,0) as mall_id
    from Mall

    left join Favorite
    on Mall.id = Favorite.mall_id
    group by Mall.id
    ) b

left join Mall
on Mall.id = b.mall_id

inner join mall_image
on Mall.id = mall_image.id

left join Favorite
on Favorite.mall_id = Mall.id

left join (
    select
count(
	if(
		Favorite.mall_id = Mall.id,
        1,
        null
    )
) as num,
ifnull(
	Favorite.mall_id,
    0
) as mall_id
from Mall

left join Favorite
on Mall.id = Favorite.mall_id

group by Mall.id
    ) mall_rank
on mall_rank.mall_id = Mall.id

left join Tag
    on Tag.mall_id = Mall.id
    
where Tag.id = ?

order by mall_rank asc";


    $st2 = $pdo->prepare($query_tag." limit ".(($page-1)*6).", 6;");
    //    $st->execute([$param,$param]);
    $st2->execute([$tag_id]);
    $st2->setFetchMode(PDO::FETCH_ASSOC);
    $res_tag = $st2->fetchAll();

    for($i = 0;$i<count($res_body);$i++){
        $res_body[$i]["tags"] = $res_tag[$i];
    }

    $res = $res_body;

    $pdo = null;

    return $res;
}

//쇼핑몰 상세 조회 API
function getMallDetail($user_id,$mall_id)
{
    $pdo = pdoSqlConnect();
    $query = "
select

Mall.id as mall_id,
mall_image.image_url as image_url,
if(
    Favorite.user_id = ?,
    'Y',
    'N'
    )
as is_favorite,
Mall.name as mall_name,
' ' as tags

from Mall

inner join mall_image
on Mall.id = mall_image.id

left join Favorite
on Favorite.mall_id = Mall.id

where Mall.id = ?;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$mall_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res_body = $st->fetchAll();

    $st=null;

    $query_tag = "
select
Tag.id as tag_id,
Tag.name as tag_name
from Mall

inner join Tag
on Tag.mall_id = Mall.id

where Mall.id = ?;";


    $st2 = $pdo->prepare($query_tag);
    //    $st->execute([$param,$param]);

    $st2->execute([$mall_id]);
    $st2->setFetchMode(PDO::FETCH_ASSOC);
    $res_tag = $st2->fetchAll();

    for($i = 0;$i<count($res_body);$i++){
        $res_body[$i]["tags"] = $res_tag;
    }


    $res = $res_body;

    $pdo = null;

    return $res[0];
}