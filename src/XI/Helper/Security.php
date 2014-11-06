<?php

namespace XI\Helper;

use XI\Core\Security as CoreSecurity;

class Security
{

    /**
     * Hash encode a string
     *
     * @access    public
     * @param    string
     * @param    string
     * @return    string
     */
    public static function do_hash($str, $type = 'sha1')
    {
        if ($type == 'sha1') {
            return sha1($str);
        } else {
            return md5($str);
        }
    }


    /**
     * Strip Image Tags
     *
     * @access    public
     * @param    string
     * @return    string
     */
    public static function strip_image_tags($str)
    {
        $str = preg_replace("#<img\s+.*?src\s*=\s*[\"'](.+?)[\"'].*?\>#", "\\1", $str);
        $str = preg_replace("#<img\s+.*?src\s*=\s*(.+?).*?\>#", "\\1", $str);

        return $str;
    }


    /**
     * Convert PHP tags to entities
     *
     * @access    public
     * @param    string
     * @return    string
     */
    public static function encode_php_tags($str)
    {
        return str_replace(array('<?php', '<?PHP', '<?', '?>'), array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);
    }
}
