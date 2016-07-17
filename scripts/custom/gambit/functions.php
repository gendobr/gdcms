<?php
function gambit_request($url,$data){
        // prn("####");
        // $url = \e::config('gambit_product_list');
        // prn("####", $url);
        //$fields_string=urlencode(json_encode($data));
        $fields_string=json_encode($data);
        //prn($fields_string);
        //open connection
        $ch = curl_init();
        // 
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HEADER, "Content-Type: application/json;charset=utf-8");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable 
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        //execute post
        $result = curl_exec($ch);
        //prn(        $result);
        //close connection
        curl_close($ch);
        // prn($result);
        return json_decode($result, true);
}

