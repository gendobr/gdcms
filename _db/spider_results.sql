select count(*) from cms_site_search where year(date_indexed)=2011;
select count(*) from cms_site_search where is_valid>0 and year(date_indexed)<2011;
select * from cms_site_search where year(date_indexed)=2011 order by date_indexed desc limit 0,200;
select * from cms_site_search where is_valid>0 order by date_indexed asc limit 0,200;

select * from cms_site_search where url like 'http://sites.znu.edu.ua/historySciWorks%';

select * from cms_site_search where url like '%action=calendar%' and site_id=74;
/* 
   delete from cms_site_search where url like '%action=calendar%' and site_id=74;
*/