<?php

namespace PhoneCom\Sdk;

abstract class Grammar
{
    public function compileUrl($pathInfo, $params = [])
    {
        $path = $pathInfo;
        if (is_array($params)) {
            foreach ($params as $param => $value) {
                $path = preg_replace("/\{" . preg_quote($param) . "(\:[^\}]+)?\}/", (string)$value, $path);
            }
        }

        return $path;
    }

}
