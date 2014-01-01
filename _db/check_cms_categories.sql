set @site_id=73;
/*

Надо проверить:
43 	Кращі випускники                           OK
27	Новини прес-центру ЗНУ                     OK
45	Виробнича практика                         OK
53	Славетні запоріжці                         OK
52	Відмінники ЗНУ                             OK
58	Центр незалежних соціологічних досліджень  OK
65	Наукове товариство студентів та аспірантів OK
66	«Молодий університет»                      
73      Головна сторінка ЗНУ                       ОК
*/

/* 
select @category_id:=category_id, @start:=`start`, @finish:=finish from cms_category where site_id=@site_id and `start`=0; 

*/

/* 
 Есть ли категории, которые неправильно стоят в иерархии


 правильное положение:
    pa.start < ch.start AND ch.finish < pa.finish AND pa.deep + 1 = ch.deep


 возможные ошибки:
 1)   pa.start < ch.start AND ch.finish < pa.finish AND pa.deep + 1 <> ch.deep

 2)   pa.start >= ch.start AND ch.finish < pa.finish

 3)   pa.start < ch.start AND ch.finish >= pa.finish

 */

select pa.category_id as pa_category_id, ch.category_id as ch_category_id
from cms_category ch,  cms_category pa
where 
pa.start >= ch.start AND ch.finish < pa.finish
and pa.site_id=@site_id
and ch.site_id=@site_id
and ch.category_id<>pa.category_id

union
select pa.category_id as pa_category_id, ch.category_id as ch_category_id
from cms_category ch,  cms_category pa
where 
pa.start < ch.start AND ch.finish >= pa.finish
and pa.site_id=@site_id
and ch.site_id=@site_id
and ch.category_id<>pa.category_id

union


select ch.category_id, pa.category_id 
from cms_category ch,  cms_category pa
where 
pa.start >= ch.start AND ch.finish < pa.finish
and pa.site_id=@site_id
and ch.site_id=@site_id
and ch.category_id<>pa.category_id

union

/*
  поля start и finish должны быть уникальны
 */
select ch.category_id, pa.category_id from cms_category ch,  cms_category pa
where ( ch.start=pa.start OR ch.start=pa.finish OR ch.finish=pa.finish ) 
and pa.site_id=@site_id
and ch.site_id=@site_id
and ch.category_id<>pa.category_id;


/* 
select distinct site_id from cms_category;
select * from cms_category where site_id=@site_id order by start;


UPDATE cms_category SET start=1, finish=2, deep=1 WHERE site_id=66 AND category_id=181;
UPDATE cms_category SET start=3, finish=4, deep=1 WHERE site_id=66 AND category_id=179;
UPDATE cms_category SET start=5, finish=6, deep=1 WHERE site_id=66 AND category_id=195;
UPDATE cms_category SET start=7, finish=8, deep=1 WHERE site_id=66 AND category_id=194;
UPDATE cms_category SET start=9, finish=10, deep=1 WHERE site_id=66 AND category_id=201;
UPDATE cms_category SET start=11, finish=12, deep=1 WHERE site_id=66 AND category_id=196;
UPDATE cms_category SET start=13, finish=40, deep=1 WHERE site_id=66 AND category_id=178;
UPDATE cms_category SET start=14, finish=15, deep=2 WHERE site_id=66 AND category_id=185;
UPDATE cms_category SET start=18, finish=19, deep=2 WHERE site_id=66 AND category_id=198;
UPDATE cms_category SET start=20, finish=21, deep=2 WHERE site_id=66 AND category_id=184;
UPDATE cms_category SET start=22, finish=23, deep=2 WHERE site_id=66 AND category_id=183;
UPDATE cms_category SET start=24, finish=25, deep=2 WHERE site_id=66 AND category_id=186;
UPDATE cms_category SET start=26, finish=27, deep=2 WHERE site_id=66 AND category_id=188;
UPDATE cms_category SET start=28, finish=29, deep=2 WHERE site_id=66 AND category_id=189;
UPDATE cms_category SET start=30, finish=31, deep=2 WHERE site_id=66 AND category_id=199;
UPDATE cms_category SET start=32, finish=33, deep=2 WHERE site_id=66 AND category_id=191;
UPDATE cms_category SET start=34, finish=35, deep=2 WHERE site_id=66 AND category_id=187;
UPDATE cms_category SET start=36, finish=37, deep=2 WHERE site_id=66 AND category_id=197;
UPDATE cms_category SET start=38, finish=39, deep=2 WHERE site_id=66 AND category_id=192;
UPDATE cms_category SET start=41, finish=42, deep=1 WHERE site_id=66 AND category_id=180;
UPDATE cms_category SET start=43, finish=44, deep=1 WHERE site_id=66 AND category_id=202;
UPDATE cms_category SET start=45, finish=46, deep=1 WHERE site_id=66 AND category_id=182;


 */