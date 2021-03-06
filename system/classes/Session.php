<?php

    /**
     * @author Joshua Kissoon
     * @date 20121212
     * @description Manages sessions throughout the site
     */
    class Session
    {

        /**
         * @desc Start the session
         */
        public static function start()
        {
            session_start();
        }

        /**
         * @desc Destroy the current session 
         */
        public static function destroy()
        {
            session_destroy();
        }

        /**
         * @desc Creates a new session and logs in a user
         * @param User The user to log in
         */
        public static function loginUser(User $user)
        {
            session_regenerate_id(true);
            $_SESSION['uid'] = $user->getUserID();
            $_SESSION['logged_in'] = true;
            $_SESSION['logged_in_email'] = $user->getEmail();
            $_SESSION['user_type'] = $user->getUserType();

            /* Add the necessary data to the class */
            $_SESSION['ipaddress'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['status'] = 1;

            /* Now we create the necessary cookies for the user and save the session data */
            setcookie("jsmartsid", session_id(), time() + 3600 * 300, "/");

            /* Save the entire session data to the database */
            $args = array(
                "::uid" => $_SESSION['uid'],
                "::sid" => session_id(),
                "::ipaddress" => $_SESSION['ipaddress'],
                "::status" => $_SESSION['status'],
                "::user_type" => $_SESSION['user_type'],
                "::data" => json_encode($_SESSION),
            );

            /* Save the session data to the database */
            global $DB;
            $DB->query("INSERT INTO user_session (uid, sid, ipaddress, status, data) VALUES('::uid', '::sid', '::ipaddress', '::status', '::data')", $args);
        }

        /**
         * @desc Try to load the user's data from cookies 
         * @return Boolean whether the load was successful or not
         */
        public static function loadDataFromCookies()
        {
            if (!isset($_COOKIE['jsmartsid']))
            {
                return false;
            }

            /* If there is a cookie, check if there exists a valid database session and load it */
            global $DB;
            $res = $DB->query("SELECT * FROM user_session WHERE sid='::sid' AND status='1' LIMIT 1", array("::sid" => $_COOKIE['jsmartsid']));
            if ($DB->resultNumRows() < 1)
            {
                /* The session is invalid, delete it */
                setcookie("jsmartsid", "", time() - 3600);
                return false;
            }

            /* The session is valid, Load all of the data into session, generate a new sid and update it in the database */
            $row = $DB->fetchObject($res);
            $data = json_decode($row->data);
            foreach ($data as $key => $value)
            {
                $_SESSION[$key] = $value;
            }

            /* Add the necessary data to the class */
            session_regenerate_id(true);
            $_SESSION['ipaddress'] = $_SERVER['REMOTE_ADDR'];

            /* update the session id to the database */
            $args = array("::usid" => $row->usid, "::sid" => session_id());
            return $DB->query("UPDATE user_session SET sid = '::sid' WHERE usid='::usid'", $args);
        }

        /**
         * @desc Here we logout the user and destroy the session 
         */
        public static function logoutUser()
        {
            global $DB;

            /* Set the session's status to 0 in the database */
            $DB->query("UPDATE user_session SET status = '0' WHERE sid='::sid'", array("::sid" => session_id()));

            unset($_SESSION['uid']);
            unset($_SESSION['logged_in']);
            unset($_SESSION['user_type']);
            unset($_SESSION['logged_in_email']);
            unset($_SESSION['ipaddress']);
            unset($_SESSION['status']);
            self::destroy();
        }

        /**
         * @desc Checks whether a user is logged in
         * @return Boolean Whether the user is logged in or not
         */
        public static function isLoggedIn()
        {
            return (isset($_SESSION['logged_in']) && ($_SESSION['logged_in'] === true));
        }

        /**
         * @return The uid of the logged in user
         */
        public static function loggedInUid()
        {
            return isset($_SESSION['uid']) ? $_SESSION['uid'] : false;
        }

    }
    