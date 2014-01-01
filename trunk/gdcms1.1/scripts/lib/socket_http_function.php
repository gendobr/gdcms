<?php
/**
 do http request using PHP socket functions
 */

# ------------------------- load declarations - begin --------------------------
run('lib/http/class_pear');
run('lib/http/class_net_socket');
run('lib/http/class_net_url');
run('lib/http/class_http_request');
# ------------------------- load declarations - end ----------------------------

function http($url,$get,$post,$proxy='',$port='80') {
    # ----------------------- initiate - begin -----------------------------------
    $obj_request = new HTTP_Request($url, Array(
                    'timeout'=>60
                    ,'allowRedirects'=>true
    ));

    # set POST data
    if(is_array($post)) {
        foreach($post as $name=>$value) {
            $obj_request->addPostData($name, $value);
        }
    }


    # set GET data
    if(is_array($get)) {
        foreach($get as $name=>$value) {
            $obj_request->addQueryString($name, $value);
        }
    }


    # set proxy
    if(strlen($proxy)>0) {
        $obj_request->setProxy($proxy, $port, $user = null, $pass = null);
    }
    # ----------------------- initiate - end -------------------------------------



    # load page
    /*
    * @param $url The url to fetch/access
    * @param $params Associative array of parameters which can be:
    *                  method         - Method to use, GET, POST etc
    *                  http           - HTTP Version to use, 1.0 or 1.1
    *                  user           - Basic Auth username
    *                  pass           - Basic Auth password
    *                  proxy_host     - Proxy server host
    *                  proxy_port     - Proxy server port
    *                  proxy_user     - Proxy auth username
    *                  proxy_pass     - Proxy auth password
    *                  timeout        - Connection timeout in seconds.
    *                  allowRedirects - Whether to follow redirects or not
    *                  maxRedirects   - Max number of redirects to follow
    *                  useBrackets    - Whether to append [] to array variable names
    *                  saveBody       - Whether to save response body in response object property

  # The querystring data. Should be of the format foo=bar&x=y etc
    $obj_request->addRawPostData($postdata, $preencoded = true)
    $obj_request->addPostData($name, $value, $preencoded = false)

    $obj_request->addRawQueryString($querystring, $preencoded = true)
    $obj_request->addQueryString($name, $value, $preencoded = false)

    g$obj_request->etResponseCode();
    $obj_request->getResponseBody();

    */
    # set timeout to 100 seconds
    set_time_limit (100);
    $obj_request->sendRequest();
    sleep(5);

    $body = $obj_request->getResponseBody();

    $headers=$obj_request->getResponseHeader();
    # check if request was successful
    $success = $obj_request->getResponseCode();
    $success = ( 200 <= $success && $success < 300 );

    return Array('url'=>$obj_request->_url->url,
            'is_successful'=>$success,
            'body'=>$body,
            'http_status'=>$obj_request->getResponseCode(),
            'headers'=>$headers  );
}

?>