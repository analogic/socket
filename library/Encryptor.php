<?php
/**
 * Created by PhpStorm.
 * User: aleksandr
 * Date: 04.05.14
 * Time: 21:10
 */

namespace qshurick\Socket;

interface Encryptor {
    /**
     * @param array $options
     */
    public function setOptions($options = array());

    /**
     * @param string $message
     * @return string encrypted message
     */
    public function encrypt($message);

    /**
     * @param string $message encrypted message
     * @return string decrypted message
     */
    public function decrypt($message);
}