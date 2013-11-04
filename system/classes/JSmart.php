<?php

    /**
     * @author Joshua Kissoon
     * @date 20121214
     * @description A class with methods specific to JSmart sites
     */
    class JSmart
    {

       public static function log($type, $message)
       {
          /* Function that logs a smart message */
          global $DB;
          $res = $DB->query("INSERT INTO logs (type, message) VALUES (':type', ':message')", array(":type" => $type, ":message" => $message));
          return ($res) ? true : false;
       }

       public static function variableSet($vid, $value)
       {
          /* Set a variable in the site table that can be used later */
          global $DB;
          $args = array("::vid" => $vid, "::value" => $value);
          $sql = "INSERT INTO variables (vid, value) VALUES ('::vid', '::value')
                ON DUPLICATE KEY UPDATE value='::value'";
          $res = $DB->query($sql, $args);
          return $res;
       }

       public static function variableGet($vid)
       {
          /* Retrieves a variable that was set earlier in the site variables table */
          global $DB;
          $vid = $DB->escapeString($vid);
          $res = $DB->query("SELECT value FROM variables WHERE vid='$vid'");
          $res = $DB->fetchObject($res);
          return @$res->value;
       }

       public static function getSiteName()
       {
          return self::variableGet("sitename");
       }

       public static function permissionDeniedError()
       {
          /*
           * Return the permission denied error
           */
          return "You do not have the necessary permissions to access this page";
       }

    }