<?php

/*
 * ������ ������ ���������
 * ������ ����� ����� SQL ��������, ������� ���� ���������, ����� ��������������� ������ ���������
 * ������� ��������� � �� ������ �� ���������
 *
 * ���������� ��������� ���������� �� ������� 'path' �������  {$table_prefix}category
 *
 *
 */
global $main_template_name;
$main_template_name = '';

if (!is_admin())
    exit('Access denied');

$site_id = (int) $input_vars['site_id'];


// check the category tree

$query = "set @site_id=$site_id";
\e::db_execute($query);

//���� �� ���������, ������� ����������� ����� � ��������
$query = "select @category_id:=category_id, @start:=`start`, @finish:=finish from {$GLOBALS['table_prefix']}category where site_id=@site_id and `start`=0";


$query = "
/* ���� �� ���������, ������� ����������� ����� � �������� */
select ch.category_id, pa.category_id from {$GLOBALS['table_prefix']}category ch,  {$GLOBALS['table_prefix']}category pa
where ch.start<pa.start and pa.start<ch.finish
and pa.finish>ch.finish
and pa.site_id=@site_id
and ch.site_id=@site_id

union

/* ���� start � finish ������ ���� ��������� */
select ch.category_id, pa.category_id from {$GLOBALS['table_prefix']}category ch,  {$GLOBALS['table_prefix']}category pa
where ( ch.start=pa.start OR ch.start=pa.finish OR ch.finish=pa.finish )
and pa.site_id=@site_id
and ch.site_id=@site_id
and ch.category_id<>pa.category_id
";
$rows = \e::db_getrows($query);
if (count($rows) == 0) {
    echo "<h3>All category tree looks consistent</h3>";
}

/*
  select ch.category_id, pa.category_id from {$GLOBALS['table_prefix']}category ch,  {$GLOBALS['table_prefix']}category pa
  where ch.start<pa.start and pa.start<ch.finish
  and pa.finish>ch.finish
  and pa.site_id=@site_id
  and ch.site_id=@site_id;

  select ch.category_id, pa.category_id from {$GLOBALS['table_prefix']}category ch,  {$GLOBALS['table_prefix']}category pa
  where ( ch.start=pa.start OR ch.start=pa.finish OR ch.finish=pa.finish )
  and pa.site_id=@site_id
  and ch.site_id=@site_id
  and ch.category_id<>pa.category_id;
 */

$query = "select category_id, site_id,category_title,start,finish,deep,path from {$table_prefix}category where site_id=$site_id order by path asc";
$categories = \e::db_getrows($query);

// ���������� ��������
function n_descendants($path, $categories) {
    //$deep=substr_count($path,'/');
    $cnt = 0;
    $len = strlen($path) + 1;
    $prefix = "$path/";
    foreach ($categories as $cat) {
        if (substr($cat['path'], 0, $len) == $prefix) {
            $cnt++;
        }
    }
    return $cnt;
}

// echo n_descendants('227/diff',$categories);
// ������������� ����������� �����
function get_prev_sibling($path, $categories) {
    // prn($categories);exit();
    $deep = substr_count($path, '/');
    $prefix = preg_replace("/(^|\\/)[^\\/]+\$/", '/', $path);
    // prn($path." prefix=".$prefix);
    $len = strlen($prefix);
    $cnt = -1;


    //  ����� ���� �������
    $siblings = Array();
    foreach ($categories as $key => $cat) {
        // prn($cat['path']);
        // if($cat['path']==$path) break;
        // prn(substr($cat['path'],0,$len), $prefix, substr($cat['path'],0,$len)==$prefix,'===');
        if (substr($cat['path'], 0, $len) == $prefix && $deep == substr_count($cat['path'], '/')) {
            //prn($key,$cat['path']);
            $siblings[$key] = $cat;
        }
    }
    // prn($path,$siblings);
    // ����������� �� ���� start
    uasort($siblings, "orderByStart");
    // prn($path,$siblings);
    //foreach($categories as $key=>$cat){
    foreach ($siblings as $key => $cat) {
        // prn($cat['path']);
        if ($cat['path'] == $path)
            break;
        // prn(substr($cat['path'],0,$len), $prefix, substr($cat['path'],0,$len)==$prefix,'===');
        if (substr($cat['path'], 0, $len) == $prefix && $deep == substr_count($cat['path'], '/')) {
            $cnt = $key;
        }
    }
    return $cnt;
}

function orderByStart($a, $b) {
    return abs($a['start']) - abs($b['start']);
}

//prn("strcmp('227/diff', '227/university')=".strcmp('227/diff', '227/university'));
//echo " all/dept/econ get_prev_siblings=". get_prev_sibling('all/dept/econ',$categories);exit();
// ����� ��������
function get_parent($path, $categories) {
    //prn($path);
    $prefix = preg_replace("/(^|\\/)[^\\/]+\$/", '', $path);
    foreach ($categories as $key => $cat) {
        if ($cat['path'] == $prefix) {
            return $key;
        }
    }
    return -1;
}

