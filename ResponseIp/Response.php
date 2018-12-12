<?php
class Response
{
    /**
     * Get IP
     *
     * @return string
     */
    public static function get_ip(): string
    {
        //IP check list
        $chk_list = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        //Check ip values
        foreach ($chk_list as $key) {
            if (!isset($_SERVER[$key])) {
                continue;
            }
            
            $ip_list = false !== strpos($_SERVER[$key], ',') ? explode(',', $_SERVER[$key]) : [$_SERVER[$key]];
            
            foreach ($ip_list as $ip) {
                $ip = filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);
                
                if (false !== $ip) {
                    unset($chk_list, $key, $ip_list);
                    return $ip;
                }
            }
        }
        
        unset($chk_list, $key, $ip_list, $ip);
        return '0.0.0.0';
    }
}
