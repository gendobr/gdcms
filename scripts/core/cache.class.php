<?php

namespace core;

/**
 * Description of cache
 *
 * @author dobro
 */
class cache {

    public $cacheRoot;

    public function __construct($cacheRoot) {
        $this->cacheRoot = $cacheRoot;
    }

    public function cachedData($cacheFileName, $timeout, $datasource) {
        $cachePath = "{$this->cacheRoot}/{$cacheFileName}";
        //if ($cacheFileName == 'topphoto') {
        //    \env::info('', "cachedData($cacheFileName, $timeout,");
        //    \env::info('', "cachePath=$cachePath;file_exists(,,,)=".file_exists($cachePath).'; mtime='.(filemtime($cachePath) + $timeout >= time()));
        //}
        if (file_exists($cachePath) && filemtime($cachePath) + $timeout >= time()) {
            //if ($cacheFileName == 'topphoto') {
            //    \env::info('', 'using cache');
            //}
            return unserialize(file_get_contents($cachePath));
        } else {
            // (re)create cache file
            //if ($cacheFileName == 'topphoto') {
            //    \env::info('', 're-cache');
            //}
            $html = $datasource();
            // put html to cache
            file_put_contents($cachePath, serialize($html));
            return $html;
        }
    }

}