// ����� ���������� ������
function get_ancestor($path, $categories) {
    //prn($path);
    $prefix = preg_replace("/(^|\\/)[^\\/]+\$/", '', $path);
    while ($prefix != '') {
        foreach ($categories as $key => $cat) {
            if ($cat['path'] == $prefix) {
                return $key;
            }
        }
        $prefix = preg_replace("/(^|\\/)[^\\/]+\$/", '', $prefix);
    }
    return -1;
}

// prn(get_parent('227/university',$categories)); exit();
// �������������� ��������
function get_children($path, $categories) {
    $deep = substr_count($path, '/') + 1;
    $cnt = Array();
    $len = strlen($path) + 1;
    $prefix = "$path/";
    foreach ($categories as $key => $cat) {
        if (substr($cat['path'], 0, $len) == $prefix && $deep == substr_count($cat['path'], '/')) {
            $cnt[] = $key;
        }
    }
    return $cnt;
}

function catcmp($a, $b) {
    return strcmp($a['path'], $b['path']);
}

usort($categories, "catcmp");
// check paths integrity
$cnt = count($categories);

$newcategories = Array();
$newcategories[0] = $categories[0];
for ($i = 1; $i < $cnt; $i++) {
    $key = get_parent($categories[$i]['path'], $newcategories);
    $ccc = 0;

    $parent_path = $categories[$i]['path'];
    while ($key < 0) {
        // �������� �� ������, ���� ��� �������
        prn("creating parent for $parent_path ");
        $parent_path = preg_replace("/(^|\\/)[^\\/]+\$/", '', $parent_path);
        $parent_code = preg_replace("/^(\\w+\\/)+/", '', $parent_path);
        $parent_deep = substr_count($parent_path, '/');
        // prn('$parent_path='.$parent_path, '$parent_code='.$parent_code,'$parent_deep='.$parent_deep);
        $newcategories[] = Array(
            'category_id' => null,
            'site_id' => $site_id,
            'category_code' => $parent_code,
            'category_title' => $parent_code,
            'category_description' => '',
            'category_concept' => '',
            'start' => 0,
            'finish' => 0,
            'is_deleted' => 0,
            'deep' => $parent_deep,
            'is_part_of' => null,
            'see_also' => '',
            'is_visible' => 1,
            'path' => $parent_path,
            'date_last_changed' => date('Y-m-d H:i:s')
        );
        $key = get_parent($parent_path, $newcategories);
        prn($newcategories[count($newcategories) - 1]);
        if (($ccc++) > 10000)
            exit();
    }
    $newcategories[] = $categories[$i];
}

usort($newcategories, "catcmp");
$categories = $newcategories;

// prn($categories); exit();
// ����������� ��������� � ���������� �������
// ����� ������ (�� 1-� � ������, �.�. ���� � ���� ����� ��������)
//   � ����� ����� ����� � ���-�� ��������, � ����� ��������� � ���������� �������
//   ��� ������� �� �����


class node {

    public $info;
    public $n_descentants;
    public $start;
    public $finish;
    public $deep;
    public $children;

    function __construct($info, & $categories, $start) {
        $this->info = $info;
        $this->deep = substr_count($this->info['path'], '/');
        $this->start = $start;
        $this->n_descentants = $this->count_descentants($info['path'], $categories);
        $this->finish = $start + 1 + 2 * $this->n_descentants;

        $this->children = $this->get_children($info['path'], $categories, $start);
    }

    function get_children($path, & $categories, $parent_start) {
        $deep = substr_count($path, '/') + 1;
        $cnt = Array();
        $len = strlen($path) + 1;
        $prefix = "$path/";
        $children = Array();
        foreach ($categories as $key => $cat) {
            if (substr($cat['path'], 0, $len) == $prefix && $deep == substr_count($cat['path'], '/')) {
                $children[$key] = $cat;
            }
        }
        // ����������� �� ���� start
        uasort($children, "orderByStart");

        $children_nodes = Array();
        $start = $parent_start + 1;
        foreach ($children as $key => $cat) {
            $node = new node($cat, $categories, $start);
            $children_nodes[] = $node;
            $start = $node->finish + 1;
        }
        return $children_nodes;
    }

    function count_descentants($path, & $categories) {
        $deep = substr_count($path, '/') + 1;
        $cnt = Array();
        $len = strlen($path) + 1;
        $prefix = "$path/";
        $count = 0;
        foreach ($categories as $key => $cat) {
            if (substr($cat['path'], 0, $len) == $prefix) {
                $count++;
            }
        }
        return $count;
    }

