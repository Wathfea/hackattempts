<?php

class Saftey {

    private $detect;

    function __construct() {
        if (!isset($_COOKIE['haccaktempts_login_check'])) {
            /**
             * Include the MobileDetect Lib 
             */
            require_once('libs/Mobile_Detect.php');
            $this->detect = new Mobile_Detect;


            $jetpack = false;
            /**
             * Read the config file 
             */
            $config_json = json_decode(file_get_contents('wp-content/plugins/hackattempts/config.json'));

            $login_attempts = $config_json->login_attempts;
            $time_limit = $config_json->time_limit;


            $get = isset($_GET['for']) ? $_GET['for'] : '';
            if ($get == 'jetpack')
                $jetpack = true;

            if (!$jetpack) {
                if (!$this->detect->isMobile()) {
                    $def_counter = 1;

                    //Get the IP address
                    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                        $remote_ip = $_SERVER['HTTP_CLIENT_IP'];
                    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                        $remote_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                    } else {
                        $remote_ip = $_SERVER['REMOTE_ADDR'];
                    }

                    $filename = $remote_ip . '.json';


                    if (file_exists('hackattempts/' . $filename)) {
                        $opened_file = json_decode(file_get_contents('hackattempts/' . $filename));

                        if ($opened_file->banned == true) {
                            die('You are locked out! If you are locked out accidentally please contact with the site administrator and send your IP address: ' . $remote_ip);
                        }

                        $opened_file->counter++;
                        $opened_file->uri = $_SERVER["REQUEST_URI"];

                        $timestamp = $opened_file->timestamp;
                        $counter = $opened_file->counter;

                        if ((time() - $timestamp) < $time_limit) {
                            if ($counter > $login_attempts) {
                                $opened_file->banned = true;
                                $opened_file = json_encode($opened_file);
                                $fp = fopen('hackattempts/' . $filename, 'w');
                                fwrite($fp, $opened_file);
                                fclose($fp);
                                die('You are locked out! If you are locked out accidentally please contact with the site administrator and send your IP address: ' . $remote_ip);
                            }
                        } else {
                            $opened_file->ip = $remote_ip;
                            $opened_file->timestamp = time();
                            $opened_file->counter = 1;
                            $opened_file->banned = false;
                        }

                        $opened_file = json_encode($opened_file);
                        $fp = fopen('hackattempts/' . $filename, 'w');
                        fwrite($fp, $opened_file);
                        fclose($fp);
                    } else {
                        $url = 'http://ip-api.com/json/' . $remote_ip;
                        $response = json_decode(@file_get_contents($url));
                        $country = isset($response->country) ? $response->country : '';
                        $city = isset($response->city) ? $response->city : '';

                        $data = array(
                            "ip" => $remote_ip,
                            "timestamp" => time(),
                            "counter" => $def_counter,
                            "uri" => $_SERVER["REQUEST_URI"],
                            "banned" => false,
                            "country" => $country,
                            "city" => $city
                        );

                        file_put_contents('hackattempts/' . $filename, json_encode($data));
                    }
                }
            }
        }
    }

}

new Saftey();
?>