    function print_update_sql($site_id) {
        global $table_prefix;
        if ($this->info['category_id']) {
            // update if needed
            if ($this->start - $this->info['start'] != 0
                || $this->finish - $this->info['finish'] != 0
                || $this->deep - $this->info['deep'] != 0) {
                // echo "{$cat['start_new']}-{$cat['start']}!=0 || {$cat['finish_new']}-{$cat['finish']}!=0 || {$cat['deep_new']}-{$cat['deep']}!=0";
                echo htmlspecialchars("UPDATE {$table_prefix}category
                                  SET start={$this->start},
                                      finish={$this->finish},
                                      deep={$this->deep}
                                  WHERE site_id=$site_id AND category_id={$this->info['category_id']}") . ';<br/>';
            }
        } else {
            echo htmlspecialchars("INSERT INTO {$table_prefix}category
                                  SET site_id=$site_id,
                                      category_code='{$this->info['category_code']}',
                                      category_title='{$this->info['category_code']}',
                                      category_description='',
                                      category_concept='',
                                      start={$this->start},
                                      finish={$this->finish},
                                      is_deleted=0,
                                      deep={$this->deep},
                                      is_part_of=null,
                                      see_also='',
                                      is_visible=1,
                                      path='{$this->info['path']}',
                                      date_last_changed=now();
                                      ") . '<br/>';
        }
        foreach($this->children as $child){
            $child->print_update_sql($site_id);
        }
    }

}

$tree = new node($categories[0], $categories, 0);
// prn($tree);
$tree->print_update_sql($site_id);

exit();
//
//
//
//
//
//$cnt = count($categories);
//$newcategories = Array();
//
//$categories[0]['start_new'] = 0;
//$categories[0]['finish_new'] = $cnt * 2 - 1;
//$categories[0]['deep_new'] = 0;
//// prn("{$categories[0]['category_id']} {$categories[0]['path']}  {$categories[0]['start_new']} {$categories[0]['finish_new']}");
//$newcategories[0] = $categories[0];
//for ($i = 1; $i < $cnt; $i++) {
//    //prn("============= $i {$categories[$i]['path']}");
//    //$key=get_prev_sibling($categories[$i]['path'],$newcategories);
//    $key = get_prev_sibling($categories[$i]['path'], $categories);
//    if ($key > 0) {
//        //prn("get_prev_sibling key=$key");
//        $newstart = $categories[$key]['finish_new'] + 1;
//    } else {
//        //$key=get_ancestor($categories[$i]['path'],$categories);
//        $key = get_parent($categories[$i]['path'], $newcategories);
//        //prn("get_parent key=$key");
//        $newstart = $categories[$key]['start_new'] + 1;
//        if ($key < 0) {
//            prn($categories[$i]);
//            exit('ERROR at row ' . $i);
//        }
//    }
//
//    $ndesc = n_descendants($categories[$i]['path'], $categories);
//    // echo "$i {$categories[$i]['path']} ndesc=$ndesc";
//    $newfinish = $newstart + 2 * $ndesc + 1;
//
//    $categories[$i]['start_new'] = $newstart;
//    $categories[$i]['finish_new'] = $newfinish;
//    $categories[$i]['deep_new'] = substr_count($categories[$i]['path'], '/');
//    prn("$i {$categories[$i]['category_id']} {$categories[$i]['path']} | deep={$categories[$i]['deep_new']} | start= {$categories[$i]['start_new']} | finish= {$categories[$i]['finish_new']}");
//
//    $newcategories[$i] = $categories[$i];
//}
//
//
//// create sql query to repair tree
//foreach ($newcategories as $cat) {
//    if ($cat['category_id']) {
//        // update if needed
//        if ($cat['start_new'] - $cat['start'] != 0 || $cat['finish_new'] - $cat['finish'] != 0 || $cat['deep_new'] - $cat['deep'] != 0) {
//            // echo "{$cat['start_new']}-{$cat['start']}!=0 || {$cat['finish_new']}-{$cat['finish']}!=0 || {$cat['deep_new']}-{$cat['deep']}!=0";
//            echo checkStr("UPDATE {$table_prefix}category
//                                  SET start={$cat['start_new']},
//                                      finish={$cat['finish_new']},
//                                      deep={$cat['deep_new']}
//                                  WHERE site_id=$site_id AND category_id={$cat['category_id']}") . ';<br/>';
//        }
//    } else {
//        echo checkStr("INSERT INTO {$table_prefix}category
//                                  SET site_id=$site_id,
//                                      category_code='{$cat['category_code']}',
//                                      category_title='{$cat['category_code']}',
//                                      category_description='',
//                                      category_concept='',
//                                      start={$cat['start_new']},
//                                      finish={$cat['finish_new']},
//                                      is_deleted=0,
//                                      deep={$cat['deep_new']},
//                                      is_part_of=null,
//                                      see_also='',
//                                      is_visible=1,
//                                      path='{$cat['path']}',
//                                      date_last_changed=now();
//                                      ") . '<br/>';
//    }
//}
//


?>